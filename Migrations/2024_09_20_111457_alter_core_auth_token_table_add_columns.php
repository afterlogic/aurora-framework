<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AlterCoreAuthTokenTableAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Aurora\System\Models\AuthToken::truncate();
        Capsule::schema()->table('core_auth_tokens', function (Blueprint $table) {
            $table->integer('AccountId')->nullable()->after('UserId');
            $table->string('AccountType')->nullable()->after('AccountId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('core_auth_tokens', function (Blueprint $table) {
            $table->dropColumn('AccountId');
            $table->dropColumn('AccountType');
        });
    }
}
