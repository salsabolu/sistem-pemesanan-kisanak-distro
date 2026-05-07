<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Produk extends Model
{
    protected $table = 'produk';

    protected function nama(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    protected function satuan(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    protected $fillable = [
        'id_kategori',
        'id_warna',
        'id_ukuran',
        'nama',
        'satuan',
        'harga',
        'stok',
        'stok_minimum',
        'durasi_produksi',
        'durasi_restok',
        'deskripsi',
        'gambar',
        'status',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function warna()
    {
        return $this->belongsTo(Warna::class, 'id_warna');
    }

    public function ukuran()
    {
        return $this->belongsTo(Ukuran::class, 'id_ukuran');
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_produk');
    }
}
