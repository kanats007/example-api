<?php

namespace App\Http\Controllers;

use App\Domain\JwtGenerator;
use App\Domain\JwtPerser;
use App\Domain\JwtValidator;
use App\Domain\Repository\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lcobucci\JWT\Signer\Key\InMemory;

class AuthController extends Controller
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $state = $request->cookie('state');
        // stateが一致しない場合はエラー
        if ($state !== $request->state) {
            return response()->json(['errorMessage' => ['state does not match.']], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $code = $request->code;
        $realm = config('keycloak.realm');
        $hostname = config('keycloak.hostname');
        $response = Http::asForm()->post("{$hostname}/realms/{$realm}/protocol/openid-connect/token", [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => config('keycloak.client_id'),
            'redirect_uri' => config('keycloak.redirect_uri'),
        ]);

        $idToken = JwtPerser::parse($response->object()->id_token);
        $jwtValidator = new JwtValidator(
            config('keycloak.url') . '/realms/' . config('keycloak.realm'),
            config('keycloak.client_id'),
            InMemory::plainText($this->getKeycloakPublicKey()),
        );
        $jwtValidator->validate($idToken->toString());

        // TODO:IDトークン、アクセストークン、リフレッシュトークンをDBに保存する（別サービスのAPIを呼び出せるようにするため）

        // JWTの作成
        // 本アプリ用のユーザーテーブルを用意してIDトークンのSubと紐付けてユーザーIDを発行＆保存し、自前JWTには本アプリで発行したIDを入れる
        $user = $this->userRepository->findBySub($idToken->claims()->get('sub'));
        if ($user === null) {
            $user = $this->userRepository->create(
                $idToken->claims()->get('sub'),
                $idToken->claims()->get('name'),
                $idToken->claims()->get('email'),
            );
        }
        // IDを暗号化する
        $token = JwtGenerator::generateToken(
            base_path('storage/jwt/rsa256.key'),
            base_path('storage/jwt/rsa256.pub'),
            $idToken,
            $user->user_id,
        );

        if ($response->ok()) {
            return redirect(
                config('keycloak.frontend_url') . "/token#{$token->toString()}"
            )->withoutCookie('state');
        } else {
            return response()->json($response->object(), $response->status(), [])->withoutCookie('state');
        }
    }

    public function login(): JsonResponse
    {
        $clientId = config('keycloak.client_id');
        $redirect_uri = config('keycloak.redirect_uri');
        $url = config('keycloak.url');
        $realm = config('keycloak.realm');
        $state = Str::random(19);
        $cookie = cookie('state', $state, 5);

        return response()->json(
            [
                'redirectUrl' =>
                    "{$url}/realms/{$realm}/protocol/openid-connect/auth"
                    . "?scope=openid"
                    . "&response_type=code"
                    . "&client_id={$clientId}"
                    . "&state={$state}"
                    . "&redirect_uri={$redirect_uri}"
            ],
            HttpResponse::HTTP_OK,
            []
        )->cookie($cookie);
    }

    public function logout(): JsonResponse
    {
        $url = config('keycloak.url');
        $realm = config('keycloak.realm');
        $clientId = config('keycloak.client_id');
        $frontendUrl = config('keycloak.frontend_url');
        return response()->json(
            ['redirectUrl' => "{$url}/realms/{$realm}/protocol/openid-connect/logout?post_logout_redirect_uri={$frontendUrl}/logout&client_id={$clientId}"],
            HttpResponse::HTTP_OK,
            []
        );
    }

    public function user(): JsonResponse
    {
        return response()->json(['name' => 'gojo.satoru'], HttpResponse::HTTP_OK, []);
    }

    private function getKeycloakPublicKey(): string
    {
        $realm = config('keycloak.realm');
        $hostname = config('keycloak.hostname');
        $response = Http::get("{$hostname}/realms/{$realm}");
        $publicKey = $response->object()->public_key;

        // pem形式にする
        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= $publicKey;
        $pem .= "\n-----END PUBLIC KEY-----\n";
        return $pem;
    }
}
