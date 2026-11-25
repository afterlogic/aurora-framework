<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCoreSuperadminSessionsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('core_superadmin_sessions', function (Blueprint $table) {
            $table->char('SessionHash', 64)->primary();
            $table->unsignedBigInteger('ExpiresAt')->index();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('core_superadmin_sessions');
    }
}
