<?php

use Aurora\Modules\Core\Models\Channel;
use Aurora\Modules\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        /** @var Channel */
        $channel = Channel::query()->firstOrCreate([
            'Login' => 'Default'
        ]);

        Tenant::query()->firstOrCreate([
            'IdChannel' => $channel->Id,
            'Name' => 'Default',
            'IsDefault' => true
        ]);

        Model::reguard();
    }
}
