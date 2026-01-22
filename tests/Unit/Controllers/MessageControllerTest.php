<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Controllers\MessageController;
use App\Services\MessageService;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class MessageControllerTest extends TestCase
{
    private MessageController $controller;
    private MessageService&MockObject $messageService;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->messageService = $this->createMock(MessageService::class);
        $this->controller = new MessageController($this->messageService);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    // ==================== SEND METHOD TESTS ====================

    public function testSendWithValidContentReturns201(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => 'Hello, world!'])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();
        $expectedResult = ['id' => 'msg-123', 'content' => 'Hello, world!', 'group_id' => 'group-123'];

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', 'Hello, world!')
            ->willReturn($expectedResult);

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertEquals($expectedResult, $data);
    }

    public function testSendWithoutUserUuidReturns401(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => 'Hello, world!']);

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(401, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testSendWithEmptyContentReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => ''])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', '')
            ->willThrowException(new \RuntimeException('Message content is required', 400));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Message content is required', $data['error']);
    }

    public function testSendWithOnlyWhitespaceContentReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => '   '])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', '')
            ->willThrowException(new \RuntimeException('Message content is required', 400));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Message content is required', $data['error']);
    }

    public function testSendWithContentExceeding5000CharactersReturns400(): void
    {
        $longContent = str_repeat('a', 5001);
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => $longContent])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', $longContent)
            ->willThrowException(new \RuntimeException('Message content must not exceed 5000 characters', 400));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertStringContainsString('5000 characters', $data['error']);
    }

    public function testSendWithContentAt5000CharactersSucceeds(): void
    {
        $contentAt5000 = str_repeat('a', 5000);
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => $contentAt5000])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', $contentAt5000)
            ->willReturn(['id' => 'msg-123']);

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testSendWithoutContentFieldReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody([])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', '')
            ->willThrowException(new \RuntimeException('Message content is required', 400));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Message content is required', $data['error']);
    }

    public function testSendTrimsWhitespaceFromContent(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => '  Hello, world!  '])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', 'Hello, world!')
            ->willReturn(['id' => 'msg-123']);

        $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testSendWithoutParsedBodyReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with('group-123', 'user-123', '')
            ->willThrowException(new \RuntimeException('Message content is required', 400));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Message content is required', $data['error']);
    }

    public function testSendWithGroupNotFoundReturnsError(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/nonexistent/messages')
            ->withParsedBody(['content' => 'Hello'])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \RuntimeException('Group not found', 404));

        $result = $this->controller->send($request, $response, ['groupId' => 'nonexistent']);

        $this->assertEquals(404, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Group not found', $data['error']);
    }

    public function testSendWithNotMemberReturnsError(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => 'Hello'])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \RuntimeException('You must join the group before sending messages', 403));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(403, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertStringContainsString('join the group', $data['error']);
    }

    public function testSendWithRuntimeExceptionWithoutCodeUses500(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => 'Hello'])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \RuntimeException('Internal error'));

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(500, $result->getStatusCode());
    }

    // ==================== LIST MESSAGES METHOD TESTS ====================

    public function testListMessagesWithValidParametersReturns200(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages?page=1&page_size=20')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();
        $expectedResult = [
            'data' => [
                ['id' => 'msg-1', 'content' => 'First message'],
                ['id' => 'msg-2', 'content' => 'Second message'],
            ],
            'pagination' => [
                'page' => 1,
                'page_size' => 20,
                'total' => 2,
            ],
        ];

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 1, 20)
            ->willReturn($expectedResult);

        $result = $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals($expectedResult, $data);
    }

    public function testListMessagesWithoutUserUuidReturns401(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(401, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testListMessagesWithDefaultPageParameters(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 1, 20)
            ->willReturn(['data' => [], 'pagination' => ['page' => 1, 'page_size' => 20, 'total' => 0]]);

        $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testListMessagesWithCustomPageSize(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages?page_size=50')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 1, 50)
            ->willReturn(['data' => [], 'pagination' => ['page' => 1, 'page_size' => 50, 'total' => 0]]);

        $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testListMessagesWithCustomPage(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages?page=5&page_size=10')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 5, 10)
            ->willReturn(['data' => [], 'pagination' => ['page' => 5, 'page_size' => 10, 'total' => 45]]);

        $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testListMessagesWithZeroPageNormalizedToOne(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages?page=0')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 1, 20)
            ->willReturn(['data' => [], 'pagination' => ['page' => 1, 'page_size' => 20, 'total' => 0]]);

        $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testListMessagesWithNegativePageNormalizedToOne(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages?page=-5')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->with('group-123', 'user-123', 1, 20)
            ->willReturn(['data' => [], 'pagination' => ['page' => 1, 'page_size' => 20, 'total' => 0]]);

        $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testListMessagesWithGroupNotFoundReturnsError(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/nonexistent/messages')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->willThrowException(new \RuntimeException('Group not found', 404));

        $result = $this->controller->listMessages($request, $response, ['groupId' => 'nonexistent']);

        $this->assertEquals(404, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Group not found', $data['error']);
    }

    public function testListMessagesWithNotMemberReturnsError(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->willThrowException(new \RuntimeException('You must join the group to view messages', 403));

        $result = $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(403, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertStringContainsString('join the group', $data['error']);
    }

    public function testListMessagesWithRuntimeExceptionWithoutCodeUses500(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/groups/group-123/messages')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->messageService->expects($this->once())
            ->method('listMessages')
            ->willThrowException(new \RuntimeException('Internal error'));

        $result = $this->controller->listMessages($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(500, $result->getStatusCode());
    }

    // ==================== RESPONSE FORMAT TESTS ====================

    public function testAllResponsesHaveJsonContentType(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => ''])
            ->withAttribute('user_uuid', 'user-123');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
    }

    public function testResponseBodyIsValidJson(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/messages')
            ->withParsedBody(['content' => ''])
            ->withAttribute('user_uuid', 'user-123');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->send($request, $response, ['groupId' => 'group-123']);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
    }
}
