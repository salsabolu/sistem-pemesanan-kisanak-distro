<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PembayaranController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        abort_unless($user && $user->hasAnyRole(['pemilik', 'kasir']), 403);

        $pembayaran = Pembayaran::with(['pesanan.produk.warna', 'pesanan.produk.ukuran', 'pembeli'])
            ->where('status', 'Belum Konfirmasi')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('produksi/KonfirmasiPembayaran', [
            'pembayaran' => $pembayaran,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pembeli' => 'required|integer|exists:users,id',
            'id_pesanan' => 'required|integer|exists:pesanan,id',
            'bukti_pembayaran' => 'nullable|string',
            'status' => 'required|in:Menunggu,Belum Konfirmasi,Terkonfirmasi',
        ]);

        Pembayaran::create($validated);

        return redirect()->route('produksi.konfirmasi-pembayaran')
            ->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, Pembayaran $pembayaran)
    {
        $validated = $request->validate([
            'id_pembeli' => 'sometimes|integer|exists:users,id',
            'id_pesanan' => 'sometimes|integer|exists:pesanan,id',
            'bukti_pembayaran' => 'nullable|string',
            'status' => 'sometimes|in:Menunggu,Belum Konfirmasi,Terkonfirmasi',
        ]);

        $pembayaran->update($validated);

        return redirect()->route('produksi.konfirmasi-pembayaran')
            ->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Pembayaran $pembayaran)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->hasAnyRole(['pemilik', 'kasir']), 403);

        $allowedStatuses = $user->hasAnyRole(['pemilik'])
            ? ['Menunggu', 'Belum Konfirmasi', 'Terkonfirmasi']
            : ['Menunggu', 'Belum Konfirmasi'];

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
        ]);

        $pembayaran->update($validated);

        // When pembayaran is confirmed by pemilik, auto-update pesanan status to 'Dalam Produksi'
        if ($validated['status'] === 'Terkonfirmasi' && $user->hasAnyRole(['pemilik']) && $pembayaran->pesanan) {
            $pesanan = $pembayaran->pesanan;
            if ($pesanan->status !== 'Dalam Produksi') {
                $pesanan->update(['status' => 'Dalam Produksi']);
                $pesanan->loadMissing('produk');
                foreach ($pesanan->produk as $produk) {
                    $qty = (int) ($produk->pivot->jumlah ?? 0);
                    if ($qty > 0) {
                        $produk->decrement('stok', $qty, []);
                    }
                }
            }
        }

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    public function uploadBukti(Request $request, Pembayaran $pembayaran)
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('kasir'), 403);

        $validated = $request->validate([
            'bukti_pembayaran' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($pembayaran->bukti_pembayaran) {
            Storage::disk('public')->delete($pembayaran->bukti_pembayaran);
        }

        $path = Storage::disk('public')->putFile('bukti_pembayaran', $validated['bukti_pembayaran']);

        $pembayaran->update([
            'bukti_pembayaran' => $path,
        ]);

        return redirect()->back()->with('success', 'Bukti pembayaran berhasil diunggah.');
    }

    public function destroy(Pembayaran $pembayaran)
    {
        $pembayaran->deleteOrFail();

        return redirect()->route('produksi.konfirmasi-pembayaran')
            ->with('success', 'Pembayaran berhasil dihapus.');
    }
}
