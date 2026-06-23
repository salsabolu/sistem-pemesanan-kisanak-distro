<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desain extends Model
{
    protected $table = 'desain';

    public $timestamps = false;

    protected $fillable = [
        'id_detail_pesanan',
        'desain_json',
    ];

    public function detailPesanan()
    {
        return $this->belongsTo(DetailPesanan::class, 'id_detail_pesanan');
    }

    public function teks()
    {
        return $this->hasMany(Teks::class, 'id_desain');
    }

    public function gambar()
    {
        return $this->hasMany(Gambar::class, 'id_desain');
    }
}
