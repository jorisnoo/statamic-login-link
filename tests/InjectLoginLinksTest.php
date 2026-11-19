<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Noo\StatamicLoginLink\Http\Middleware\InjectLoginLinks;
use Noo\StatamicLoginLink\Support\SecurityCheck;

beforeEach(function () {
    config([
        'statamic.login-link.users' => ['admin@example.com' => 'Admin'],
        'statamic.login-link.allowed_environments' => ['testing'],
        'statamic.login-link.allowed_hosts' => ['localhost'],
        'statamic.login-link.link_expiration_minutes' => 5,
    ]);
});

it('offers signed developer links on the real CP login page only to a direct local client', function () {
    $page = $this->get('/cp/auth/login')->assertOk();
    $page->assertSee('id="statamic-login-links"', false);
    expect($page->headers->get('Cache-Control'))->toContain('no-store');

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->get('/cp/auth/login')
        ->assertOk()
        ->assertDontSee('id="statamic-login-links"', false);
});

it('leaves the Inertia login response as JSON', function () {
    $this->withHeader('X-Inertia', 'true')->get('/cp/auth/login')
        ->assertOk()
        ->assertJsonStructure(['component', 'props'])
        ->assertDontSee('id="statamic-login-links"', false);
});

function createLoginRequest(): Request
{
    $request = Request::create('http://localhost/cp/login', 'GET');
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
    $request = Request::create('http://localhost/cp/dashboard', 'GET');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/cp/dashboard', []);
        $route->name('statamic.cp.dashboard');

        return $route;
    });

    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject on POST requests', function () {
    $request = Request::create('http://localhost/cp/login', 'POST');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('POST', '/cp/login', []);
        $route->name('statamic.cp.login');

        return $route;
    });

    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when security check fails', function () {
    config(['statamic.login-link.allowed_environments' => ['production']]);

    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when no users are configured', function () {
    config(['statamic.login-link.users' => []]);

    $request = createLoginRequest();
    $response = runMiddleware($request);

    expect($response->getContent())->not->toContain('statamic-login-links');
});

it('does not inject when host is not allowed', function () {
    $request = Request::create('http://evil.com/cp/login', 'GET');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/cp/login', []);
        $route->name('statamic.cp.login');

        return $route;
    });

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
    config(['statamic.login-link.users' => [
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
