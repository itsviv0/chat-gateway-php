<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\Database;
use Firebase\JWT\JWT;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use PDO;

class GroupApiTest extends TestCase
{
    private $app;
    private array $originalEnv;
    private string $dbPath;
    private PDO $pdo;
    private string $jwtSecret = 'test-secret-key';
    private array $testUsers = [];

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
        $this->dbPath = sys_get_temp_dir() . '/chat_gateway_test_' . uniqid('', true) . '.sqlite';

        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_NAME'] = 'Chat Gateway API Test';
        $_ENV['CORS_ALLOWED_ORIGINS'] = '*';
        $_ENV['DB_PATH'] = $this->dbPath;
        $_ENV['JWT_SECRET'] = $this->jwtSecret;

        $container = new Container();
        $containerConfig = require __DIR__ . '/../../config/container.php';
        $containerConfig($container);

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        $middlewareConfig = require __DIR__ . '/../../config/middleware.php';
        $middlewareConfig($this->app);

        $routesConfig = require __DIR__ . '/../../config/routes.php';
        $routesConfig($this->app);

        /** @var Database $db */
        $db = $container->get(Database::class);
        $this->pdo = $db->getConnection();

        $this->createSchema($this->pdo);
        $this->seedData($this->pdo);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        if (isset($this->dbPath) && file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    private function generateToken(string $userUuid): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $userUuid,
            'username' => $this->testUsers[$userUuid] ?? 'user',
        ];
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function testCreateGroup(): void
    {
        $aliceUuid = array_key_first($this->testUsers);
        $payload = [
            'name' => 'New Group',
            'description' => 'Created during test',
            'is_private' => false,
        ];

        $response = $this->jsonRequest('POST', '/groups', $this->generateToken($aliceUuid), $payload);

        $this->assertEquals(201, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals('New Group', $data['name']);
        $this->assertEquals(false, $data['is_private']);
        $this->assertEquals($aliceUuid, $data['created_by']);
    }    public function testJoinPublicGroup(): void
    {
        [$aliceUuid] = array_keys($this->testUsers);
        $groupResult = $this->pdo->query('SELECT uuid FROM groups LIMIT 1')->fetch();
        $groupUuid = $groupResult['uuid'];
        
        $charlieUuid = array_keys($this->testUsers)[2];
        $response = $this->jsonRequest('POST', '/groups/' . $groupUuid . '/join', $this->generateToken($charlieUuid));

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals($groupUuid, $data['group_id']);

        $membershipStmt = $this->pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $membershipStmt->execute([$groupUuid, $charlieUuid]);
        $this->assertEquals('member', $membershipStmt->fetchColumn());
    }

    public function testJoinPrivateGroupRequiresInvite(): void
    {
        $groupResult = $this->pdo->query('SELECT uuid FROM groups WHERE is_private = 1 LIMIT 1')->fetch();
        $groupUuid = $groupResult['uuid'];
        
        $bobUuid = array_keys($this->testUsers)[1];
        $response = $this->jsonRequest('POST', '/groups/' . $groupUuid . '/join', $this->generateToken($bobUuid));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testJoinPrivateGroupWithInviteSucceeds(): void
    {
        $groupResult = $this->pdo->query('SELECT uuid FROM groups WHERE is_private = 1 LIMIT 1')->fetch();
        $groupUuid = $groupResult['uuid'];
        
        $bobUuid = array_keys($this->testUsers)[1];
        $payload = ['invite_token' => 'invite-secret-bob'];
        $response = $this->jsonRequest('POST', '/groups/' . $groupUuid . '/join', $this->generateToken($bobUuid), $payload);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals($groupUuid, $data['group_id']);

        $membershipStmt = $this->pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $membershipStmt->execute([$groupUuid, $bobUuid]);
        $this->assertEquals('member', $membershipStmt->fetchColumn());
    }

    public function testInviteRequiresAdmin(): void
    {
        $groupResult = $this->pdo->query('SELECT uuid FROM groups LIMIT 1')->fetch();
        $groupUuid = $groupResult['uuid'];
        
        $bobUuid = array_keys($this->testUsers)[1];
        $payload = ['email' => 'newuser@example.com'];
        $response = $this->jsonRequest('POST', '/groups/' . $groupUuid . '/invite', $this->generateToken($bobUuid), $payload);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSendAndListMessagesWithPagination(): void
    {
        $groupResult = $this->pdo->query('SELECT uuid FROM groups LIMIT 1')->fetch();
        $groupUuid = $groupResult['uuid'];
        
        $charlieUuid = array_keys($this->testUsers)[2];

        // Charlie joins public group
        $this->jsonRequest('POST', '/groups/' . $groupUuid . '/join', $this->generateToken($charlieUuid));

        // Charlie sends a message
        $messagePayload = ['content' => 'Hello from Charlie'];
        $sendResponse = $this->jsonRequest('POST', '/groups/' . $groupUuid . '/messages', $this->generateToken($charlieUuid), $messagePayload);
        $this->assertEquals(201, $sendResponse->getStatusCode());

        // Paginate messages (page size 1)
        $listResponse = $this->jsonRequest('GET', '/groups/' . $groupUuid . '/messages?page=1&page_size=1', $this->generateToken($charlieUuid));
        $this->assertEquals(200, $listResponse->getStatusCode());
        $data = $this->decodeResponse($listResponse);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(1, $data['pagination']['page_size']);
        $this->assertNotEmpty($data['data']);
    }

    private function jsonRequest(string $method, string $uri, string $token, array $body = [])
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Content-Type', 'application/json');

        if (!empty($body)) {
            $stream = (new StreamFactory())->createStream(json_encode($body));
            $request = $request->withBody($stream);
        }

        return $this->app->handle($request);
    }

    private function decodeResponse($response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        return $data;
    }

    private function createSchema(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $pdo->exec('CREATE TABLE users (
            uuid VARCHAR(36) PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );');
        $pdo->exec('CREATE UNIQUE INDEX idx_users_username ON users (username);');
        $pdo->exec('CREATE UNIQUE INDEX idx_users_email ON users (email);');

        $pdo->exec('CREATE TABLE groups (
            uuid VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            is_private BOOLEAN NOT NULL DEFAULT 0,
            created_by VARCHAR(36) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(uuid)
        );');

        $pdo->exec('CREATE TABLE group_members (
            group_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT "member",
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(uuid) ON DELETE CASCADE
        );');
        $pdo->exec('CREATE INDEX idx_group_members_user_id ON group_members (user_id);');

        $pdo->exec('CREATE TABLE messages (
            uuid VARCHAR(36) PRIMARY KEY,
            group_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(uuid)
        );');
        $pdo->exec('CREATE INDEX idx_messages_group_id ON messages (group_id);');
        $pdo->exec('CREATE INDEX idx_messages_user_id ON messages (user_id);');

        $pdo->exec('CREATE TABLE invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id VARCHAR(36) NOT NULL,
            inviter_id VARCHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES users(uuid) ON DELETE CASCADE
        );');
        $pdo->exec('CREATE UNIQUE INDEX idx_invitations_token ON invitations (token);');
    }

    private function seedData(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');

        // Generate UUIDs for test users
        $aliceUuid = 'alice-uuid-' . md5('alice');
        $bobUuid = 'bob-uuid-' . md5('bob');
        $charlieUuid = 'charlie-uuid-' . md5('charlie');

        $this->testUsers = [
            $aliceUuid => 'alice',
            $bobUuid => 'bob',
            $charlieUuid => 'charlie',
        ];

        $pdo->exec("INSERT INTO users (uuid, username, email, password_hash, created_at) VALUES
            ('$aliceUuid', 'alice', 'alice@example.com', 'hash', '{$now}'),
            ('$bobUuid', 'bob', 'bob@example.com', 'hash', '{$now}'),
            ('$charlieUuid', 'charlie', 'charlie@example.com', 'hash', '{$now}')
        ");

        $generalGroupUuid = 'general-uuid-' . md5('general');
        $secretGroupUuid = 'secret-uuid-' . md5('secret');

        $pdo->exec("INSERT INTO groups (uuid, name, description, is_private, created_by, created_at) VALUES
            ('$generalGroupUuid', 'General', 'General discussion', 0, '$aliceUuid', '{$now}'),
            ('$secretGroupUuid', 'Secret Project', 'Top secret stuff', 1, '$aliceUuid', '{$now}')
        ");

        $pdo->exec("INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES
            ('$generalGroupUuid', '$aliceUuid', 'admin', '{$now}'),
            ('$generalGroupUuid', '$bobUuid', 'member', '{$now}'),
            ('$secretGroupUuid', '$aliceUuid', 'admin', '{$now}')
        ");

        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $pdo->exec("INSERT INTO invitations (group_id, inviter_id, email, token, status, created_at, expires_at) VALUES
            ('$secretGroupUuid', '$aliceUuid', 'bob@example.com', 'invite-secret-bob', 'pending', '{$now}', '{$expires}')
        ");

        $msg1Uuid = 'msg1-uuid-' . md5('msg1');
        $msg2Uuid = 'msg2-uuid-' . md5('msg2');

        $pdo->exec("INSERT INTO messages (uuid, group_id, user_id, content, created_at) VALUES
            ('$msg1Uuid', '$generalGroupUuid', '$aliceUuid', 'Welcome to the General group!', '{$now}'),
            ('$msg2Uuid', '$generalGroupUuid', '$bobUuid', 'Hi everyone!', '{$now}')
        ");
    }
}
