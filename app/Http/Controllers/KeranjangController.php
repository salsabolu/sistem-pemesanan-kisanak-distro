<?php

namespace App\Http\Controllers;

use App\Models\Desain;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Teks;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Distro;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KeranjangController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Get orders that are "Dalam Produksi" and have confirmed payment
        $pesanan = Pesanan::with(['produk.warna', 'produk.ukuran', 'pembayaran', 'detailPesanan.desain.teks', 'detailPesanan.desain.gambar'])
            ->where('status', 'Dalam Produksi')
            ->whereHas('pembayaran', function ($q) {
                $q->where('status', 'Terkonfirmasi');
            })
            ->where('id_pembeli', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $distro = Distro::first();

        return Inertia::render('Keranjang', [
            'pesananAktif' => $pesanan,
            'distro' => $distro,
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'tenggat_waktu' => 'required|date',
            'bukti_pembayaran' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'rekening' => 'required|in:BCA,BRI',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|integer|exists:produk,id',
            'items.*.color' => 'nullable|string',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.desainId' => 'nullable|integer|exists:desain,id',
            'items.*.customText' => 'nullable|string|max:255',
            'items.*.bulkData' => 'nullable|array',
            'items.*.bulkData.*.teksKustom' => 'nullable|string|max:255',
            'items.*.bulkData.*.logoPath' => 'nullable|string',
        ]);

        $userId = Auth::id();

        $total = 0;
        foreach ($validated['items'] as $item) {
            $subtotal = (int) round(((float) $item['unitPrice']) * ((int) $item['quantity']));
            $total += $subtotal;
        }

        $tenggatWaktu = $validated['tenggat_waktu'];

        // Save the proof of payment uploaded by Pembeli
        $path = null;
        if ($request->hasFile('bukti_pembayaran')) {
            $path = Storage::disk('public')->putFile('bukti_pembayaran', $validated['bukti_pembayaran']);
        }

        // Create the order with initial status null (new order awaiting confirmation)
        $pesanan = Pesanan::create([
            'id_pembeli' => $userId,
            'total' => $total,
            'status' => null, // Initial state before payment is Terkonfirmasi
            'tenggat_waktu' => $tenggatWaktu,
            'estimasi_selesai' => null, // Calculated later upon confirmation
        ]);

        // Track whether the original desain has been linked (for the first item)
        $linkedDesainIds = [];
        $originalTextsCache = [];
        $originalGambarsCache = [];

        foreach ($validated['items'] as $itemIndex => $item) {
            $produk = Produk::findOrFail($item['productId']);
            $jumlah = (int) $item['quantity'];
            $subtotal = (int) round(((float) $item['unitPrice']) * $jumlah);

            $pesanan->produk()->attach($produk->id, [
                'jumlah' => $jumlah,
                'subtotal' => $subtotal,
            ]);

            // Read bulkData directly from request to avoid loss in validation output
            $bulkData = $request->input("items.{$itemIndex}.bulkData", []);
            // Hubungkan desain ke detail_pesanan jika ada
            if (!empty($item['desainId'])) {
                $detailPesanan = $pesanan->detailPesanan()
                    ->where('id_produk', $produk->id)
                    ->latest('id')
                    ->first();

                if ($detailPesanan) {
                    $desainId = $item['desainId'];
                    $targetDesainId = $desainId;

                    if (!in_array($desainId, $linkedDesainIds)) {
                        // First time seeing this desainId, link the original desain
                        $originalDesain = Desain::with(['teks', 'gambar'])->find($desainId);
                        if ($originalDesain) {
                            $originalTextsCache[$desainId] = $originalDesain->teks;
                            $originalGambarsCache[$desainId] = $originalDesain->gambar;
                        }
                        
                        Desain::where('id', $desainId)->update([
                            'id_detail_pesanan' => $detailPesanan->id,
                        ]);
                        $linkedDesainIds[] = $desainId;
                    } else {
                        // This desainId has been used by ANOTHER variant in this order.
                        // We must duplicate the original desain (using cached original data) for this variant.
                        $originalDesain = Desain::find($desainId);
                        if ($originalDesain) {
                            $newDesain = Desain::create([
                                'id_detail_pesanan' => $detailPesanan->id,
                                'desain_json' => $originalDesain->desain_json,
                                'file_excel' => $originalDesain->file_excel,
                            ]);
                            $targetDesainId = $newDesain->id;

                            // Copy ORIGINAL texts from cache
                            if (isset($originalTextsCache[$desainId])) {
                                foreach ($originalTextsCache[$desainId] as $teks) {
                                    Teks::create([
                                        'id_desain' => $targetDesainId,
                                        'teks' => $teks->teks,
                                    ]);
                                }
                            }
                            
                            // Copy ORIGINAL gambars from cache
                            if (isset($originalGambarsCache[$desainId])) {
                                foreach ($originalGambarsCache[$desainId] as $gambar) {
                                    \App\Models\Gambar::create([
                                        'id_desain' => $targetDesainId,
                                        'file' => $gambar->file,
                                    ]);
                                }
                            }
                        }
                    }

                    // Process normal custom properties (if any)
                    if (!empty($item['customText'])) {
                        Teks::create([
                            'id_desain' => $targetDesainId,
                            'teks' => $item['customText'],
                        ]);
                    }
                    if (!empty($item['customLogoPath'])) {
                        \App\Models\Gambar::create([
                            'id_desain' => $targetDesainId,
                            'file' => $item['customLogoPath'],
                        ]);
                    }

                    // Process bulkData (array of custom texts and logos)
                    if (!empty($bulkData) && is_array($bulkData)) {
                        foreach ($bulkData as $bd) {
                            if (!empty($bd['teksKustom'])) {
                                Teks::create([
                                    'id_desain' => $targetDesainId,
                                    'teks' => $bd['teksKustom'],
                                ]);
                            }
                            if (!empty($bd['logoPath'])) {
                                \App\Models\Gambar::create([
                                    'id_desain' => $targetDesainId,
                                    'file' => $bd['logoPath'],
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Create pembayaran record with status 'Belum Konfirmasi' (uploaded but not yet verified)
        Pembayaran::create([
            'id_pembeli' => $userId,
            'id_pesanan' => $pesanan->id,
            'bukti_pembayaran' => $path,
            'rekening' => $validated['rekening'],
            'status' => 'Belum Konfirmasi',
        ]);

        return redirect()->route('keranjang')
            ->with('success', 'Pesanan berhasil dibuat!');
    }
}
