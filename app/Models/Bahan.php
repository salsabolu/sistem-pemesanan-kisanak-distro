<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bahan extends Model
{
    protected $table = 'bahan';

    protected function nama(): Attribute
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
        'stok',
        'stok_minimum',
        'durasi_restok',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function produk()
    {
        return $this->hasMany(Produk::class, 'id_bahan');
    }
}
