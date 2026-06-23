<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teks extends Model
{
    protected $table = 'teks';

    public $timestamps = false;

    protected $fillable = [
        'id_desain',
        'teks',
    ];

    public function desain()
    {
        return $this->belongsTo(Desain::class, 'id_desain');
    }
}
