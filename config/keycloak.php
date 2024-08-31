<?php

return [
    'url' => env('KEYCLOAK_URL', ''),
    'hostname' => env('KEYCLOAK_HOSTNAME', ''),
    'client_id' => env('CLIENT_ID', ''),
    'client_secret' => env('CLIENT_SECRET', ''),
    'redirect_uri' => env('REDIRECT_URI', ''),
    'realm' => env('REALM', ''),
    'frontend_url' => env('FRONTEND_URL', ''),
];
