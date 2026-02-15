<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | Users that can log in via the login link buttons. The key is the email
    | address and the value is the display label shown on the button.
    | Leave empty to disable the package entirely.
    |
    */

    'users' => [
        // 'admin@example.com' => 'Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | The login link buttons will only be shown and the login route will only
    | work when the application environment matches one of these values.
    |
    */

    'allowed_environments' => ['local'],

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs
    |--------------------------------------------------------------------------
    |
    | The login link buttons will only be shown and the login route will only
    | work when the request originates from one of these IP addresses.
    |
    */

    'allowed_ips' => ['127.0.0.1', '::1'],

    /*
    |--------------------------------------------------------------------------
    | Redirect URL
    |--------------------------------------------------------------------------
    |
    | Where to redirect after a successful login. Set to null to redirect
    | to the Statamic CP index.
    |
    */

    'redirect_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Link Expiration
    |--------------------------------------------------------------------------
    |
    | The number of minutes before a signed login link expires.
    |
    */

    'link_expiration_minutes' => 5,

];
