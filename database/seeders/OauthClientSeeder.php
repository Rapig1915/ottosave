<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OauthClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Start fresh
        DB::table('oauth_clients')->truncate();
        
        DB::table('oauth_clients')->insert([
            'id' => 1,
            'name' => 'App Front End',
            'secret' => 'SfUriDYNPVejrGckqC0OIWHoWKHTJwGIl9G5D9TD',
            'redirect' => config('app.url'),
            'personal_access_client' => 0,
            'password_client' => 1,
            'revoked' => 0,
        ]);
    }
}
