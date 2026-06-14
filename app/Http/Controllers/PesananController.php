<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PesananController extends Controller
{
    public function index()
    {
        $pesanan = Pesanan::with(['pembeli', 'produk.warna', 'produk.ukuran', 'pembayaran'])
        ->orderBy('tenggat_waktu', 'asc') // EDD: Earliest Due Date
            ->orderBy('created_at', 'desc') // FCFS
            ->paginate(10);

        return Inertia::render('pesanan/DaftarPesanan', [
            'pesanan' => $pesanan,
        ]);
    }

    public function antreanProduksi()
    {
        // Auto-promote: pesanan dengan pembayaran terkonfirmasi → Dalam Produksi
        Pesanan::whereNull('status')
            ->whereHas('pembayaran', function ($q) {
                $q->where('status', '=', 'Terkonfirmasi');
            })
            ->update(['status' => 'Dalam Produksi']);

        $pesanan = Pesanan::with(['pembeli', 'produk.warna', 'produk.ukuran', 'pembayaran'])
            ->where('status', '=', 'Dalam Produksi')
            ->orderBy('tenggat_waktu', 'asc') // EDD: Earliest Due Date
            ->orderBy('created_at', 'asc') // FCFS: First Come First Served
            ->paginate(10);

        return Inertia::render('produksi/AntreanProduksi', [
            'pesanan' => $pesanan,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pembeli' => 'required|integer|exists:users,id',
            'total' => 'required|integer|min:0',
            'status' => 'nullable|in:Dalam Produksi,Selesai,Dibatalkan',
            'tenggat_waktu' => 'nullable|date',
            'estimasi_selesai' => 'nullable|date',
        ]);

        Pesanan::create($validated);

        return redirect()->back()->with('success', 'Pesanan berhasil ditambahkan.');
    }

    public function update(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'id_pembeli' => 'sometimes|integer|exists:users,id',
            'total' => 'sometimes|integer|min:0',
            'status' => 'sometimes|nullable|in:Dalam Produksi,Selesai,Dibatalkan',
            'tenggat_waktu' => 'sometimes|nullable|date',
            'estimasi_selesai' => 'sometimes|nullable|date',
        ]);

        $oldStatus = $pesanan->status;

        if ($oldStatus === 'Selesai' && isset($validated['status']) && $validated['status'] !== 'Selesai') {
            return redirect()->back()->with('error', 'Pesanan sudah selesai dan tidak dapat diubah kembali.');
        }

        $pesanan->update($validated);

        if ($oldStatus !== 'Dalam Produksi' && isset($validated['status']) && $validated['status'] === 'Dalam Produksi') {
            $pesanan->loadMissing('produk.bahan');
            foreach ($pesanan->produk as $produk) {
                $qty = (int) ($produk->pivot->jumlah ?? 0);
                if ($qty > 0 && $produk->bahan) {
                    $produk->bahan->decrement('stok', $qty);
                }
            }
        }

        return redirect()->back()->with('success', 'Pesanan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status' => 'required|in:Dalam Produksi,Selesai,Dibatalkan',
        ]);

        $oldStatus = $pesanan->status;

        if ($oldStatus === 'Selesai' && $validated['status'] !== 'Selesai') {
            return redirect()->back()->with('error', 'Pesanan sudah selesai dan tidak dapat diubah kembali.');
        }

        $pesanan->update($validated);

        if ($oldStatus !== 'Dalam Produksi' && $validated['status'] === 'Dalam Produksi') {
            $pesanan->loadMissing('produk.bahan');
            foreach ($pesanan->produk as $produk) {
                $qty = (int) ($produk->pivot->jumlah ?? 0);
                if ($qty > 0 && $produk->bahan) {
                    $produk->bahan->decrement('stok', $qty);
                }
            }
        }

        return redirect()->back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function destroy(Pesanan $pesanan)
    {
        $pesanan->deleteOrFail();

        return redirect()->back()->with('success', 'Pesanan berhasil dihapus.');
    }
}
