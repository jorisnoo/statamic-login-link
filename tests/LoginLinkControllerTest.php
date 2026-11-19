<?php

use Illuminate\Support\Facades\URL;
use Statamic\Facades\User;

beforeEach(function () {
    config([
        'statamic.login-link.users' => ['admin@example.com' => 'Admin'],
        'statamic.login-link.allowed_environments' => ['testing'],
        'statamic.login-link.allowed_hosts' => ['localhost'],
        'statamic.login-link.link_expiration_minutes' => 5,
    ]);
});

function createTestUser(string $email = 'admin@example.com'): \Statamic\Contracts\Auth\User
{
    return User::make()
        ->email($email)
        ->makeSuper()
        ->save();
}

function signedLoginUrl(string $email = 'admin@example.com', ?int $minutes = null): string
{
    return URL::temporarySignedRoute(
        'statamic.cp.login-link',
        now()->addMinutes($minutes ?? config('statamic.login-link.link_expiration_minutes')),
        ['email' => $email],
    );
}

it('authenticates a valid user via signed url', function () {
    createTestUser();

    $this->get(signedLoginUrl())
        ->assertRedirect()
        ->assertSessionHas('statamic_elevated_session');

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->email())->toBe('admin@example.com');
});

it('rejects invalid signatures', function () {
    createTestUser();

    $url = signedLoginUrl().'tampered';

    $this->get($url)->assertStatus(401);
});

it('rejects expired signatures', function () {
    createTestUser();

    $this->travel(6)->minutes();

    $url = signedLoginUrl('admin@example.com', 5);

    // Travel happened after URL generation, so the URL is expired
    $this->travel(-6)->minutes();
    $url = signedLoginUrl('admin@example.com', 5);
    $this->travel(6)->minutes();

    $this->get($url)->assertStatus(401);
});

it('rejects when environment is not allowed', function () {
    createTestUser();
    config(['statamic.login-link.allowed_environments' => ['production']]);

    $this->get(signedLoginUrl())->assertStatus(403);
});

it('rejects when host is not allowed', function () {
    createTestUser();
    config(['statamic.login-link.allowed_hosts' => ['evil.com']]);

    $this->get(signedLoginUrl())->assertStatus(403);
});

it('denies a valid link unless the peer is directly local', function (array $server) {
    createTestUser();

    $this->withServerVariables($server)->get(signedLoginUrl())->assertForbidden();
    $this->assertGuest();
})->with([
    'nonlocal peer' => [['REMOTE_ADDR' => '192.0.2.10']],
    'unknown peer' => [['REMOTE_ADDR' => '']],
    'forwarded request' => [['REMOTE_ADDR' => '127.0.0.1', 'HTTP_FORWARDED' => 'for=192.0.2.10']],
    'tunneled request' => [['REMOTE_ADDR' => '::1', 'HTTP_X_FORWARDED_HOST' => 'localhost']],
]);

it('rejects unconfigured users', function () {
    User::make()->email('hacker@example.com')->makeSuper()->save();

    $url = URL::temporarySignedRoute(
        'statamic.cp.login-link',
        now()->addMinutes(5),
        ['email' => 'hacker@example.com'],
    );

    $this->get($url)->assertStatus(403);
});

it('rejects non-existent users', function () {
    config(['statamic.login-link.users' => ['nonexistent@example.com' => 'Ghost']]);

    $url = signedLoginUrl('nonexistent@example.com');

    $this->get($url)->assertStatus(404);
});

it('redirects to cp index by default', function () {
    createTestUser();

    $this->get(signedLoginUrl())
        ->assertRedirect(cp_route('index'));
});

it('redirects to custom url when configured', function () {
    createTestUser();
    config(['statamic.login-link.redirect_url' => '/cp/collections']);

    $this->get(signedLoginUrl())
        ->assertRedirect('/cp/collections');
});

it('regenerates the session after login', function () {
    createTestUser();

    $sessionId = session()->getId();

    $this->get(signedLoginUrl());

    expect(session()->getId())->not->toBe($sessionId);
});
