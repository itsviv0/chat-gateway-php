<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Controllers\GroupController;
use App\Services\GroupService;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use PDOException;

class GroupControllerTest extends TestCase
{
    private GroupController $controller;
    private GroupService&MockObject $groupService;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->groupService = $this->createMock(GroupService::class);
        $this->controller = new GroupController($this->groupService);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    // ==================== CREATE METHOD TESTS ====================

    public function testCreateWithValidDataReturns201(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => 'Test Group',
                'description' => 'A test group',
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();
        $expectedResult = ['uuid' => 'group-123', 'name' => 'Test Group'];

        $this->groupService->expects($this->once())
            ->method('createGroup')
            ->with('Test Group', 'A test group', false, 'user-123')
            ->willReturn($expectedResult);

        $result = $this->controller->create($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
        
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertEquals($expectedResult, $data);
    }

    public function testCreateWithEmptyNameReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => '',
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('required', strtolower($data['error']));
    }

    public function testCreateWithNameExceedingLimitReturns400(): void
    {
        $longName = str_repeat('a', 101);
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => $longName,
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('100 characters', $data['error']);
    }

    public function testCreateWithoutUserUuidReturns401(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => 'Test Group',
                'is_private' => false,
            ]);

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($request, $response);

        $this->assertEquals(401, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testCreateWithDatabaseExceptionReturns500(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => 'Test Group',
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createGroup')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->controller->create($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Failed to create group', $data['error']);
    }

    public function testCreateTrimsWhitespaceFromName(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => '  Test Group  ',
                'description' => null,
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createGroup')
            ->with('Test Group', null, false, 'user-123')
            ->willReturn(['uuid' => 'group-123']);

        $this->controller->create($request, $response);

        // Verification is implicit in the mock expectations
        $this->assertTrue(true);
    }

    public function testCreateWithPrivateGroupFlag(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => 'Private Group',
                'is_private' => true,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createGroup')
            ->with('Private Group', null, true, 'user-123')
            ->willReturn(['uuid' => 'group-123']);

        $this->controller->create($request, $response);

        $this->assertTrue(true);
    }

    public function testCreateWithNullDescription(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody([
                'name' => 'Test Group',
                'is_private' => false,
            ])
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createGroup')
            ->with('Test Group', null, false, 'user-123')
            ->willReturn(['uuid' => 'group-123']);

        $this->controller->create($request, $response);

        $this->assertTrue(true);
    }

    public function testCreateWithoutParsedBodyUsesDefaults(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withAttribute('user_uuid', 'user-123');

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
    }

    // ==================== JOIN METHOD TESTS ====================

    public function testJoinWithValidDataReturns200(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join')
            ->withParsedBody(['invite_token' => 'token-123'])
            ->withAttribute('user_uuid', 'user-456');

        $response = $this->responseFactory->createResponse();
        $expectedResult = ['membership_id' => 'mem-123', 'status' => 'active'];

        $this->groupService->expects($this->once())
            ->method('joinGroup')
            ->with('group-123', 'user-456', 'token-123')
            ->willReturn($expectedResult);

        $result = $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals($expectedResult, $data);
    }

    public function testJoinWithoutUserUuidReturns401(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(401, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testJoinWithoutInviteToken(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join')
            ->withAttribute('user_uuid', 'user-456');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('joinGroup')
            ->with('group-123', 'user-456', null)
            ->willReturn(['membership_id' => 'mem-123']);

        $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testJoinWithRuntimeExceptionReturnsErrorStatus(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join')
            ->withAttribute('user_uuid', 'user-456');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('joinGroup')
            ->willThrowException(new \RuntimeException('Group not found', 404));

        $result = $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(404, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Group not found', $data['error']);
    }

    public function testJoinWithRuntimeExceptionWithoutCodeUses500(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join')
            ->withAttribute('user_uuid', 'user-456');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('joinGroup')
            ->willThrowException(new \RuntimeException('Something went wrong'));

        $result = $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(500, $result->getStatusCode());
    }

    public function testJoinTrimsInviteToken(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/join')
            ->withParsedBody(['invite_token' => '  token-123  '])
            ->withAttribute('user_uuid', 'user-456');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('joinGroup')
            ->with('group-123', 'user-456', 'token-123')
            ->willReturn(['membership_id' => 'mem-123']);

        $this->controller->join($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    // ==================== INVITE METHOD TESTS ====================

    public function testInviteWithValidDataReturns201(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody([
                'email' => 'user@example.com',
                'expires_in_hours' => 72,
            ])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();
        $expectedResult = ['invitation_token' => 'inv-token-123', 'expires_at' => '2026-01-24'];

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->with('group-123', 'user-789', 'user@example.com', 72)
            ->willReturn($expectedResult);

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(201, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals($expectedResult, $data);
    }

    public function testInviteWithoutUserUuidReturns401(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody(['email' => 'user@example.com']);

        $response = $this->responseFactory->createResponse();

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(401, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testInviteWithoutEmailReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody([])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->with('group-123', 'user-789', '', 168)
            ->willThrowException(new \RuntimeException('Email is required to issue an invitation', 400));

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Email is required', $data['error']);
    }

    public function testInviteWithEmptyEmailReturns400(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody(['email' => '   '])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->with('group-123', 'user-789', '', 168)
            ->willThrowException(new \RuntimeException('Email is required to issue an invitation', 400));

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(400, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('Email is required to issue an invitation', $data['error']);
    }

    public function testInviteWithDefaultExpiresInHours(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody(['email' => 'user@example.com'])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->with('group-123', 'user-789', 'user@example.com', 168)
            ->willReturn(['invitation_token' => 'inv-token-123']);

        $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testInviteTrimsEmail(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody([
                'email' => '  user@example.com  ',
                'expires_in_hours' => 48,
            ])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->with('group-123', 'user-789', 'user@example.com', 48)
            ->willReturn(['invitation_token' => 'inv-token-123']);

        $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertTrue(true);
    }

    public function testInviteWithRuntimeExceptionReturnsErrorStatus(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody(['email' => 'user@example.com'])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->willThrowException(new \RuntimeException('User already in group', 409));

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(409, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('User already in group', $data['error']);
    }

    public function testInviteWithRuntimeExceptionWithoutCodeUses500(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups/group-123/invite')
            ->withParsedBody(['email' => 'user@example.com'])
            ->withAttribute('user_uuid', 'user-789');

        $response = $this->responseFactory->createResponse();

        $this->groupService->expects($this->once())
            ->method('createInvitation')
            ->willThrowException(new \RuntimeException('Internal error'));

        $result = $this->controller->invite($request, $response, ['groupId' => 'group-123']);

        $this->assertEquals(500, $result->getStatusCode());
    }

    // ==================== RESPONSE FORMAT TESTS ====================

    public function testAllResponsesHaveJsonContentType(): void
    {
        $createRequest = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody(['name' => ''])
            ->withAttribute('user_uuid', 'user-123');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($createRequest, $response);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
    }

    public function testResponseBodyIsValidJson(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/groups')
            ->withParsedBody(['name' => ''])
            ->withAttribute('user_uuid', 'user-123');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->create($request, $response);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
    }
}
