<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['nama' => 'KAOS 30S PENDEK', 'is_active' => true],
            ['nama' => 'KAOS 30S PANJANG', 'is_active' => true],
            ['nama' => 'KAOS 24S PENDEK', 'is_active' => true],
            ['nama' => 'KAOS 24S PANJANG', 'is_active' => true],
            ['nama' => 'KAOS 20S PENDEK', 'is_active' => true],
            ['nama' => 'KAOS 20S PANJANG', 'is_active' => true],
        ];

        foreach ($kategori as $data) {
            \App\Models\Kategori::create($data);
        }
    }
}
