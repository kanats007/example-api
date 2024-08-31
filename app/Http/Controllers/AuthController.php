<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function index(Request $request): RedirectResponse|JsonResponse
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
        if ($response->ok()) {
            return redirect(config('keycloak.frontend_url') . "#id_token={$response->object()->id_token}")->withoutCookie('state');
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
