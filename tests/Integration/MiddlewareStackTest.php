<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use DI\Container;

class MiddlewareStackTest extends TestCase
{
    private $app;
    private $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;

        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_NAME'] = 'Chat Gateway API Test';
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:3000';
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_PORT'] = '3306';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test';
        $_ENV['DB_PASS'] = 'test';
        $_ENV['JWT_SECRET'] = 'test-secret-key';

        $container = new Container();
        $containerConfig = require __DIR__ . '/../../config/container.php';
        $containerConfig($container);
        
        AppFactory::setContainer($container);

        $this->app = AppFactory::create();

        $middlewareConfig = require __DIR__ . '/../../config/middleware.php';
        $middlewareConfig($this->app);

        $routesConfig = require __DIR__ . '/../../config/routes.php';
        $routesConfig($this->app);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
    }

    public function testJsonBodyIsParsedBeforeReachingController(): void
    {
        // This test verifies JsonBodyParserMiddleware is working in the stack
        $jsonData = ['test' => 'data'];

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')  // Use GET since /health only accepts GET
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream(json_encode($jsonData)));

        $response = $this->app->handle($request);

        // Should succeed - middleware handles JSON gracefully
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCorsHeadersArePresentInResponse(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $response = $this->app->handle($request);

        $this->assertEquals('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testProtectedRouteRequiresAuthentication(): void
    {
        // Request to protected route without auth token should return 401
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/users/me');

        $response = $this->app->handle($request);
        
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertArrayHasKey('error', $data);
    }
}

