<?php

namespace Noo\StatamicLoginLink\Support;

use Illuminate\Http\Request;

class SecurityCheck
{
    public function passes(Request $request): bool
    {
        return $this->hasConfiguredUsers()
            && $this->isAllowedEnvironment()
            && $this->isAllowedHost($request);
    }

    public function hasConfiguredUsers(): bool
    {
        return count(config('statamic.login-link.users', [])) > 0;
    }

    public function isAllowedEnvironment(): bool
    {
        return app()->environment(config('statamic.login-link.allowed_environments', []));
    }

    public function isAllowedHost(Request $request): bool
    {
        $currentHost = $request->getHost();

        foreach (config('statamic.login-link.allowed_hosts', []) as $allowedHost) {
            if (fnmatch($allowedHost, $currentHost)) {
                return true;
            }
        }

        return false;
    }
}
