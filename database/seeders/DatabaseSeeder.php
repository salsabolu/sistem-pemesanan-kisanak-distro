<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        $owner = User::firstOrCreate(
            ['email' => 'pemilik@example.com'],
            [
                'nama' => 'Pemilik',
                'password' => Hash::make('password'),
                'no_whatsapp' => '080000000001',
                'alamat' => null,
            ]
        );

        if (! $owner->hasRole('pemilik')) {
            $owner->assignRole('pemilik');
        }
    }
}
