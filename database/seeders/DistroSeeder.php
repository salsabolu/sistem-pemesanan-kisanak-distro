<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DistroSeeder extends Seeder
{
    public function run(): void
    {
        $distro = [
            [
                'nama' => 'Kisanak Distro',
                'alamat' => 'Jl. Sersan Harun No.23, Kartoharjo, Kecamatan Nganjuk, Kabupaten Nganjuk, Jawa Timur 64416',
                'jam_buka' => '07:30',
                'jam_tutup' => '17:00',
                'hari_buka' => 'Senin - Sabtu',
                'hari_tutup' => 'Minggu',
                'whatsapp' => '081233843999',
                'instagram' => 'https://www.instagram.com/kisanakdistro?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==',
                'tiktok' => 'https://www.tiktok.com/@kisanak.distro?is_from_webapp=1&sender_device=pc'
            ],
        ];

        foreach ($distro as $data) {
            \App\Models\Distro::create($data);
        }
    }
}