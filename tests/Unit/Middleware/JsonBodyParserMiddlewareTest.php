<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\JsonBodyParserMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonBodyParserMiddlewareTest extends TestCase
{
    private JsonBodyParserMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new JsonBodyParserMiddleware();
    }

    public function testValidJsonIsParsedCorrectly(): void
    {
        $jsonData = ['username' => 'testuser', 'email' => 'test@example.com'];
        $request = $this->createRequestWithJson(json_encode($jsonData));

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) use ($jsonData) {
            $this->assertEquals($jsonData, $request->getParsedBody());
        });

        $this->middleware->process($request, $handler);
    }

    public function testInvalidJsonIsIgnored(): void
    {
        $request = $this->createRequestWithJson('{"invalid": json}');

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) {
            $this->assertNull($request->getParsedBody());
        });

        $this->middleware->process($request, $handler);
    }

    public function testEmptyBodyIsHandled(): void
    {
        $request = $this->createRequestWithJson('');

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) {
            $this->assertNull($request->getParsedBody());
        });

        $this->middleware->process($request, $handler);
    }

    public function testNonJsonContentTypeIsIgnored(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/test')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody((new StreamFactory())->createStream('plain text'));

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) {
            $this->assertNull($request->getParsedBody());
        });

        $this->middleware->process($request, $handler);
    }

    public function testNestedJsonIsParsedCorrectly(): void
    {
        $jsonData = [
            'user' => [
                'name' => 'John',
                'settings' => ['theme' => 'dark']
            ]
        ];
        $request = $this->createRequestWithJson(json_encode($jsonData));

        $handler = $this->createAssertingHandler(function (ServerRequestInterface $request) use ($jsonData) {
            $this->assertEquals($jsonData, $request->getParsedBody());
        });

        $this->middleware->process($request, $handler);
    }

    private function createRequestWithJson(string $json): ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest('POST', '/test')
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream($json));
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
