<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        // Users table
        $users = $this->table('users');
        $users->addColumn('username', 'string', ['limit' => 50])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('api_token', 'string', ['limit' => 64])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['api_token'], ['unique' => true])
            ->create();

        // Groups table
        $groups = $this->table('groups');
        $groups->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('is_private', 'boolean', ['default' => false])
            ->addColumn('created_by', 'integer')
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->create();

        // Group Members table
        $groupMembers = $this->table('group_members', ['id' => false, 'primary_key' => ['group_id', 'user_id']]);
        $groupMembers->addColumn('group_id', 'integer')
            ->addColumn('user_id', 'integer')
            ->addColumn('role', 'string', ['limit' => 20, 'default' => 'member'])
            ->addColumn('joined_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('group_id', 'groups', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['user_id'])
            ->create();

        // Messages table
        $messages = $this->table('messages');
        $messages->addColumn('group_id', 'integer')
            ->addColumn('user_id', 'integer')
            ->addColumn('content', 'text')
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('group_id', 'groups', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addIndex(['group_id'])
            ->addIndex(['user_id'])
            ->create();

        // Invitations table
        $invitations = $this->table('invitations');
        $invitations->addColumn('group_id', 'integer')
            ->addColumn('inviter_id', 'integer')
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('token', 'string', ['limit' => 64])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('expires_at', 'datetime')
            ->addForeignKey('group_id', 'groups', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('inviter_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['token'], ['unique' => true])
            ->create();
    }
}
