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
            ->orderByRaw("case when prioritas = 'Tinggi' then 0 else 1 end")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('pesanan/DaftarPesanan', [
            'pesanan' => $pesanan,
        ]);
    }

    public function pesananBaru()
    {
        $pesanan = Pesanan::with(['pembeli', 'produk.warna', 'produk.ukuran', 'pembayaran'])
            ->where('status', 'Pesanan Baru')
            ->orderByRaw("case when prioritas = 'Tinggi' then 0 else 1 end")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('produksi/PesananBaru', [
            'pesanan' => $pesanan,
        ]);
    }

    public function antreanProduksi()
    {
        Pesanan::where('status', '=', 'Pesanan Baru', 'and')
            ->whereHas('pembayaran', function ($q) {
                $q->where('status', '=', 'Terkonfirmasi', 'and');
            })
            ->update(['status' => 'Dalam Produksi']);

        $pesanan = Pesanan::with(['pembeli', 'produk.warna', 'produk.ukuran', 'pembayaran'])
            ->where('status', 'Dalam Produksi')
            ->orderByRaw("case when prioritas = 'Tinggi' then 0 else 1 end") // Priority Scheduling
            ->orderBy('tenggat_waktu', 'asc') // Earliest Due Date
            ->paginate(10);

        return Inertia::render('produksi/AntreanProduksi', [
            'pesanan' => $pesanan,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pembeli' => 'required|integer|exists:users,id',
            'id_produk' => 'required|integer|exists:produk,id',
            'jumlah' => 'required|integer|min:1',
            'total_harga' => 'required|numeric|min:0',
            'prioritas' => 'required|in:Normal,Tinggi',
            'tenggat_waktu' => 'nullable|date',
            'catatan' => 'nullable|string',
            'status' => 'required|in:Pesanan Baru,Dalam Produksi,Selesai,Dibatalkan',
            'estimasi_selesai' => 'nullable|date',
        ]);

        Pesanan::create($validated);

        return redirect()->back()->with('success', 'Pesanan berhasil ditambahkan.');
    }

    public function update(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'id_pembeli' => 'sometimes|integer|exists:users,id',
            'id_produk' => 'sometimes|integer|exists:produk,id',
            'jumlah' => 'sometimes|integer|min:1',
            'total_harga' => 'sometimes|numeric|min:0',
            'prioritas' => 'sometimes|in:Normal,Tinggi',
            'tenggat_waktu' => 'nullable|date',
            'catatan' => 'nullable|string',
            'status' => 'sometimes|in:Pesanan Baru,Dalam Produksi,Selesai,Dibatalkan',
            'estimasi_selesai' => 'nullable|date',
        ]);

        $oldStatus = $pesanan->status;

        if ($oldStatus === 'Selesai' && isset($validated['status']) && $validated['status'] !== 'Selesai') {
            return redirect()->back()->with('error', 'Pesanan sudah selesai dan tidak dapat diubah kembali.');
        }

        $pesanan->update($validated);

        if ($oldStatus !== 'Dalam Produksi' && isset($validated['status']) && $validated['status'] === 'Dalam Produksi') {
            if ($pesanan->produk) {
                $pesanan->produk->decrement('stok', $pesanan->jumlah, []);
            }
        }

        return redirect()->back()->with('success', 'Pesanan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pesanan Baru,Dalam Produksi,Selesai,Dibatalkan',
        ]);

        $oldStatus = $pesanan->status;

        if ($oldStatus === 'Selesai' && $validated['status'] !== 'Selesai') {
            return redirect()->back()->with('error', 'Pesanan sudah selesai dan tidak dapat diubah kembali.');
        }

        $pesanan->update($validated);

        if ($oldStatus !== 'Dalam Produksi' && $validated['status'] === 'Dalam Produksi') {
            if ($pesanan->produk) {
                $pesanan->produk->decrement('stok', $pesanan->jumlah, []);
            }
        }

        return redirect()->back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function updatePrioritas(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'prioritas' => 'required|in:Normal,Tinggi',
        ]);

        $pesanan->update($validated);

        return redirect()->back()->with('success', 'Prioritas pesanan berhasil diperbarui.');
    }

    public function destroy(Pesanan $pesanan)
    {
        $pesanan->deleteOrFail();

        return redirect()->back()->with('success', 'Pesanan berhasil dihapus.');
    }
}
