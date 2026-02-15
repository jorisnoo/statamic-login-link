# Statamic Login Link

One-click developer login buttons for the Statamic CP. Skip typing credentials on localhost.

Adds "Developer Login" buttons below the CP login form that authenticate you instantly via signed URLs. Multiple independent security layers ensure this can never activate in production.

Inspired by [spatie/laravel-login-link](https://github.com/spatie/laravel-login-link) and [filament-developer-logins](https://github.com/DutchCodingCompany/filament-developer-logins), built for Statamic 6.

## Requirements

- PHP 8.3+
- Statamic 6
- Laravel 12

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
2. Each button is a plain `<a>` tag pointing to a signed URL (no JavaScript needed)
3. Clicking a button hits a controller that validates the signature, runs all security checks, and authenticates you
4. You're redirected to the CP dashboard

## Security

The package is gated by multiple independent layers:

| Layer | What it prevents |
|---|---|
| Empty user list = disabled | Package is inert until configured |
| Environment whitelist (`local`) | Any injection/login on production |
| Host whitelist (`localhost`, `*.test`) | Remote access even on local env |
| Signed URL with 5-min expiry | URL tampering, replay attacks |
| Configured user check | Login as non-configured users |
| User existence check | Login as non-existent users |
| Session regeneration | Session fixation |

Middleware and controller enforce environment + host checks **independently** — even if middleware is somehow bypassed, the controller still rejects.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
