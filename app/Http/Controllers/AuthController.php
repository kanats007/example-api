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

        $id_token = JwtPerser::parse($response->object()->id_token);
        $jwtValidator = new JwtValidator(
            config('keycloak.url') . '/realms/' . config('keycloak.realm'),
            config('keycloak.client_id'),
            base_path('storage/jwt/keycloak/publickey.pem'),
        );
        $jwtValidator->validate($id_token);

        $token = JwtGenerator::generateToken(
            base_path('storage/jwt/id_ed25519'),
            $id_token->claims()->get('sub'),
            $id_token->claims()->get('exp'),
        );

        if ($response->ok()) {
            return redirect(
                config('keycloak.frontend_url') . "#token={$token->toString()}"
            )->withoutCookie('state');
        } else {
            return response()->json($response->object(), $response->status(), [])->withoutCookie('state');
        }
    }

    public function login(): RedirectResponse
    {
        $clientId = config('keycloak.client_id');
        $redirect_uri = config('keycloak.redirect_uri');
        $url = config('keycloak.url');
        $realm = config('keycloak.realm');
        $state = Str::random(19);
        $cookie = cookie('state', $state, 5);
        return redirect(
            "{$url}/realms/{$realm}/protocol/openid-connect/auth"
            . "?scope=openid"
            . "&response_type=code"
            . "&client_id={$clientId}"
            . "&state={$state}"
            . "&redirect_uri={$redirect_uri}"
        )->cookie($cookie);
    }
}
