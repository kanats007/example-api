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
    private readonly string $realm;
    private readonly string $hostname;
    private readonly string $keyCloakUrl;
    private readonly string $clientId;
    private readonly string $frontendUrl;
    private readonly string $redirectUri;
    public function __construct(private readonly UserRepository $userRepository)
    {
        $this->realm = config('keycloak.realm');
        $this->hostname = config('keycloak.hostname');;
        $this->keyCloakUrl = config('keycloak.url');
        $this->clientId = config('keycloak.client_id');
        $this->frontendUrl = config('keycloak.frontend_url');
        $this->redirectUri = config('keycloak.redirect_uri');
    }

    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $state = $request->cookie('state');
        // stateが一致しない場合はエラー
        if ($state !== $request->state) {
            return response()->json(['errorMessage' => ['state does not match.']], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $code = $request->code;
        $response = Http::asForm()->post("{$this->hostname}/realms/{$this->realm}/protocol/openid-connect/token", [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
        ]);

        $idToken = JwtPerser::parse($response->object()->id_token);
        $jwtValidator = new JwtValidator(
            $this->keyCloakUrl . '/realms/' . $this->realm,
            $this->clientId,
            InMemory::plainText($this->getKeycloakPublicKey()),
        );
        $jwtValidator->validate($idToken->toString());

        // TODO:IDトークン、アクセストークン、リフレッシュトークンをDBに保存する（別サービスのAPIを呼び出せるようにするため）

        // JWTの作成
        // 本アプリ用のユーザーテーブルを用意してIDトークンのSubと紐付けてユーザーIDを発行＆保存し、自前JWTには本アプリで発行したIDを入れる
        $user = $this->userRepository->findByOidcUserId($idToken->claims()->get('sub'));
        if ($user === null) {
            $user = $this->userRepository->create(
                $idToken->claims()->get('sub'),
                $idToken->claims()->get('name'),
                $idToken->claims()->get('email'),
                $idToken->toString(),
                $response->object()->access_token,
                $response->object()->refresh_token,
            );
        } else {
            $this->userRepository->updateToken(
                $user->user_id,
                $idToken->toString(),
                $response->object()->access_token,
                $response->object()->refresh_token,
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
                $this->frontendUrl . "/token#{$token->toString()}"
            )->withoutCookie('state');
        } else {
            return response()->json($response->object(), $response->status(), [])->withoutCookie('state');
        }
    }

    public function login(): JsonResponse
    {
        $state = Str::random(19);
        $cookie = cookie('state', $state, 5);

        return response()->json(
            [
                'redirectUrl' =>
                "{$this->keyCloakUrl}/realms/{$this->realm}/protocol/openid-connect/auth"
                    . "?scope=openid"
                    . "&response_type=code"
                    . "&client_id={$this->clientId}"
                    . "&state={$state}"
                    . "&redirect_uri={$this->redirectUri}"
            ],
            HttpResponse::HTTP_OK,
            []
        )->cookie($cookie);
    }

    public function logout(): JsonResponse
    {
        return response()->json(
            ['redirectUrl' => "{$this->keyCloakUrl}/realms/{$this->realm}/protocol/openid-connect/logout?post_logout_redirect_uri={$this->frontendUrl}/logout&client_id={$this->clientId}"],
            HttpResponse::HTTP_OK,
            []
        );
    }

    public function user(Request $request): JsonResponse
    {
        $token = JwtPerser::parse($request->bearerToken());
        $user = $this->userRepository->findByUserId($token->claims()->get('sub'));
        $data = $user !== null ? ['name' => $user->name, 'email' => $user->email] : [];
        return response()->json($data, HttpResponse::HTTP_OK, []);
    }

    private function getKeycloakPublicKey(): string
    {
        $response = Http::get("{$this->hostname}/realms/{$this->realm}");
        $publicKey = $response->object()->public_key;

        // pem形式にする
        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= $publicKey;
        $pem .= "\n-----END PUBLIC KEY-----\n";
        return $pem;
    }
}
