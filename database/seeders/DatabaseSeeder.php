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
            BahanSeeder::class,
            DistroSeeder::class,
        ]);

        $owner = User::firstOrCreate(
            ['email' => 'pemilik@mail.com'],
            [
                'nama' => 'Pemilik',
                'password' => Hash::make('password'),
                'whatsapp' => '083811111111',
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
                'whatsapp' => '083822222222',
                'alamat' => null,
            ]
        );

        if (! $cashier->hasRole('kasir')) {
            $cashier->assignRole('kasir');
        }

        $pembeli = User::firstOrCreate(
            ['email' => 'jo@mail.com'],
            [
                'nama' => 'Jo',
                'password' => Hash::make('password'),
                'whatsapp' => '083811112222',
                'alamat' => 'Jl. Klampis Ngasem No 67, Sukolilo, Surabaya',
            ]
        );

        if (! $pembeli->hasRole('pembeli')) {
            $pembeli->assignRole('pembeli');
        }

        $pembeli = User::firstOrCreate(
            ['email' => 'nico@mail.com'],
            [
                'nama' => 'Nicholas Apparel',
                'password' => Hash::make('password'),
                'whatsapp' => '083833334444',
                'alamat' => 'Jl. Mulyorejo No 55, Sukolilo, Surabaya',
            ]
        );

        if (! $pembeli->hasRole('pembeli')) {
            $pembeli->assignRole('pembeli');
        }

        $pembeli = User::firstOrCreate(
            ['email' => 'suns@mail.com'],
            [
                'nama' => 'Suns Custom',
                'password' => Hash::make('password'),
                'whatsapp' => '083855556666',
                'alamat' => 'Jl. Ir. Soekarno 185, Surabaya',
            ]
        );

        if (! $pembeli->hasRole('pembeli')) {
            $pembeli->assignRole('pembeli');
        }
    }
}
