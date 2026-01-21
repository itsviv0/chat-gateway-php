<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUuidPrimaryKey extends AbstractMigration
{
    public function change(): void
    {
        // Disable foreign key checks
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
}
