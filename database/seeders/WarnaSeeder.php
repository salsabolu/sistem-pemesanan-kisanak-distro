<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WarnaSeeder extends Seeder
{
    public function run(): void
    {
        $warna = [
            ['nama' => 'HITAM'],
            ['nama' => 'PUTIH'],
            ['nama' => 'NAVY'],
            ['nama' => 'MERAH CABE'],
            ['nama' => 'PINK FANTA'],
            ['nama' => 'HIJAU PUPUS'],
            ['nama' => 'HIJAU TOSCA'],
            ['nama' => 'HIJAU SAGE'],
            ['nama' => 'KUNING KENARI'],
            ['nama' => 'COKLAT SUSU'],
            ['nama' => 'COKLAT KOPI'],
            ['nama' => 'TURKISH'],
            ['nama' => 'TURKISH MUDA'],
        ];

        foreach ($warna as $data) {
            \App\Models\Warna::create($data);
        }
    }
}
