<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'id_pembeli',
        'id_pesanan',
        'bukti_pembayaran',
        'status',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan');
    }

    public function pembeli()
    {
        return $this->belongsTo(User::class, 'id_pembeli');
    }
}
