<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WarnaSeeder extends Seeder
{
    public function run(): void
    {
        $warna = [
            ['nama' => 'HITAM', 'kode' => '0,0,0,100', 'is_active' => true],
            ['nama' => 'PUTIH', 'kode' => '0,0,0,0', 'is_active' => true],
            ['nama' => 'NAVY', 'kode' => '100,100,0,50', 'is_active' => true],
            ['nama' => 'MERAH CABE', 'kode' => '0,100,100,0', 'is_active' => true],
            ['nama' => 'PINK FANTA', 'kode' => '0,100,0,0', 'is_active' => true],
            ['nama' => 'HIJAU PUPUS', 'kode' => '60,0,100,0', 'is_active' => true],
            ['nama' => 'HIJAU TOSCA', 'kode' => '100,0,50,0', 'is_active' => true],
            ['nama' => 'HIJAU SAGE', 'kode' => '40,20,50,10', 'is_active' => true],
            ['nama' => 'KUNING KENARI', 'kode' => '0,10,100,0', 'is_active' => true],
            ['nama' => 'COKLAT SUSU', 'kode' => '10,30,50,10', 'is_active' => true],
            ['nama' => 'COKLAT KOPI', 'kode' => '30,60,80,40', 'is_active' => true],
            ['nama' => 'TURKISH', 'kode' => '100,30,0,0', 'is_active' => true],
            ['nama' => 'TURKISH MUDA', 'kode' => '50,15,0,0', 'is_active' => true],
        ];

        foreach ($warna as $data) {
            \App\Models\Warna::create($data);
        }
    }
}
