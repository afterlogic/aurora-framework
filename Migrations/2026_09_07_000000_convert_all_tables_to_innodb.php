<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class ConvertAllTablesToInnodb extends Migration
{
    public function up()
    {
        $conn = Capsule::connection();
        $prefix = $conn->getTablePrefix();
        $dbName = $conn->getDatabaseName();

        $tables = $conn->select(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?",
            [$dbName]
        );

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;
            $conn->statement(
                "ALTER TABLE `" . str_replace('`', '``', $tableName) . "` ENGINE = InnoDB"
            );
        }
    }

    public function down()
    {
    }
}
