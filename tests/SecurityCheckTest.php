<?php

use Illuminate\Http\Request;
use Noo\StatamicLoginLink\Support\SecurityCheck;

beforeEach(function () {
    $this->securityCheck = new SecurityCheck;
});

it('passes when all checks pass', function () {
    config(['statamic-login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic-login-link.allowed_environments' => ['testing']]);
    config(['statamic-login-link.allowed_ips' => ['127.0.0.1']]);

    $request = Request::create('/cp/login');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    expect($this->securityCheck->passes($request))->toBeTrue();
});

it('fails when no users are configured', function () {
    config(['statamic-login-link.users' => []]);
    config(['statamic-login-link.allowed_environments' => ['testing']]);
    config(['statamic-login-link.allowed_ips' => ['127.0.0.1']]);

    $request = Request::create('/cp/login');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('fails when environment is not allowed', function () {
    config(['statamic-login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic-login-link.allowed_environments' => ['local']]);
    config(['statamic-login-link.allowed_ips' => ['127.0.0.1']]);

    $request = Request::create('/cp/login');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('fails when ip is not allowed', function () {
    config(['statamic-login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic-login-link.allowed_environments' => ['testing']]);
    config(['statamic-login-link.allowed_ips' => ['127.0.0.1']]);

    $request = Request::create('/cp/login');
    $request->server->set('REMOTE_ADDR', '192.168.1.100');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('checks configured users', function () {
    config(['statamic-login-link.users' => ['admin@example.com' => 'Admin']]);

    expect($this->securityCheck->hasConfiguredUsers())->toBeTrue();

    config(['statamic-login-link.users' => []]);

    expect($this->securityCheck->hasConfiguredUsers())->toBeFalse();
});

it('checks allowed environment', function () {
    config(['statamic-login-link.allowed_environments' => ['testing']]);

    expect($this->securityCheck->isAllowedEnvironment())->toBeTrue();

    config(['statamic-login-link.allowed_environments' => ['production']]);

    expect($this->securityCheck->isAllowedEnvironment())->toBeFalse();
});

it('checks allowed ip', function () {
    config(['statamic-login-link.allowed_ips' => ['127.0.0.1', '::1']]);

    $request = Request::create('/cp/login');

    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    expect($this->securityCheck->isAllowedIp($request))->toBeTrue();

    $request->server->set('REMOTE_ADDR', '::1');
    expect($this->securityCheck->isAllowedIp($request))->toBeTrue();

    $request->server->set('REMOTE_ADDR', '10.0.0.1');
    expect($this->securityCheck->isAllowedIp($request))->toBeFalse();
});
