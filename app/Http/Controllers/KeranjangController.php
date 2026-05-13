<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KeranjangController extends Controller
{
    public function index()
    {
        $userId = Auth::id() ?? User::query()->first()?->getKey() ?? 1;

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
            'tenggat_waktu' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|integer|exists:produk,id',
            'items.*.color' => 'nullable|string',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id() ?? User::query()->first()?->getKey() ?? 1;

        $total = 0;
        foreach ($validated['items'] as $item) {
            $subtotal = (int) round(((float) $item['unitPrice']) * ((int) $item['quantity']));
            $total += $subtotal;
        }

        $tenggatWaktu = $validated['tenggat_waktu'];

        $pesanan = Pesanan::create([
            'id_pembeli' => $userId,
            'total' => $total,
            'prioritas' => 'Normal',
            'status' => 'Pesanan Baru',
            'tenggat_waktu' => $tenggatWaktu,
            'estimasi_selesai' => $tenggatWaktu,
        ]);

        foreach ($validated['items'] as $item) {
            $produk = Produk::findOrFail($item['productId']);
            $jumlah = (int) $item['quantity'];
            $subtotal = (int) round(((float) $item['unitPrice']) * $jumlah);

            $pesanan->produk()->attach($produk->id, [
                'jumlah' => $jumlah,
                'subtotal' => $subtotal,
            ]);
        }

        // Create pembayaran record for the new order
        Pembayaran::create([
            'id_pembeli' => $userId,
            'id_pesanan' => $pesanan->id,
            'bukti_pembayaran' => null,
            'status' => 'Menunggu',
        ]);

        return redirect()->route('keranjang')
            ->with('success', 'Pesanan berhasil dibuat!');
    }
}
