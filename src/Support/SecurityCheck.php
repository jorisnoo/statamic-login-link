<?php

namespace Noo\StatamicLoginLink\Support;

use Illuminate\Http\Request;

class SecurityCheck
{
    public function passes(Request $request): bool
    {
        return $this->hasConfiguredUsers()
            && $this->isAllowedEnvironment()
            && $this->isDirectLocalRequest($request)
            && $this->isAllowedHost($request);
    }

    private function isDirectLocalRequest(Request $request): bool
    {
        if (! in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)) {
            return false;
        }

        foreach ($request->headers->keys() as $header) {
            if (str_starts_with($header, 'x-forwarded-')
                || in_array($header, ['forwarded', 'via', 'x-real-ip', 'x-original-host', 'x-exposed-by'], true)) {
                return false;
            }
        }

        return true;
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
            if (fnmatch(strtolower($allowedHost), $currentHost)) {
                return true;
            }
        }

        return false;
    }
}
