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

    protected $fillable = [
        'id_bahan',
        'nama',
        'harga',
        'deskripsi',
        'gambar',
        'durasi_produksi',
        'is_customizable',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_customizable' => 'boolean',
    ];

    protected $appends = [
        'stok',
        'stok_minimum',
        'durasi_restok',
        'status',
    ];

    public function getStokAttribute()
    {
        return $this->bahan?->stok ?? 0;
    }

    public function getStokMinimumAttribute()
    {
        return $this->bahan?->stok_minimum ?? 0;
    }

    public function getDurasiRestokAttribute()
    {
        return $this->bahan?->durasi_restok ?? 0;
    }

    public function getStatusAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Non-Aktif';
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'id_bahan');
    }

    public function kategori()
    {
        return $this->hasOneThrough(Kategori::class, Bahan::class, 'id', 'id', 'id_bahan', 'id_kategori');
    }

    public function warna()
    {
        return $this->hasOneThrough(Warna::class, Bahan::class, 'id', 'id', 'id_bahan', 'id_warna');
    }

    public function ukuran()
    {
        return $this->hasOneThrough(Ukuran::class, Bahan::class, 'id', 'id', 'id_bahan', 'id_ukuran');
    }

    public function pesanan()
    {
        return $this->belongsToMany(Pesanan::class, 'detail_pesanan', 'id_produk', 'id_pesanan')
            ->withPivot(['jumlah', 'subtotal']);
    }
}
