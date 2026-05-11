<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UkuranSeeder extends Seeder
{
    public function run(): void
    {
        $ukuran = [
            ['nama' => 'A'],
            ['nama' => 'B'],
            ['nama' => 'C'],
            ['nama' => 'D'],
            ['nama' => 'E'],
            ['nama' => 'XS'],
            ['nama' => 'S'],
            ['nama' => 'M'],
            ['nama' => 'L'],
            ['nama' => 'XL'],
            ['nama' => '2XL'],
            ['nama' => '3XL'],
            ['nama' => '4XL'],
            ['nama' => 'A4'],
            ['nama' => 'A3'],
            ['nama' => '1 METER'],
        ];

        foreach ($ukuran as $data) {
            \App\Models\Ukuran::create($data);
        }
    }
}