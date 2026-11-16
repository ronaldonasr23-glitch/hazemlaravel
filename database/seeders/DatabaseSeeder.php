<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Content is captured from the live WordPress source via `php artisan
        // wp:capture` (then `wp:redirects`), not seeded from raw files. Here we
        // only need the admin roles & users.
        $this->call([
            RolesSeeder::class,
        ]);
    }
}
