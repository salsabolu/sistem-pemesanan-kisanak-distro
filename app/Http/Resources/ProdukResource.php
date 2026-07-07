<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'harga' => $this->harga,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar ? url('storage/' . $this->gambar) : null,
            'durasi_produksi' => $this->durasi_produksi,
            'is_customizable' => $this->is_customizable,
            'is_active' => $this->is_active,
            'stok' => $this->stok,
            'status' => $this->status,
            'kategori' => $this->kategori ? [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
            ] : null,
            'bahan' => $this->bahan ? [
                'id' => $this->bahan->id,
                'nama' => $this->bahan->nama,
            ] : null,
            'warna' => $this->warna ? [
                'id' => $this->warna->id,
                'nama' => $this->warna->nama,
                'kode' => $this->warna->kode,
            ] : null,
            'ukuran' => $this->ukuran ? [
                'id' => $this->ukuran->id,
                'nama' => $this->ukuran->nama,
            ] : null,
        ];
    }
}
