<?php

namespace Noo\StatamicLoginLink\Support;

use Illuminate\Http\Request;

class SecurityCheck
{
    public function passes(Request $request): bool
    {
        return $this->hasConfiguredUsers()
            && $this->isAllowedEnvironment()
            && $this->isAllowedIp($request);
    }

    public function hasConfiguredUsers(): bool
    {
        return count(config('statamic-login-link.users', [])) > 0;
    }

    public function isAllowedEnvironment(): bool
    {
        return app()->environment(config('statamic-login-link.allowed_environments', []));
    }

    public function isAllowedIp(Request $request): bool
    {
        return in_array($request->ip(), config('statamic-login-link.allowed_ips', []));
    }
}
