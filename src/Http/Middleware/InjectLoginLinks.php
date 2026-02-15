<?php

namespace Noo\StatamicLoginLink\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Noo\StatamicLoginLink\Support\SecurityCheck;
use Symfony\Component\HttpFoundation\Response;

class InjectLoginLinks
{
    public function __construct(
        protected SecurityCheck $securityCheck,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldInject($request, $response)) {
            $this->injectLoginLinks($response);
        }

        return $response;
    }

    protected function shouldInject(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->route()?->getName() !== 'statamic.cp.login') {
            return false;
        }

        if (! str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return false;
        }

        return $this->securityCheck->passes($request);
    }

    protected function injectLoginLinks(Response $response): void
    {
        $html = $this->buildHtml();

        $content = $response->getContent();
        $content = str_replace('</body>', "{$html}</body>", $content);

        $response->setContent($content);
    }

    protected function buildHtml(): string
    {
        $users = config('statamic.login-link.users', []);
        $expiration = now()->addMinutes(config('statamic.login-link.link_expiration_minutes', 5));

        $buttons = '';

        foreach ($users as $email => $label) {
            $url = URL::temporarySignedRoute(
                'statamic.cp.login-link',
                $expiration,
                ['email' => $email],
            );

            $escapedUrl = e($url);
            $escapedLabel = e($label);
            $escapedEmail = e($email);

            $buttons .= <<<HTML
                <a href="{$escapedUrl}" class="statamic-login-link">
                    <span class="statamic-login-link-label">{$escapedLabel}</span>
                    <span class="statamic-login-link-email">{$escapedEmail}</span>
                </a>
            HTML;
        }

        return <<<HTML

        <div id="statamic-login-links">
            <div class="statamic-login-links-divider">
                <span>Developer Login</span>
            </div>
            {$buttons}
        </div>
        <style>
            #statamic-login-links {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
                padding: 16px;
                z-index: 9999;
            }
            .statamic-login-links-divider {
                width: 100%;
                text-align: center;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #9ca3af;
                margin-bottom: 4px;
            }
            .dark .statamic-login-links-divider {
                color: #6b7280;
            }
            .statamic-login-link {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 2px;
                padding: 8px 16px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #ffffff;
                color: #374151;
                text-decoration: none;
                font-size: 13px;
                line-height: 1.4;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .statamic-login-link:hover {
                border-color: #3b82f6;
                box-shadow: 0 0 0 1px #3b82f6;
            }
            .statamic-login-link-label {
                font-weight: 600;
            }
            .statamic-login-link-email {
                font-size: 11px;
                color: #9ca3af;
            }
            .dark .statamic-login-link {
                background: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }
            .dark .statamic-login-link:hover {
                border-color: #3b82f6;
                box-shadow: 0 0 0 1px #3b82f6;
            }
            .dark .statamic-login-link-email {
                color: #6b7280;
            }
        </style>

        HTML;
    }
}
