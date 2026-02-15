<?php

use Illuminate\Http\Request;
use Noo\StatamicLoginLink\Support\SecurityCheck;

beforeEach(function () {
    $this->securityCheck = new SecurityCheck;
});

it('passes when all checks pass', function () {
    config(['statamic.login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic.login-link.allowed_environments' => ['testing']]);
    config(['statamic.login-link.allowed_hosts' => ['localhost']]);

    $request = Request::create('http://localhost/cp/login');

    expect($this->securityCheck->passes($request))->toBeTrue();
});

it('fails when no users are configured', function () {
    config(['statamic.login-link.users' => []]);
    config(['statamic.login-link.allowed_environments' => ['testing']]);
    config(['statamic.login-link.allowed_hosts' => ['localhost']]);

    $request = Request::create('http://localhost/cp/login');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('fails when environment is not allowed', function () {
    config(['statamic.login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic.login-link.allowed_environments' => ['local']]);
    config(['statamic.login-link.allowed_hosts' => ['localhost']]);

    $request = Request::create('http://localhost/cp/login');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('fails when host is not allowed', function () {
    config(['statamic.login-link.users' => ['admin@example.com' => 'Admin']]);
    config(['statamic.login-link.allowed_environments' => ['testing']]);
    config(['statamic.login-link.allowed_hosts' => ['localhost']]);

    $request = Request::create('http://evil.com/cp/login');

    expect($this->securityCheck->passes($request))->toBeFalse();
});

it('checks configured users', function () {
    config(['statamic.login-link.users' => ['admin@example.com' => 'Admin']]);

    expect($this->securityCheck->hasConfiguredUsers())->toBeTrue();

    config(['statamic.login-link.users' => []]);

    expect($this->securityCheck->hasConfiguredUsers())->toBeFalse();
});

it('checks allowed environment', function () {
    config(['statamic.login-link.allowed_environments' => ['testing']]);

    expect($this->securityCheck->isAllowedEnvironment())->toBeTrue();

    config(['statamic.login-link.allowed_environments' => ['production']]);

    expect($this->securityCheck->isAllowedEnvironment())->toBeFalse();
});

it('checks allowed host', function () {
    config(['statamic.login-link.allowed_hosts' => ['localhost', '*.test']]);

    $request = Request::create('http://localhost/cp/login');
    expect($this->securityCheck->isAllowedHost($request))->toBeTrue();

    $request = Request::create('http://myapp.test/cp/login');
    expect($this->securityCheck->isAllowedHost($request))->toBeTrue();

    $request = Request::create('http://evil.com/cp/login');
    expect($this->securityCheck->isAllowedHost($request))->toBeFalse();
});
