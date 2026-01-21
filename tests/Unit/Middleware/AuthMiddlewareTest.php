<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthMiddleware;
use Firebase\JWT\JWT;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;
    private string $jwtSecret = 'test-secret-key';

    protected function setUp(): void
    {
        $_ENV['JWT_SECRET'] = $this->jwtSecret;
        $this->middleware = new AuthMiddleware();
    }

    private function generateTestToken(string $userUuid = 'test-user-123', string $username = 'alice'): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $userUuid,
            'username' => $username,
        ];
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
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
        $token = $this->generateTestToken();
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handlerCalled = false;
        $handler = $this->createAssertingHandler(function () use (&$handlerCalled) {
            $handlerCalled = true;
        });

        $this->middleware->process($request, $handler);

        $this->assertTrue($handlerCalled, 'Handler should be called for valid token');
    }

    public function testBearerKeywordIsCaseInsensitive(): void
    {
        $token = $this->generateTestToken();
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'bearer ' . $token);

        $handlerCalled = false;
        $handler = $this->createAssertingHandler(function () use (&$handlerCalled) {
            $handlerCalled = true;
        });

        $this->middleware->process($request, $handler);

        $this->assertTrue($handlerCalled);
    }

    public function testTokenIsAddedToRequestAttributes(): void
    {
        $userUuid = 'test-user-uuid-456';
        $username = 'bob';
        $token = $this->generateTestToken($userUuid, $username);
        
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $capturedRequest = null;
        $handler = $this->createAssertingHandler(function (ServerRequestInterface $req) use (&$capturedRequest) {
            $capturedRequest = $req;
        });

        $this->middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $this->assertEquals($userUuid, $capturedRequest->getAttribute('user_uuid'));
        $this->assertEquals($username, $capturedRequest->getAttribute('username'));
    }

    public function testExpiredTokenReturns401(): void
    {
        $payload = [
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'sub' => 'test-user',
            'username' => 'alice',
        ];
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/groups')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = $this->createMockHandler();
        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
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
