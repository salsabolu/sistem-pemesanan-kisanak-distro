<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distro extends Model
{
    protected $table = 'distro';

    public $timestamps = true; // Since the migration includes $table->timestamps()

    protected $fillable = [
        'nama',
        'alamat',
        'jam_buka',
        'jam_tutup',
        'hari_buka',
        'hari_tutup',
        'rekening_bca',
        'rekening_bri',
        'whatsapp',
        'instagram',
        'tiktok',
    ];
}
