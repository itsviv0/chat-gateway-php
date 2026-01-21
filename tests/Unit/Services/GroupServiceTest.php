<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\GroupService;
use App\Repositories\GroupRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\InvitationRepository;
use PDO;
use PDOException;

class GroupServiceTest extends TestCase
{
    private GroupService $service;
    private PDO&MockObject $pdo;
    private GroupRepository&MockObject $groupRepository;
    private MembershipRepository&MockObject $membershipRepository;
    private InvitationRepository&MockObject $invitationRepository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->groupRepository = $this->createMock(GroupRepository::class);
        $this->membershipRepository = $this->createMock(MembershipRepository::class);
        $this->invitationRepository = $this->createMock(InvitationRepository::class);

        $this->service = new GroupService(
            $this->pdo,
            $this->groupRepository,
            $this->membershipRepository,
            $this->invitationRepository
        );
    }

    // ==================== CREATE GROUP METHOD TESTS ====================

    public function testCreateGroupSuccessfully(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->with('Test Group', 'A test group', false, 'user-123', $this->isType('string'))
            ->willReturn('group-123');

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->with('group-123', 'user-123', 'admin', $this->isType('string'));

        $result = $this->service->createGroup('Test Group', 'A test group', false, 'user-123');

        $this->assertIsArray($result);
        $this->assertEquals('group-123', $result['id']);
        $this->assertEquals('Test Group', $result['name']);
        $this->assertEquals('A test group', $result['description']);
        $this->assertEquals(false, $result['is_private']);
        $this->assertEquals('user-123', $result['created_by']);
        $this->assertArrayHasKey('created_at', $result);
    }

    public function testCreatePrivateGroupSuccessfully(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->with('Private Group', null, true, 'user-456', $this->isType('string'))
            ->willReturn('group-456');

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->with('group-456', 'user-456', 'admin', $this->isType('string'));

        $result = $this->service->createGroup('Private Group', null, true, 'user-456');

        $this->assertEquals('group-456', $result['id']);
        $this->assertEquals('Private Group', $result['name']);
        $this->assertNull($result['description']);
        $this->assertEquals(true, $result['is_private']);
    }

    public function testCreateGroupWithNullDescription(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->with('No Description Group', null, false, 'user-789', $this->isType('string'))
            ->willReturn('group-789');

        $this->membershipRepository->expects($this->once())
            ->method('addMember');

        $result = $this->service->createGroup('No Description Group', null, false, 'user-789');

        $this->assertNull($result['description']);
    }

    public function testCreateGroupRollsBackTransactionOnRepositoryException(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');
        $this->pdo->expects($this->never())->method('commit');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->willThrowException(new PDOException('Database error'));

        $this->membershipRepository->expects($this->never())->method('addMember');

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Database error');

        $this->service->createGroup('Test Group', null, false, 'user-123');
    }

    public function testCreateGroupRollsBackTransactionOnAddMemberException(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');
        $this->pdo->expects($this->never())->method('commit');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->willReturn('group-123');

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->willThrowException(new PDOException('Failed to add member'));

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Failed to add member');

        $this->service->createGroup('Test Group', null, false, 'user-123');
    }

    public function testCreateGroupReturnsCurrentTimestamp(): void
    {
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $this->groupRepository->expects($this->once())
            ->method('create')
            ->willReturn('group-123');

        $this->membershipRepository->expects($this->once())
            ->method('addMember');

        $before = time();
        $result = $this->service->createGroup('Test Group', null, false, 'user-123');
        $after = time();

        $createdAt = strtotime($result['created_at']);
        $this->assertGreaterThanOrEqual($before, $createdAt);
        $this->assertLessThanOrEqual($after, $createdAt);
    }

    // ==================== JOIN GROUP METHOD TESTS ====================

    public function testJoinPublicGroupSuccessfully(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('group-123')
            ->willReturn(['uuid' => 'group-123', 'is_private' => false]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('group-123', 'user-456')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->with('group-123', 'user-456', 'member', $this->isType('string'));

        $this->invitationRepository->expects($this->never())->method('findPendingInvitation');

        $result = $this->service->joinGroup('group-123', 'user-456', null);

        $this->assertIsArray($result);
        $this->assertEquals('Joined group', $result['message']);
        $this->assertEquals('group-123', $result['group_id']);
        $this->assertEquals('user-456', $result['user_uuid']);
    }

    public function testJoinGroupWhenAlreadyMember(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('group-123')
            ->willReturn(['uuid' => 'group-123', 'is_private' => false]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('group-123', 'user-456')
            ->willReturn(['role' => 'member']);

        $this->pdo->expects($this->never())->method('beginTransaction');

        $result = $this->service->joinGroup('group-123', 'user-456', null);

        $this->assertEquals('Already joined', $result['message']);
        $this->assertEquals('group-123', $result['group_id']);
    }

    public function testJoinGroupNotFound(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Group not found');
        $this->expectExceptionCode(404);

        $this->service->joinGroup('nonexistent', 'user-123', null);
    }

    public function testJoinPrivateGroupWithoutTokenRejectsJoin(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('private-group')
            ->willReturn(['uuid' => 'private-group', 'is_private' => true]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('private-group', 'user-123')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invitation token required for private groups');
        $this->expectExceptionCode(403);

        $this->service->joinGroup('private-group', 'user-123', null);
    }

    public function testJoinPrivateGroupWithEmptyTokenRejectsJoin(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('private-group')
            ->willReturn(['uuid' => 'private-group', 'is_private' => true]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('private-group', 'user-123')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invitation token required for private groups');
        $this->expectExceptionCode(403);

        $this->service->joinGroup('private-group', 'user-123', '');
    }

    public function testJoinPrivateGroupWithInvalidTokenRejectsJoin(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('private-group')
            ->willReturn(['uuid' => 'private-group', 'is_private' => true]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('private-group', 'user-123')
            ->willReturn(null);

        $this->invitationRepository->expects($this->once())
            ->method('findPendingInvitation')
            ->with('private-group', 'invalid-token')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid invitation token');
        $this->expectExceptionCode(403);

        $this->service->joinGroup('private-group', 'user-123', 'invalid-token');
    }

    public function testJoinPrivateGroupWithExpiredTokenRejectsJoin(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('private-group')
            ->willReturn(['uuid' => 'private-group', 'is_private' => true]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('private-group', 'user-123')
            ->willReturn(null);

        $expiredDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->invitationRepository->expects($this->once())
            ->method('findPendingInvitation')
            ->with('private-group', 'expired-token')
            ->willReturn(['id' => 1, 'expires_at' => $expiredDate]);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invitation token has expired');
        $this->expectExceptionCode(403);

        $this->service->joinGroup('private-group', 'user-123', 'expired-token');
    }

    public function testJoinPrivateGroupWithValidTokenSucceeds(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('private-group')
            ->willReturn(['uuid' => 'private-group', 'is_private' => true]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('private-group', 'user-123')
            ->willReturn(null);

        $futureDate = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->invitationRepository->expects($this->once())
            ->method('findPendingInvitation')
            ->with('private-group', 'valid-token')
            ->willReturn(['id' => 1, 'expires_at' => $futureDate]);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $this->invitationRepository->expects($this->once())
            ->method('markAsAccepted')
            ->with(1);

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->with('private-group', 'user-123', 'member', $this->isType('string'));

        $result = $this->service->joinGroup('private-group', 'user-123', 'valid-token');

        $this->assertEquals('Joined group', $result['message']);
        $this->assertEquals('private-group', $result['group_id']);
    }

    public function testJoinGroupRollsBackTransactionOnAddMemberException(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('group-123')
            ->willReturn(['uuid' => 'group-123', 'is_private' => false]);

        $this->membershipRepository->expects($this->once())
            ->method('findMembership')
            ->with('group-123', 'user-456')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('rollBack');

        $this->membershipRepository->expects($this->once())
            ->method('addMember')
            ->willThrowException(new PDOException('Database error'));

        $this->expectException(PDOException::class);

        $this->service->joinGroup('group-123', 'user-456', null);
    }

    // ==================== CREATE INVITATION METHOD TESTS ====================

    public function testCreateInvitationSuccessfully(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('group-123')
            ->willReturn(['uuid' => 'group-123']);

        $this->membershipRepository->expects($this->once())
            ->method('isAdmin')
            ->with('group-123', 'user-123')
            ->willReturn(true);

        $this->invitationRepository->expects($this->once())
            ->method('create')
            ->with(
                'group-123',
                'user-123',
                'user@example.com',
                $this->isType('string'),
                $this->isType('string'),
                $this->isType('string')
            );

        $result = $this->service->createInvitation('group-123', 'user-123', 'user@example.com', 168);

        $this->assertIsArray($result);
        $this->assertEquals('group-123', $result['group_id']);
        $this->assertEquals('user@example.com', $result['email']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertIsString($result['token']);
        $this->assertTrue(strlen($result['token']) > 0);
    }

    public function testCreateInvitationGroupNotFound(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Group not found');
        $this->expectExceptionCode(404);

        $this->service->createInvitation('nonexistent', 'user-123', 'user@example.com', 168);
    }

    public function testCreateInvitationUserNotAdmin(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->with('group-123')
            ->willReturn(['uuid' => 'group-123']);

        $this->membershipRepository->expects($this->once())
            ->method('isAdmin')
            ->with('group-123', 'user-456')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only group admins can invite users');
        $this->expectExceptionCode(403);

        $this->service->createInvitation('group-123', 'user-456', 'user@example.com', 168);
    }

    public function testCreateInvitationWithVariousExpirationTimes(): void
    {
        $this->groupRepository->expects($this->any())
            ->method('findById')
            ->willReturn(['uuid' => 'group-123']);

        $this->membershipRepository->expects($this->any())
            ->method('isAdmin')
            ->willReturn(true);

        $this->invitationRepository->expects($this->any())
            ->method('create');

        // Test with 1 hour expiration
        $result1 = $this->service->createInvitation('group-123', 'user-123', 'user1@example.com', 1);
        $expiresAt1 = strtotime($result1['expires_at']);
        $now = time();
        $this->assertGreaterThan($now, $expiresAt1);
        $this->assertLessThan($now + 7200, $expiresAt1); // Less than 2 hours

        // Test with 720 hours (30 days) expiration
        $result720 = $this->service->createInvitation('group-123', 'user-123', 'user2@example.com', 720);
        $expiresAt720 = strtotime($result720['expires_at']);
        $this->assertGreaterThan($now + 86400 * 29, $expiresAt720); // More than 29 days
    }

    public function testCreateInvitationGeneratesRandomToken(): void
    {
        $this->groupRepository->expects($this->any())
            ->method('findById')
            ->willReturn(['uuid' => 'group-123']);

        $this->membershipRepository->expects($this->any())
            ->method('isAdmin')
            ->willReturn(true);

        $this->invitationRepository->expects($this->any())
            ->method('create');

        $result1 = $this->service->createInvitation('group-123', 'user-123', 'user1@example.com', 168);
        $result2 = $this->service->createInvitation('group-123', 'user-123', 'user2@example.com', 168);

        $this->assertNotEquals($result1['token'], $result2['token']);
    }

    public function testCreateInvitationReturnsCorrectExpirationDate(): void
    {
        $this->groupRepository->expects($this->once())
            ->method('findById')
            ->willReturn(['uuid' => 'group-123']);

        $this->membershipRepository->expects($this->once())
            ->method('isAdmin')
            ->willReturn(true);

        $this->invitationRepository->expects($this->once())
            ->method('create');

        $before = time();
        $result = $this->service->createInvitation('group-123', 'user-123', 'user@example.com', 48);
        $after = time();

        $expiresAt = strtotime($result['expires_at']);
        $expectedMin = $before + (48 * 3600);
        $expectedMax = $after + (48 * 3600);

        $this->assertGreaterThanOrEqual($expectedMin, $expiresAt);
        $this->assertLessThanOrEqual($expectedMax + 1, $expiresAt);
    }
}
