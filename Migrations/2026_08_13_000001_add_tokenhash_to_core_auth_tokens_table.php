<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddTokenHashToCoreAuthTokensTable extends Migration
{
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'core_auth_tokens');

        // Rename Token column to TokenHash (stores SHA-256 hash instead of plain text)
        if ($doctrineTable->hasColumn('Token') && !$doctrineTable->hasColumn('TokenHash')) {
            Capsule::connection()->statement(
                "ALTER TABLE `{$prefix}core_auth_tokens` CHANGE `Token` `TokenHash` TEXT NULL"
            );

            // Drop old plain text token index
            Capsule::connection()->statement(
                "ALTER TABLE `{$prefix}core_auth_tokens` DROP INDEX `core_auth_tokens_token_index`"
            );

            // Hash all existing plain text tokens
            Capsule::connection()->statement(
                "UPDATE `{$prefix}core_auth_tokens` SET `TokenHash` = SHA2(`TokenHash`, 256) WHERE `TokenHash` IS NOT NULL"
            );

            // Add index on TokenHash (with key length for TEXT column)
            Capsule::connection()->statement(
                "ALTER TABLE `{$prefix}core_auth_tokens` ADD INDEX `core_auth_tokens_token_index` (`TokenHash`(255))"
            );
        }
    }

    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'core_auth_tokens');

        if ($doctrineTable->hasColumn('TokenHash') && !$doctrineTable->hasColumn('Token')) {
            Capsule::connection()->statement(
                "ALTER TABLE `{$prefix}core_auth_tokens` CHANGE `TokenHash` `Token` TEXT NULL"
            );
        }
    }
}
