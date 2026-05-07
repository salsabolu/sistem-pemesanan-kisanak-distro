<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KeranjangController extends Controller
{
    public function index()
    {
        $userId = auth()->id() ?? User::first()?->id ?? 1;

        // Get orders that are "Dalam Produksi" and have confirmed payment
        $pesanan = Pesanan::with(['produk.warna', 'produk.ukuran', 'pembayaran'])
            ->where('status', 'Dalam Produksi')
            ->whereHas('pembayaran', function ($q) {
                $q->where('status', 'Terkonfirmasi');
            })
            ->where('id_pembeli', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Keranjang', [
            'pesananAktif' => $pesanan,
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|integer|exists:produk,id',
            'items.*.color' => 'nullable|string',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'tenggat_waktu' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        $userId = auth()->id() ?? User::first()?->id ?? 1;

        foreach ($validated['items'] as $item) {
            $produk = Produk::findOrFail($item['productId']);
            $totalHarga = $item['unitPrice'] * $item['quantity'];

            $pesanan = Pesanan::create([
                'id_pembeli' => $userId,
                'id_produk' => $item['productId'],
                'jumlah' => $item['quantity'],
                'total_harga' => $totalHarga,
                'prioritas' => 'Normal',
                'tenggat_waktu' => $validated['tenggat_waktu'],
                'catatan' => $validated['catatan'] ?? '-',
                'status' => 'Pesanan Baru',
                'estimasi_selesai' => $validated['tenggat_waktu'],
            ]);

            // Create pembayaran record for the new order
            Pembayaran::create([
                'id_pembeli' => $userId,
                'id_pesanan' => $pesanan->id,
                'bukti_pembayaran' => null,
                'status' => 'Menunggu',
            ]);
        }

        return redirect()->route('keranjang')
            ->with('success', 'Pesanan berhasil dibuat!');
    }
}
