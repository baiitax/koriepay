<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // database/seeders/DatabaseSeeder.php

    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@sahelpay.com'],
            [
                'name' => 'Sovereign Admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            \Database\Seeders\LedgerSeed::class,
        ]);
    }
}
