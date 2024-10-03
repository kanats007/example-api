<?php

namespace App\Http\Controllers;

use App\domain\JwtGenerator;
use App\domain\JwtPerser;
use App\domain\JwtValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $state = $request->cookie('state');
        // stateが一致しない場合はエラー
        if ($state !== $request->state) {
            return response()->json(['errorMessage' => ['state does not match.']], HttpResponse::HTTP_UNAUTHORIZED);
            // return redirect(
            //     config('keycloak.frontend_url') . "/login"
            // )->withoutCookie('state');
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
            // TODO:keycloakの公開鍵はファイルではなくAPI（/realms/{realm}）で取得する
            base_path('storage/jwt/keycloak/publickey.pem'),
        );
        $jwtValidator->validate($idToken->toString());

        // TODO:IDトークン、アクセストークン、リフレッシュトークンをDBに保存する（別サービスのAPIを呼び出せるようにするため）

        // JWTの作成
        // TODO:本アプリ用のユーザーテーブルを用意してIDトークンのSubと紐付けてユーザーIDを発行＆保存し、自前JWTには本アプリで発行したIDを入れる
        // IDを暗号化する
        $token = JwtGenerator::generateToken(
            base_path('storage/jwt/rsa256.key'),
            base_path('storage/jwt/rsa256.pub'),
            $idToken,
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
            ['redirectUrl' => "{$url}/realms/{$realm}/protocol/openid-connect/logout?post_logout_redirect_uri={$frontendUrl}/login&client_id={$clientId}"],
            HttpResponse::HTTP_OK,
            []
        );
    }

    public function user(): JsonResponse
    {
        return response()->json(['name' => 'gojo.satoru'], HttpResponse::HTTP_OK, []);
    }
}
