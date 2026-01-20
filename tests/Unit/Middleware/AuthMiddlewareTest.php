<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new AuthMiddleware();
    }

    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/groups');
        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testMissingAuthorizationHeaderReturnsJsonError(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/groups');
        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Missing authorization header', $data['error']);
    }

    public function testInvalidAuthorizationFormatReturns401(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'InvalidFormat token123');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testInvalidAuthorizationFormatReturnsJsonError(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'InvalidFormat token123');

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid authorization format', $data['error']);
    }

    public function testValidBearerTokenIsAccepted(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'Bearer valid-token-123');

        $handlerCalled = false;
        $handler = $this->createAssertingHandler(function () use (&$handlerCalled) {
            $handlerCalled = true;
        });

        $this->middleware->process($request, $handler);

        $this->assertTrue($handlerCalled, 'Handler should be called for valid token');
    }

    public function testTokenIsAddedToRequestAttributes(): void
    {
        $token = 'valid-token-123';
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', "Bearer {$token}");

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) use ($token) {
            $this->assertEquals($token, $request->getAttribute('token'));
        });

        $this->middleware->process($request, $handler);
    }

    public function testBearerKeywordIsCaseInsensitive(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'bearer lowercase-token');

        $handlerCalled = false;
        $handler = $this->createAssertingHandler(function () use (&$handlerCalled) {
            $handlerCalled = true;
        });

        $this->middleware->process($request, $handler);

        $this->assertTrue($handlerCalled);
    }

    private function createMockHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse();
            }
        };
    }

    private function createAssertingHandler(callable $assertion): RequestHandlerInterface
    {
        return new class ($assertion) implements RequestHandlerInterface {
            private $assertion;

            public function __construct(callable $assertion)
            {
                $this->assertion = $assertion;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->assertion)($request);
                return (new ResponseFactory())->createResponse();
            }
        };
    }
}
