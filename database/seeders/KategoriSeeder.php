<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['nama' => 'KAOS'],
            ['nama' => 'KAOS SABLON'],
            ['nama' => 'CETAK DTF'],
        ];

        foreach ($kategori as $data) {
            \App\Models\Kategori::create($data);
        }
    }
}
