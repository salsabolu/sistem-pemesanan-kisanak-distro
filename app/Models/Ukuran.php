<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ukuran extends Model
{
    protected $table = 'ukuran';

    protected function nama(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    protected $fillable = [
        'nama',
        'panjang',
        'lebar',
        'is_active',
    ];

    protected $casts = [
        'panjang'   => 'integer',
        'lebar'     => 'integer',
        'is_active' => 'boolean',
    ];

    public function bahan()
    {
        return $this->hasMany(Bahan::class, 'id_ukuran');
    }
}
