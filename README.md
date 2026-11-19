# Statamic Login Link

One-click developer login buttons for the Statamic CP. Skip typing credentials on localhost.

Adds developer login links after the CP login page. Use this dev dependency only on a private local server, never on an exposed or tunneled instance.

Inspired by [spatie/laravel-login-link](https://github.com/spatie/laravel-login-link) and [filament-developer-logins](https://github.com/DutchCodingCompany/filament-developer-logins), built for Statamic 6.

## Requirements

- PHP 8.3+
- Statamic 6
- Laravel 12 or 13, as supported by Statamic

## Installation

```bash
composer require jorisnoo/statamic-login-link --dev
```

Publish the config file:

```bash
php artisan vendor:publish --tag=statamic-login-link-config
```

## Configuration

Add users to `config/statamic/login-link.php`:

```php
return [
    'users' => [
        'admin@example.com' => 'Admin',
        'editor@example.com' => 'Editor',
    ],

    'allowed_environments' => ['local'],
    'allowed_hosts' => ['localhost', '127.0.0.1', '*.test'],
    'redirect_url' => null, // null = CP index
    'link_expiration_minutes' => 5,
];
```

The package does nothing until you add at least one user.

## How It Works

1. Middleware detects the CP login page and injects styled buttons before `</body>`
2. Each button is an ordinary signed link outside the JavaScript app; no form or inline script is required
3. Clicking a button hits a controller that validates the signature, runs all security checks, and authenticates you
4. You're redirected to the CP dashboard

## Security

The package is gated by multiple independent layers:

| Layer | What it prevents |
|---|---|
| Empty user list = disabled | Package is inert until configured |
| Environment allowlist (`local`) | Operation outside an explicitly allowed environment |
| Direct loopback peer, no forwarding headers | Logins over the LAN or through an identified proxy |
| Case-insensitive host allowlist (`localhost`, `*.test`) | Unexpected development hostnames; this is not proof the client is local |
| Signed URL with 5-min expiry | URL tampering; a copied link remains usable until it expires |
| Configured user check | Login as non-configured users |
| User existence check | Login as non-existent users |
| Session regeneration | Session fixation |

Both the page and the login endpoint enforce the same checks. The TCP peer (`REMOTE_ADDR`) must be `127.0.0.1` or `::1`; forwarding headers are rejected, not trusted. Do not expose this service through a proxy that strips forwarding information: it is indistinguishable from a local client. A successful developer login regenerates and elevates the CP session, as a normal CP login does.

## Testing

```bash
vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Maintainer releases

See [RELEASING.md](RELEASING.md) for versioning, changelog entries and the release command.
