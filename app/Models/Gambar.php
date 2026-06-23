<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gambar extends Model
{
    protected $table = 'gambar';

    public $timestamps = false;

    protected $fillable = [
        'id_desain',
        'file',
    ];

    public function desain()
    {
        return $this->belongsTo(Desain::class, 'id_desain');
    }
}
