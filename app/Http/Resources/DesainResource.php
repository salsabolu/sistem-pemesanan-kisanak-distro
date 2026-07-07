<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesainResource extends JsonResource
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
            'id_detail_pesanan' => $this->id_detail_pesanan,
            'desain_json' => json_decode($this->desain_json),
            'teks' => $this->whenLoaded('teks', function () {
                return $this->teks->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'teks' => $t->teks,
                    ];
                });
            }),
            'gambar' => $this->whenLoaded('gambar', function () {
                return $this->gambar->map(function ($g) {
                    return [
                        'id' => $g->id,
                        'url' => url($g->file),
                    ];
                });
            }),
        ];
    }
}
