<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        // 1. Create Users
        $users = [
            [
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-alice-123',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-bob-456',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'charlie',
                'email' => 'charlie@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-charlie-789',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $usersTable = $this->table('users');
        // Truncate to avoid duplicates on re-run
        $usersTable->truncate();
        $usersTable->insert($users)->save();

        // Fetch user IDs
        $rows = $this->fetchAll('SELECT id, username FROM users');
        $userMap = [];
        foreach ($rows as $row) {
            $userMap[$row['username']] = $row['id'];
        }

        // 2. Create Groups
        $groups = [
            [
                'name' => 'General',
                'description' => 'General discussion',
                'is_private' => 0,
                'created_by' => $userMap['alice'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Secret Project',
                'description' => 'Top secret stuff',
                'is_private' => 1,
                'created_by' => $userMap['alice'],
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $groupsTable = $this->table('groups');
        $groupsTable->truncate();
        $groupsTable->insert($groups)->save();

        // Fetch group IDs
        $rows = $this->fetchAll('SELECT id, name FROM groups');
        $groupMap = [];
        foreach ($rows as $row) {
            $groupMap[$row['name']] = $row['id'];
        }

        // 3. Add Members
        $members = [
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['alice'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['bob'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['alice'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['charlie'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $membersTable = $this->table('group_members');
        $membersTable->truncate();
        $membersTable->insert($members)->save();

        // 4. Seed Invitations for private group
        $invitations = [
            [
                'group_id' => $groupMap['Secret Project'],
                'inviter_id' => $userMap['alice'],
                'email' => 'bob@example.com',
                'token' => 'invite-secret-bob',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ],
        ];

        $invitationsTable = $this->table('invitations');
        $invitationsTable->truncate();
        $invitationsTable->insert($invitations)->save();

        // 5. Seed Messages
        $messages = [
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['alice'],
                'content' => 'Welcome to the General group!',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['bob'],
                'content' => 'Hi everyone!',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['alice'],
                'content' => 'Remember: this group is invite-only.',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $messagesTable = $this->table('messages');
        $messagesTable->truncate();
        $messagesTable->insert($messages)->save();
    }
}
