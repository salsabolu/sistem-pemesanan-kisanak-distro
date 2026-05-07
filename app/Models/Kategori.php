<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Kategori extends Model
{
    protected $table = 'kategori';

    protected function nama(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    protected $fillable = [
        'nama',
    ];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'id_kategori');
    }
}
