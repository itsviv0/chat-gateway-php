<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use DI\Container;

class HealthEndpointTest extends TestCase
{
    private $app;
    private $originalEnv;

    protected function setUp(): void
    {
        // Save original environment
        $this->originalEnv = $_ENV;

        // Load environment for testing
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_NAME'] = 'Chat Gateway API Test';
        $_ENV['CORS_ALLOWED_ORIGINS'] = '*';

        // Create container
        $container = new Container();
        AppFactory::setContainer($container);

        // Create app
        $this->app = AppFactory::create();

        // Load middleware
        $middlewareConfig = require __DIR__ . '/../../config/middleware.php';
        $middlewareConfig($this->app);

        // Load routes
        $routesConfig = require __DIR__ . '/../../config/routes.php';
        $routesConfig($this->app);
    }

    protected function tearDown(): void
    {
        // Restore original environment
        $_ENV = $this->originalEnv;
    }

    public function testHealthEndpointReturns200(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHealthEndpointReturnsJson(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = $this->app->handle($request);

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHealthEndpointResponseStructure(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = $this->app->handle($request);

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('service', $data);
        $this->assertEquals('healthy', $data['status']);
    }

    public function testHealthEndpointDoesNotRequireAuthentication(): void
    {
        // Request without Authorization header should succeed
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHealthEndpointHasCorsHeaders(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $response = $this->app->handle($request);

        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
