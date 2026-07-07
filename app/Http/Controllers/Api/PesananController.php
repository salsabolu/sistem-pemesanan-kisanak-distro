<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use App\Http\Resources\PesananResource;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pesanan::with(['pembeli', 'detailPesanan.produk']);

        if (! $request->user()->hasRole('admin')) {
            $query->where('id_pembeli', $request->user()->id);
        }

        $pesanan = $query->latest()->paginate(10);

        return PesananResource::collection($pesanan);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Fitur checkout API, tapi sementara kita biarkan sederhana
        $validated = $request->validate([
            'total' => 'required|numeric|min:0',
            'tenggat_waktu' => 'required|date',
            'status' => 'required|string',
        ]);

        $validated['id_pembeli'] = $request->user()->id;
        
        $pesanan = Pesanan::create($validated);

        return new PesananResource($pesanan->load(['pembeli', 'detailPesanan.produk']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Pesanan $pesanan)
    {
        if (! $request->user()->hasRole('admin') && $pesanan->id_pembeli !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new PesananResource($pesanan->load(['pembeli', 'detailPesanan.produk']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'estimasi_selesai' => 'nullable|date',
        ]);

        $pesanan->update($validated);

        return new PesananResource($pesanan->load(['pembeli', 'detailPesanan.produk']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pesanan $pesanan)
    {
        $pesanan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dihapus'
        ]);
    }
}
