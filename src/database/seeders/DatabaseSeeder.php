<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\ConnectRelationshipsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            ConnectRelationshipsSeeder::class,
            UsersTableSeeder::class,
        ]);

    }
}
