<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUuidPrimaryKey extends AbstractMigration
{
    public function change(): void
    {
        $adapter = $this->getAdapter();
        $adapterType = $adapter->getAdapterType();
        
        // SQLite handling
        if ($adapterType === 'sqlite') {
            $this->execute('PRAGMA foreign_keys = OFF');
            $this->migrateSqlite();
            $this->execute('PRAGMA foreign_keys = ON');
            return;
        }
        
        // MySQL handling
        $this->execute('SET FOREIGN_KEY_CHECKS=0');

        // Add uuid column to users table
        $users = $this->table('users');
        $users->addColumn('uuid', 'string', ['limit' => 36, 'after' => 'id'])
            ->save();

        // Generate UUIDs for existing users
        $this->execute("UPDATE users SET uuid = UUID() WHERE uuid IS NULL");

        // Make uuid unique
        $users->addIndex(['uuid'], ['unique' => true])->save();

        // Drop old primary key and create new one with uuid
        $users->changePrimaryKey(['uuid'])->save();

        // Drop the old id column
        $users->removeColumn('id')->save();

        // Update group_members table - drop and recreate foreign keys
        $this->execute('ALTER TABLE group_members DROP FOREIGN KEY group_members_group_id_foreign');
        $this->execute('ALTER TABLE group_members DROP FOREIGN KEY group_members_user_id_foreign');

        // Modify group_members columns to use uuid
        $this->execute('ALTER TABLE group_members MODIFY group_id VARCHAR(36)');
        $this->execute('ALTER TABLE group_members MODIFY user_id VARCHAR(36)');

        // Recreate foreign keys
        $this->execute('ALTER TABLE group_members ADD CONSTRAINT group_members_group_id_foreign FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE');
        $this->execute('ALTER TABLE group_members ADD CONSTRAINT group_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(uuid) ON DELETE CASCADE');

        // Update messages table - add uuid column
        $messages = $this->table('messages');
        $messages->addColumn('uuid', 'string', ['limit' => 36, 'after' => 'id'])
            ->save();

        $this->execute("UPDATE messages SET uuid = UUID() WHERE uuid IS NULL");
        $messages->addIndex(['uuid'], ['unique' => true])->save();

        // Drop old foreign keys
        $this->execute('ALTER TABLE messages DROP FOREIGN KEY messages_group_id_foreign');
        $this->execute('ALTER TABLE messages DROP FOREIGN KEY messages_user_id_foreign');

        // Modify messages columns to use uuid
        $this->execute('ALTER TABLE messages MODIFY group_id VARCHAR(36)');
        $this->execute('ALTER TABLE messages MODIFY user_id VARCHAR(36)');

        // Change messages primary key to uuid
        $messages->changePrimaryKey(['uuid'])->save();
        $messages->removeColumn('id')->save();

        // Recreate foreign keys
        $this->execute('ALTER TABLE messages ADD CONSTRAINT messages_group_id_foreign FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE');
        $this->execute('ALTER TABLE messages ADD CONSTRAINT messages_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(uuid) ON DELETE NO ACTION');

        // Update groups table - drop and recreate foreign key for created_by
        $this->execute('ALTER TABLE groups DROP FOREIGN KEY groups_created_by_foreign');
        $this->execute('ALTER TABLE groups MODIFY created_by VARCHAR(36)');
        $this->execute('ALTER TABLE groups ADD CONSTRAINT groups_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(uuid) ON DELETE NO ACTION');

        // Update groups table - make uuid primary key
        $groups = $this->table('groups');
        $groups->addColumn('uuid', 'string', ['limit' => 36, 'after' => 'id'])
            ->save();

        $this->execute("UPDATE groups SET uuid = UUID() WHERE uuid IS NULL");
        $groups->addIndex(['uuid'], ['unique' => true])->save();
        $groups->changePrimaryKey(['uuid'])->save();
        $groups->removeColumn('id')->save();

        // Update invitations table - modify foreign keys
        $this->execute('ALTER TABLE invitations DROP FOREIGN KEY invitations_group_id_foreign');
        $this->execute('ALTER TABLE invitations DROP FOREIGN KEY invitations_inviter_id_foreign');

        $this->execute('ALTER TABLE invitations MODIFY group_id VARCHAR(36)');
        $this->execute('ALTER TABLE invitations MODIFY inviter_id VARCHAR(36)');

        $this->execute('ALTER TABLE invitations ADD CONSTRAINT invitations_group_id_foreign FOREIGN KEY (group_id) REFERENCES groups(uuid) ON DELETE CASCADE');
        $this->execute('ALTER TABLE invitations ADD CONSTRAINT invitations_inviter_id_foreign FOREIGN KEY (inviter_id) REFERENCES users(uuid) ON DELETE CASCADE');

        // Re-enable foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1');
    }
    
    private function migrateSqlite(): void
    {
        // SQLite requires recreating tables since it doesn't support complex ALTER TABLE operations
        
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
        
        // 1. Migrate users table
        $this->execute('CREATE TABLE users_new (
            uuid VARCHAR(36) PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            api_token VARCHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        
        $this->execute('CREATE UNIQUE INDEX users_new_username_index ON users_new (username)');
        $this->execute('CREATE UNIQUE INDEX users_new_email_index ON users_new (email)');
        $this->execute('CREATE UNIQUE INDEX users_new_api_token_index ON users_new (api_token)');
        
        // Copy data with generated UUIDs
        $users = $this->fetchAll('SELECT * FROM users');
        $userIdMap = [];
        foreach ($users as $user) {
            $uuid = $generateUuid();
            $userIdMap[$user['id']] = $uuid;
            $this->execute(sprintf(
                "INSERT INTO users_new (uuid, username, email, password_hash, api_token, created_at) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
                $uuid,
                $this->escape($user['username']),
                $this->escape($user['email']),
                $this->escape($user['password_hash']),
                $this->escape($user['api_token']),
                $user['created_at']
            ));
        }
        
        // 2. Migrate groups table
        $this->execute('CREATE TABLE groups_new (
            uuid VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            is_private INTEGER DEFAULT 0,
            created_by VARCHAR(36) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users_new(uuid) ON DELETE NO ACTION
        )');
        
        $groups = $this->fetchAll('SELECT * FROM groups');
        $groupIdMap = [];
        foreach ($groups as $group) {
            $uuid = $generateUuid();
            $groupIdMap[$group['id']] = $uuid;
            $this->execute(sprintf(
                "INSERT INTO groups_new (uuid, name, description, is_private, created_by, created_at) VALUES ('%s', '%s', %s, %d, '%s', '%s')",
                $uuid,
                $this->escape($group['name']),
                $group['description'] ? "'" . $this->escape($group['description']) . "'" : 'NULL',
                $group['is_private'],
                $userIdMap[$group['created_by']],
                $group['created_at']
            ));
        }
        
        // 3. Migrate group_members table
        $this->execute('CREATE TABLE group_members_new (
            group_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            role VARCHAR(20) DEFAULT \'member\',
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES groups_new(uuid) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users_new(uuid) ON DELETE CASCADE
        )');
        
        $this->execute('CREATE INDEX group_members_new_user_id_index ON group_members_new (user_id)');
        
        $members = $this->fetchAll('SELECT * FROM group_members');
        foreach ($members as $member) {
            $this->execute(sprintf(
                "INSERT INTO group_members_new (group_id, user_id, role, joined_at) VALUES ('%s', '%s', '%s', '%s')",
                $groupIdMap[$member['group_id']],
                $userIdMap[$member['user_id']],
                $this->escape($member['role']),
                $member['joined_at']
            ));
        }
        
        // 4. Migrate messages table
        $this->execute('CREATE TABLE messages_new (
            uuid VARCHAR(36) PRIMARY KEY,
            group_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups_new(uuid) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users_new(uuid) ON DELETE NO ACTION
        )');
        
        $this->execute('CREATE INDEX messages_new_group_id_index ON messages_new (group_id)');
        $this->execute('CREATE INDEX messages_new_user_id_index ON messages_new (user_id)');
        
        $messages = $this->fetchAll('SELECT * FROM messages');
        foreach ($messages as $message) {
            $uuid = $generateUuid();
            $this->execute(sprintf(
                "INSERT INTO messages_new (uuid, group_id, user_id, content, created_at) VALUES ('%s', '%s', '%s', '%s', '%s')",
                $uuid,
                $groupIdMap[$message['group_id']],
                $userIdMap[$message['user_id']],
                $this->escape($message['content']),
                $message['created_at']
            ));
        }
        
        // 5. Migrate invitations table
        $this->execute('CREATE TABLE invitations_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id VARCHAR(36) NOT NULL,
            inviter_id VARCHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            status VARCHAR(20) DEFAULT \'pending\',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (group_id) REFERENCES groups_new(uuid) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES users_new(uuid) ON DELETE CASCADE
        )');
        
        $this->execute('CREATE UNIQUE INDEX invitations_new_token_index ON invitations_new (token)');
        
        $invitations = $this->fetchAll('SELECT * FROM invitations');
        foreach ($invitations as $invitation) {
            $this->execute(sprintf(
                "INSERT INTO invitations_new (group_id, inviter_id, email, token, status, created_at, expires_at) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $groupIdMap[$invitation['group_id']],
                $userIdMap[$invitation['inviter_id']],
                $this->escape($invitation['email']),
                $this->escape($invitation['token']),
                $this->escape($invitation['status']),
                $invitation['created_at'],
                $invitation['expires_at']
            ));
        }
        
        // Drop old tables and rename new ones
        $this->execute('DROP TABLE invitations');
        $this->execute('DROP TABLE messages');
        $this->execute('DROP TABLE group_members');
        $this->execute('DROP TABLE groups');
        $this->execute('DROP TABLE users');
        
        $this->execute('ALTER TABLE users_new RENAME TO users');
        $this->execute('ALTER TABLE groups_new RENAME TO groups');
        $this->execute('ALTER TABLE group_members_new RENAME TO group_members');
        $this->execute('ALTER TABLE messages_new RENAME TO messages');
        $this->execute('ALTER TABLE invitations_new RENAME TO invitations');
    }
    
    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
