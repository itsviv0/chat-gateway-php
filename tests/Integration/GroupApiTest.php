<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\Database;
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

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
        $this->dbPath = sys_get_temp_dir() . '/chat_gateway_test_' . uniqid('', true) . '.sqlite';

        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_NAME'] = 'Chat Gateway API Test';
        $_ENV['CORS_ALLOWED_ORIGINS'] = '*';
        $_ENV['DB_PATH'] = $this->dbPath;

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

    public function testCreateGroup(): void
    {
        $payload = [
            'name' => 'New Group',
            'description' => 'Created during test',
            'is_private' => false,
        ];

        $response = $this->jsonRequest('POST', '/groups', 'token-alice-123', $payload);

        $this->assertEquals(201, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals('New Group', $data['name']);
        $this->assertEquals(false, $data['is_private']);
        $this->assertEquals(1, $data['created_by']);

        $membershipStmt = $this->pdo->query('SELECT role FROM group_members WHERE group_id = ' . (int) $data['id'] . ' AND user_id = 1');
        $this->assertEquals('admin', $membershipStmt->fetchColumn());
    }

    public function testJoinPublicGroup(): void
    {
        $response = $this->jsonRequest('POST', '/groups/1/join', 'token-charlie-789');

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals(3, $data['user_id']);
        $this->assertEquals(1, $data['group_id']);

        $membershipStmt = $this->pdo->query('SELECT role FROM group_members WHERE group_id = 1 AND user_id = 3');
        $this->assertEquals('member', $membershipStmt->fetchColumn());
    }

    public function testJoinPrivateGroupRequiresInvite(): void
    {
        $response = $this->jsonRequest('POST', '/groups/2/join', 'token-bob-456');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testJoinPrivateGroupWithInviteSucceeds(): void
    {
        $payload = ['invite_token' => 'invite-secret-bob'];
        $response = $this->jsonRequest('POST', '/groups/2/join', 'token-bob-456', $payload);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $this->decodeResponse($response);
        $this->assertEquals(2, $data['group_id']);

        $membershipStmt = $this->pdo->query('SELECT role FROM group_members WHERE group_id = 2 AND user_id = 2');
        $this->assertEquals('member', $membershipStmt->fetchColumn());
    }

    public function testInviteRequiresAdmin(): void
    {
        $payload = ['email' => 'newuser@example.com'];
        $response = $this->jsonRequest('POST', '/groups/1/invite', 'token-bob-456', $payload);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSendAndListMessagesWithPagination(): void
    {
        // Charlie joins public group
        $this->jsonRequest('POST', '/groups/1/join', 'token-charlie-789');

        // Charlie sends a message
        $messagePayload = ['content' => 'Hello from Charlie'];
        $sendResponse = $this->jsonRequest('POST', '/groups/1/messages', 'token-charlie-789', $messagePayload);
        $this->assertEquals(201, $sendResponse->getStatusCode());

        // Paginate messages (page size 1)
        $listResponse = $this->jsonRequest('GET', '/groups/1/messages?page=1&page_size=1', 'token-charlie-789');
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            api_token VARCHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );');
        $pdo->exec('CREATE UNIQUE INDEX idx_users_username ON users (username);');
        $pdo->exec('CREATE UNIQUE INDEX idx_users_email ON users (email);');
        $pdo->exec('CREATE UNIQUE INDEX idx_users_api_token ON users (api_token);');

        $pdo->exec('CREATE TABLE groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            is_private BOOLEAN NOT NULL DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        );');

        $pdo->exec('CREATE TABLE group_members (
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT "member",
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );');
        $pdo->exec('CREATE INDEX idx_group_members_user_id ON group_members (user_id);');

        $pdo->exec('CREATE TABLE messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );');
        $pdo->exec('CREATE INDEX idx_messages_group_id ON messages (group_id);');
        $pdo->exec('CREATE INDEX idx_messages_user_id ON messages (user_id);');

        $pdo->exec('CREATE TABLE invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            inviter_id INTEGER NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE
        );');
        $pdo->exec('CREATE UNIQUE INDEX idx_invitations_token ON invitations (token);');
    }

    private function seedData(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');

        $pdo->exec("INSERT INTO users (id, username, email, password_hash, api_token, created_at) VALUES
            (1, 'alice', 'alice@example.com', 'hash', 'token-alice-123', '{$now}'),
            (2, 'bob', 'bob@example.com', 'hash', 'token-bob-456', '{$now}'),
            (3, 'charlie', 'charlie@example.com', 'hash', 'token-charlie-789', '{$now}')
        ");

        $pdo->exec("INSERT INTO groups (id, name, description, is_private, created_by, created_at) VALUES
            (1, 'General', 'General discussion', 0, 1, '{$now}'),
            (2, 'Secret Project', 'Top secret stuff', 1, 1, '{$now}')
        ");

        $pdo->exec("INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES
            (1, 1, 'admin', '{$now}'),
            (1, 2, 'member', '{$now}'),
            (2, 1, 'admin', '{$now}')
        ");

        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $pdo->exec("INSERT INTO invitations (group_id, inviter_id, email, token, status, created_at, expires_at) VALUES
            (2, 1, 'bob@example.com', 'invite-secret-bob', 'pending', '{$now}', '{$expires}')
        ");

        $pdo->exec("INSERT INTO messages (group_id, user_id, content, created_at) VALUES
            (1, 1, 'Welcome to the General group!', '{$now}'),
            (1, 2, 'Hi everyone!', '{$now}')
        ");
    }
}
