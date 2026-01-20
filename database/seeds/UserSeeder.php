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
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'charlie',
                'email' => 'charlie@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $usersTable = $this->table('users');
        // Truncate to avoid duplicates on re-run
        $usersTable->truncate();
        $usersTable->insert($users)->save();

        // Get IDs (assuming 1, 2, 3 but safely fetching could be better if complex, 
        // but for a simple seeder reliable reset is fine)

        // 2. Create Groups
        $groups = [
            [
                'name' => 'General',
                'description' => 'General discussion',
                'is_private' => 0,
                'created_by' => 1, // alice
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Secret Project',
                'description' => 'Top secret stuff',
                'is_private' => 1,
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $groupsTable = $this->table('groups');
        $groupsTable->truncate();
        $groupsTable->insert($groups)->save();

        // 3. Add Members
        $members = [
            [
                'group_id' => 1, // General
                'user_id' => 1,  // alice
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => 1,
                'user_id' => 2,  // bob
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => 2, // Secret Project
                'user_id' => 1,  // alice
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s'),
            ],
            [
                'group_id' => 2,
                'user_id' => 3,  // charlie
                'role' => 'member',
                'joined_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $membersTable = $this->table('group_members');
        $membersTable->truncate();
        $membersTable->insert($members)->save();
    }
}
