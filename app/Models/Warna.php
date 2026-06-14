<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Warna extends Model
{
    protected $table = 'warna';

    protected function nama(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    protected $fillable = [
        'nama',
        'kode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bahan()
    {
        return $this->hasMany(Bahan::class, 'id_warna');
    }
}
