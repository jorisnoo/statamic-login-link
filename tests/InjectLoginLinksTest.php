<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Noo\StatamicLoginLink\Http\Middleware\InjectLoginLinks;
use Noo\StatamicLoginLink\Support\SecurityCheck;

beforeEach(function () {
    config([
        'statamic-login-link.users' => ['admin@example.com' => 'Admin'],
        'statamic-login-link.allowed_environments' => ['testing'],
        'statamic-login-link.allowed_ips' => ['127.0.0.1'],
        'statamic-login-link.link_expiration_minutes' => 5,
    ]);
});

function createLoginRequest(string $ip = '127.0.0.1'): Request
{
    $request = Request::create('/cp/login', 'GET');
    $request->server->set('REMOTE_ADDR', $ip);
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/cp/login', []);
        $route->name('statamic.cp.login');

        return $route;
    });

    return $request;
}

function runMiddleware(Request $request, ?Response $response = null): Response
{
    $response ??= new Response('<html><body><p>Login</p></body></html>', 200, ['Content-Type' => 'text/html']);

    $middleware = app(InjectLoginLinks::class);

    return $middleware->handle($request, fn () => $response);
}

it('injects login links on the login page', function () {
    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())
        ->toContain('statamic-login-links')
        ->toContain('Admin')
        ->toContain('admin@example.com')
        ->toContain('Developer Login');
});

it('does not inject on non-login routes', function () {
    $request = Request::create('/cp/dashboard', 'GET');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/cp/dashboard', []);
        $route->name('statamic.cp.dashboard');

        return $route;
    });

    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject on POST requests', function () {
    $request = Request::create('/cp/login', 'POST');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('POST', '/cp/login', []);
        $route->name('statamic.cp.login');

        return $route;
    });

    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when security check fails', function () {
    config(['statamic-login-link.allowed_environments' => ['production']]);

    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when no users are configured', function () {
    config(['statamic-login-link.users' => []]);

    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when ip is not allowed', function () {
    $request = createLoginRequest('192.168.1.100');
    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject on non-html responses', function () {
    $request = createLoginRequest();
    $response = new Response('{"status": "ok"}', 200, ['Content-Type' => 'application/json']);

    $result = runMiddleware($request, $response);

    expect($result->getContent())->not->toContain('statamic-login-links');
});

it('renders multiple user buttons', function () {
    config(['statamic-login-link.users' => [
        'admin@example.com' => 'Admin',
        'editor@example.com' => 'Editor',
    ]]);

    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())
        ->toContain('Admin')
        ->toContain('admin@example.com')
        ->toContain('Editor')
        ->toContain('editor@example.com');
});

it('generates signed urls for each button', function () {
    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())
        ->toContain('auth/login-link/')
        ->toContain('signature=');
});
