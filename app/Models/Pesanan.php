<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'id_pembeli',
        'total',
        'prioritas',
        'status',
        'tenggat_waktu',
        'estimasi_selesai',
    ];

    protected $casts = [
        'tenggat_waktu' => 'date',
        'estimasi_selesai' => 'date',
    ];

    public function pembeli()
    {
        return $this->belongsTo(User::class, 'id_pembeli');
    }

    public function produk()
    {
        return $this->belongsToMany(Produk::class, 'detail_pesanan', 'id_pesanan', 'id_produk')
            ->withPivot(['jumlah', 'subtotal']);
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan');
    }
}
