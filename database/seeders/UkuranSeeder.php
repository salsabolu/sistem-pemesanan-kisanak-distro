<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UkuranSeeder extends Seeder
{
    public function run(): void
    {
        $ukuran = [
            ['nama' => 'A', 'is_active' => true],
            ['nama' => 'B', 'is_active' => true],
            ['nama' => 'C', 'is_active' => true],
            ['nama' => 'D', 'is_active' => true],
            ['nama' => 'E', 'is_active' => true],
            ['nama' => 'XS', 'is_active' => true],
            ['nama' => 'S', 'is_active' => true],
            ['nama' => 'M', 'is_active' => true],
            ['nama' => 'L', 'is_active' => true],
            ['nama' => 'XL', 'is_active' => true],
            ['nama' => '2XL', 'is_active' => true],
            ['nama' => '3XL', 'is_active' => true],
            ['nama' => '4XL', 'is_active' => true],
        ];

        foreach ($ukuran as $data) {
            \App\Models\Ukuran::create($data);
        }
    }
}