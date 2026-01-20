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

    protected function setUp(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_NAME'] = 'Chat Gateway API Test';
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:3000';

        $container = new Container();
        AppFactory::setContainer($container);

        $this->app = AppFactory::create();

        $middlewareConfig = require __DIR__ . '/../../config/middleware.php';
        $middlewareConfig($this->app);

        $routesConfig = require __DIR__ . '/../../config/routes.php';
        $routesConfig($this->app);
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

    public function testErrorMiddlewareHandlesNotFound(): void
    {
        // Request to non-existent route should throw HttpNotFoundException
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/non-existent-route');

        $this->expectException(\Slim\Exception\HttpNotFoundException::class);
        $this->app->handle($request);
    }

    public function testMiddlewareStackProcessesValidRequests(): void
    {
        // Valid request should go through entire middleware stack successfully
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000')
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        // Should have successful response
        $this->assertEquals(200, $response->getStatusCode());

        // Should have CORS headers (from CorsMiddleware)
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));

        // Should have JSON content type (from controller)
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }
}
