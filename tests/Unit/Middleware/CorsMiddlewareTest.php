<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\CorsMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CorsMiddleware();
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:3000,http://localhost:8080';
    }

    public function testCorsHeadersAreAddedForAllowedOrigin(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $this->assertEquals('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    public function testCorsHeadersAreAddedForWildcard(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = '*';

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://example.com');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testCorsHeadersNotAddedForDisallowedOrigin(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://evil.com');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testAllowedMethodsIncludesCommonHttpMethods(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $methods = $response->getHeaderLine('Access-Control-Allow-Methods');

        $this->assertStringContainsString('GET', $methods);
        $this->assertStringContainsString('POST', $methods);
        $this->assertStringContainsString('PUT', $methods);
        $this->assertStringContainsString('DELETE', $methods);
    }

    public function testAllowedHeadersIncludesAuthorizationHeader(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/health')
            ->withHeader('Origin', 'http://localhost:3000');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $headers = $response->getHeaderLine('Access-Control-Allow-Headers');

        $this->assertStringContainsString('Authorization', $headers);
        $this->assertStringContainsString('Content-Type', $headers);
    }

    private function createMockHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse();
            }
        };
    }
}
