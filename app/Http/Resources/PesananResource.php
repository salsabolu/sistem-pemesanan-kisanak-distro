<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesananResource extends JsonResource
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
            'id_pembeli' => $this->id_pembeli,
            'pembeli' => $this->whenLoaded('pembeli', function () {
                return [
                    'id' => $this->pembeli->id,
                    'nama' => $this->pembeli->nama,
                    'email' => $this->pembeli->email,
                ];
            }),
            'total' => $this->total,
            'tenggat_waktu' => $this->tenggat_waktu,
            'estimasi_selesai' => $this->estimasi_selesai,
            'status' => $this->status,
            'detail_pesanan' => $this->whenLoaded('detailPesanan', function () {
                return $this->detailPesanan->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'id_produk' => $detail->id_produk,
                        'produk' => $detail->produk ? [
                            'id' => $detail->produk->id,
                            'nama' => $detail->produk->nama,
                            'gambar' => $detail->produk->gambar ? url('storage/' . $detail->produk->gambar) : null,
                        ] : null,
                        'jumlah' => $detail->jumlah,
                        'subtotal' => $detail->subtotal,
                        'desain_custom' => $detail->desain_custom ? true : false,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
