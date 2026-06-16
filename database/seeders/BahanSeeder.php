<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BahanSeeder extends Seeder
{
    public function run(): void
    {
        $bahan = [
            [
                'id_kategori' => 1,
                'id_warna' => 1,
                'id_ukuran' => 8,
                'nama' => 'KAOS 30S PENDEK HITAM',
                'stok' => 20,
                'stok_minimum' => 2,
                'durasi_produksi' => 5,
                'durasi_restok' => 2880,
                'is_active' => true,
            ],
            [
                'id_kategori' => 1,
                'id_warna' => 1,
                'id_ukuran' => 9,
                'nama' => 'KAOS 30S PENDEK HITAM',
                'stok' => 30,
                'stok_minimum' => 2,
                'durasi_produksi' => 5,
                'durasi_restok' => 2880,
                'is_active' => true,
            ],
            [
                'id_kategori' => 2,
                'id_warna' => 2,
                'id_ukuran' => 8,
                'nama' => 'KAOS 30S PANJANG PUTIH', 
                'stok' => 20,
                'stok_minimum' => 2,
                'durasi_produksi' => 5,
                'durasi_restok' => 2880,
                'is_active' => true,
            ],
            [
                'id_kategori' => 2,
                'id_warna' => 2,
                'id_ukuran' => 9,
                'nama' => 'KAOS 30S PANJANG PUTIH', 
                'stok' => 30,
                'stok_minimum' => 2,
                'durasi_produksi' => 5,
                'durasi_restok' => 2880,
                'is_active' => true,
            ],
            
        ];

        foreach ($bahan as $data) {
            \App\Models\Bahan::create($data);
        }
    }
}