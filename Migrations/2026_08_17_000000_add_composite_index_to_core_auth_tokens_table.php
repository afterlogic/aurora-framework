<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddCompositeIndexUserIdLastUsageToCoreAuthTokensTable extends Migration
{
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        $table = $prefix . 'core_auth_tokens';

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($table);

        if (!$doctrineTable->hasIndex('core_auth_tokens_userid_lastusage_index')) {
            Capsule::connection()->statement(
                "ALTER TABLE `{$table}` ADD INDEX `core_auth_tokens_userid_lastusage_index` (`UserId`, `LastUsageDateTime`)"
            );
        }
    }

    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        $table = $prefix . 'core_auth_tokens';

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($table);

        if ($doctrineTable->hasIndex('core_auth_tokens_userid_lastusage_index')) {
            Capsule::connection()->statement(
                "ALTER TABLE `{$table}` DROP INDEX `core_auth_tokens_userid_lastusage_index`"
            );
        }
    }
}
