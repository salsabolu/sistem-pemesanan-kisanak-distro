<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UkuranSeeder extends Seeder
{
    public function run(): void
    {
        $ukuran = [
            ['nama' => 'A', 'panjang' => null, 'lebar' => 33, 'is_active' => true],
            ['nama' => 'B', 'panjang' => null, 'lebar' => 35, 'is_active' => true],
            ['nama' => 'C', 'panjang' => null, 'lebar' => 38, 'is_active' => true],
            ['nama' => 'D', 'panjang' => null, 'lebar' => 40, 'is_active' => true],
            ['nama' => 'E', 'panjang' => null, 'lebar' => 43, 'is_active' => true],
            ['nama' => 'XS', 'panjang' => 64, 'lebar' => 45, 'is_active' => true],
            ['nama' => 'S', 'panjang' => 66, 'lebar' => 47, 'is_active' => true],
            ['nama' => 'M', 'panjang' => 69, 'lebar' => 49, 'is_active' => true],
            ['nama' => 'L', 'panjang' => 72, 'lebar' => 51, 'is_active' => true],
            ['nama' => 'XL', 'panjang' => 75, 'lebar' => 53, 'is_active' => true],
            ['nama' => '2XL', 'panjang' => 78, 'lebar' => 55, 'is_active' => true],
            ['nama' => '3XL', 'panjang' => 81, 'lebar' => 57, 'is_active' => true],
        ];

        foreach ($ukuran as $data) {
            \App\Models\Ukuran::create($data);
        }
    }
}