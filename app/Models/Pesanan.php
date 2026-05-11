<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'id_pembeli',
        'id_produk',
        'jumlah',
        'total_harga',
        'prioritas',
        'tenggat_waktu',
        'catatan',
        'status',
        'estimasi_selesai',
    ];

    public function pembeli()
    {
        return $this->belongsTo(User::class, 'id_pembeli');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan');
    }
}
