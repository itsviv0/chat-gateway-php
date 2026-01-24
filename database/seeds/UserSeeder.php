<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
public function run(): void
    {
        // Helper function to generate UUID v4
        $generateUuid = function() {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        };
        
        // 1. Create Users
        $users = [
            [
                'uuid' => $generateUuid(),
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-alice-123',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-bob-456',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'username' => 'charlie',
                'email' => 'charlie@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-charlie-789',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'username' => 'david',
                'email' => 'david@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-david-101',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'username' => 'emma',
                'email' => 'emma@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'api_token' => 'token-emma-202',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $usersTable = $this->table('users', ['id' => false, 'primary_key' => 'uuid']);
        // Truncate to avoid duplicates on re-run
        $usersTable->truncate();
        $usersTable->insert($users)->save();

        // Fetch user UUIDs
        $rows = $this->fetchAll('SELECT uuid, username FROM users');
        $userMap = [];
        foreach ($rows as $row) {
            $userMap[$row['username']] = $row['uuid'];
        }

        // 2. Create Groups
        $groups = [
            [
                'uuid' => $generateUuid(),
                'name' => 'General',
                'description' => 'General discussion for everyone',
                'is_private' => 0,
                'created_by' => $userMap['alice'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'name' => 'Tech Talk',
                'description' => 'Discussion about technology and programming',
                'is_private' => 0,
                'created_by' => $userMap['bob'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'name' => 'Random',
                'description' => 'Random conversations and fun',
                'is_private' => 0,
                'created_by' => $userMap['charlie'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'name' => 'Secret Project',
                'description' => 'Top secret project discussion',
                'is_private' => 1,
                'created_by' => $userMap['alice'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'name' => 'VIP Club',
                'description' => 'Private group for VIP members only',
                'is_private' => 1,
                'created_by' => $userMap['david'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $generateUuid(),
                'name' => 'Project Alpha',
                'description' => 'Confidential project alpha discussions',
                'is_private' => 1,
                'created_by' => $userMap['emma'],
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $groupsTable = $this->table('groups', ['id' => false, 'primary_key' => 'uuid']);
        $groupsTable->truncate();
        $groupsTable->insert($groups)->save();

        // Fetch group UUIDs
        $rows = $this->fetchAll('SELECT uuid, name FROM groups');
        $groupMap = [];
        foreach ($rows as $row) {
            $groupMap[$row['name']] = $row['uuid'];
        }

        // 3. Add Members
        $members = [
            // General group - all users
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
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['charlie'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['david'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['emma'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            
            // Tech Talk group
            [
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['bob'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['alice'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['david'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            
            // Random group
            [
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['charlie'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['bob'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['emma'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            
            // Secret Project - private
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
            ],
            
            // VIP Club - private
            [
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['david'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['alice'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['emma'],
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            
            // Project Alpha - private
            [
                'group_id' => $groupMap['Project Alpha'],
                'user_id' => $userMap['emma'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => $groupMap['Project Alpha'],
                'user_id' => $userMap['bob'],
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
            [
                'group_id' => $groupMap['VIP Club'],
                'inviter_id' => $userMap['david'],
                'email' => 'charlie@example.com',
                'token' => 'invite-vip-charlie',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ],
            [
                'group_id' => $groupMap['Project Alpha'],
                'inviter_id' => $userMap['emma'],
                'email' => 'david@example.com',
                'token' => 'invite-alpha-david',
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
            // General group messages
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['alice'],
                'content' => 'Welcome to the General group!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['bob'],
                'content' => 'Hi everyone! Glad to be here.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 50 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['charlie'],
                'content' => 'Hello team! Looking forward to collaborating.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 30 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['david'],
                'content' => 'Great to meet everyone!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['General'],
                'user_id' => $userMap['emma'],
                'content' => 'Hi all! Excited to be part of this group.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            ],
            
            // Tech Talk messages
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['bob'],
                'content' => 'Welcome to Tech Talk! Let\'s discuss all things tech.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['alice'],
                'content' => 'Anyone tried the new PHP 8.3 features?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours 30 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['david'],
                'content' => 'Yes! The readonly classes are amazing.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Tech Talk'],
                'user_id' => $userMap['bob'],
                'content' => 'I\'ve been working with typed constants lately.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 45 minutes')),
            ],
            
            // Random group messages
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['charlie'],
                'content' => 'Anyone up for a coffee break?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['bob'],
                'content' => 'Count me in!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours 55 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['emma'],
                'content' => 'What\'s everyone working on today?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours 30 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Random'],
                'user_id' => $userMap['charlie'],
                'content' => 'Just finished a sprint planning. Feeling productive!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            ],
            
            // Secret Project messages (private)
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['alice'],
                'content' => 'Remember: this group is invite-only.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['charlie'],
                'content' => 'Understood. The project is on track.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours 30 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Secret Project'],
                'user_id' => $userMap['alice'],
                'content' => 'Great! Let\'s review the progress tomorrow.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            ],
            
            // VIP Club messages (private)
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['david'],
                'content' => 'Welcome to the VIP Club!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['alice'],
                'content' => 'Thanks for the invitation!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours 45 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['emma'],
                'content' => 'Excited to be part of this exclusive group.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours 30 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['VIP Club'],
                'user_id' => $userMap['david'],
                'content' => 'Let\'s discuss our quarterly goals.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours')),
            ],
            
            // Project Alpha messages (private)
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Project Alpha'],
                'user_id' => $userMap['emma'],
                'content' => 'Project Alpha kickoff meeting scheduled for next week.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 hours')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Project Alpha'],
                'user_id' => $userMap['bob'],
                'content' => 'Perfect! I\'ll prepare the technical documentation.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours 45 minutes')),
            ],
            [
                'uuid' => $generateUuid(),
                'group_id' => $groupMap['Project Alpha'],
                'user_id' => $userMap['emma'],
                'content' => 'Excellent! Looking forward to the launch.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours 30 minutes')),
            ],
        ];

        $messagesTable = $this->table('messages', ['id' => false, 'primary_key' => 'uuid']);
        $messagesTable->truncate();
        $messagesTable->insert($messages)->save();
    }
}
