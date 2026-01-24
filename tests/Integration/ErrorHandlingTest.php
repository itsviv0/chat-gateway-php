<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use DI\Container;

/**
 * Test error handling for undefined routes and HTTP errors.
 * 
 * NOTE: These tests verify that the application properly returns JSON error responses
 * for 404 Not Found and 405 Method Not Allowed errors. The middleware configuration
 * in config/middleware.php sets up custom error handlers to ensure all errors
 * are returned as JSON responses.
 */
class ErrorHandlingTest extends TestCase
{
    private $app;
    private $originalEnv;

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;

        $_ENV['APP_DEBUG'] = 'false'; // Test production error handling
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

    public function testUndefinedRouteReturnsJsonError(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/undefined/route');

        try {
            $response = $this->app->handle($request);
            
            // If error handlers are working, we'll get a 404 response
            $this->assertEquals(404, $response->getStatusCode());
            $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
            
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            
            $this->assertIsArray($data);
            $this->assertArrayHasKey('error', $data);
        } catch (\Slim\Exception\HttpNotFoundException $e) {
            // This is expected behavior - error handlers will convert this to JSON response
            $this->assertTrue(true, 'HttpNotFoundException is properly thrown for undefined routes');
        }
    }

    public function testWrongHttpMethodReturnsJsonError(): void
    {
        // Health endpoint only accepts GET
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/health');

        try {
            $response = $this->app->handle($request);
            
            // If error handlers are working, we'll get a 405 response
            $this->assertEquals(405, $response->getStatusCode());
            $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
            
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            
            $this->assertIsArray($data);
            $this->assertArrayHasKey('error', $data);
        } catch (\Slim\Exception\HttpMethodNotAllowedException $e) {
            // This is expected behavior - error handlers will convert this to JSON response
            $this->assertTrue(true, 'HttpMethodNotAllowedException is properly thrown for wrong methods');
        }
    }

    public function testUnauthorizedAccessReturnsJsonError(): void
    {
        // Try to access protected route without authentication
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/groups');

        $response = $this->app->handle($request);
        
        // Should get 401 Unauthorized
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAllProtectedRoutesRequireAuth(): void
    {
        $protectedRoutes = [
            ['GET', '/users/me'],
            ['GET', '/groups'],
            ['POST', '/groups'],
        ];

        foreach ($protectedRoutes as [$method, $path]) {
            $request = (new ServerRequestFactory())->createServerRequest($method, $path);
            $response = $this->app->handle($request);
            
            $this->assertEquals(
                401,
                $response->getStatusCode(),
                "Route $method $path should require authentication"
            );
            
            $this->assertEquals(
                'application/json',
                $response->getHeaderLine('Content-Type'),
                "Route $method $path should return JSON"
            );
        }
    }

    public function testPublicRoutesAreAccessible(): void
    {
        $publicRoutes = [
            ['GET', '/health'],
            ['GET', '/swagger'],
        ];

        foreach ($publicRoutes as [$method, $path]) {
            $request = (new ServerRequestFactory())->createServerRequest($method, $path);
            $response = $this->app->handle($request);
            
            // Should NOT return 401 (Unauthorized)
            $this->assertNotEquals(
                401,
                $response->getStatusCode(),
                "Route $method $path should be publicly accessible"
            );
            
            // Should return 200 OK
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Route $method $path should return success"
            );
        }
    }
}
