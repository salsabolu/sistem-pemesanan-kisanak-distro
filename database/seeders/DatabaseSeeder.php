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
            KategoriSeeder::class,
            WarnaSeeder::class,
            UkuranSeeder::class,
        ]);

        $owner = User::firstOrCreate(
            ['email' => 'pemilik@mail.com'],
            [
                'nama' => 'Pemilik',
                'password' => Hash::make('password'),
                'whatsapp' => '080000000001',
                'alamat' => null,
            ]
        );

        if (! $owner->hasRole('pemilik')) {
            $owner->assignRole('pemilik');
        }

        $cashier = User::firstOrCreate(
            ['email' => 'kasir@mail.com'],
            [
                'nama' => 'Kasir',
                'password' => Hash::make('password'),
                'whatsapp' => '080000000002',
                'alamat' => null,
            ]
        );

        if (! $cashier->hasRole('kasir')) {
            $cashier->assignRole('kasir');
        }
    }
}
