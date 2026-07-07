<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use App\Http\Resources\ProdukResource;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Produk::with(['bahan', 'kategori', 'warna', 'ukuran']);

        // Jika user, mungkin hanya tampilkan yang aktif
        if (! $request->user() || ! $request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        }

        $produk = $query->paginate(10);

        return ProdukResource::collection($produk);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Akan diamankan dengan middleware role:admin di routes
        $validated = $request->validate([
            'id_bahan' => 'required|exists:bahan,id',
            'nama' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'durasi_produksi' => 'nullable|integer|min:1',
            'is_customizable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $produk = Produk::create($validated);

        return new ProdukResource($produk->load(['bahan', 'kategori', 'warna', 'ukuran']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Produk $produk)
    {
        return new ProdukResource($produk->load(['bahan', 'kategori', 'warna', 'ukuran']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'id_bahan' => 'sometimes|exists:bahan,id',
            'nama' => 'sometimes|string|max:255',
            'harga' => 'sometimes|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'durasi_produksi' => 'nullable|integer|min:1',
            'is_customizable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $produk->update($validated);

        return new ProdukResource($produk->load(['bahan', 'kategori', 'warna', 'ukuran']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produk $produk)
    {
        $produk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }
}
