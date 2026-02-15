<?php

namespace Noo\StatamicLoginLink\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Noo\StatamicLoginLink\Support\SecurityCheck;
use Statamic\Facades\User;

class LoginLinkController
{
    public function __construct(
        protected SecurityCheck $securityCheck,
    ) {}

    public function __invoke(Request $request, string $email)
    {
        abort_unless($request->hasValidSignature(), 401);

        abort_unless($this->securityCheck->passes($request), 403);

        abort_unless($this->isConfiguredUser($email), 403);

        $user = User::findByEmail($email);

        abort_unless($user, 404);

        $guard = config('statamic.users.guards.cp', 'web');

        Auth::guard($guard)->login($user);

        $request->session()->regenerate();

        $redirect = config('statamic.login-link.redirect_url') ?? cp_route('index');

        return redirect($redirect);
    }

    protected function isConfiguredUser(string $email): bool
    {
        return array_key_exists($email, config('statamic.login-link.users', []));
    }
}
