<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Warna;
use App\Models\Ukuran;
use App\Models\Bahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function index()
    {
        $produks = Produk::with(['bahan.kategori', 'bahan.warna', 'bahan.ukuran'])->paginate(10);
        $bahan = Bahan::with(['kategori', 'warna', 'ukuran'])->where('is_active', true)->get();

        return Inertia::render('master/Produk', [
            'produks' => $produks,
            'bahan'   => $bahan,
        ]);
    }

    public function katalog(Request $request)
    {
        $query = Produk::with(['kategori', 'warna', 'ukuran', 'bahan'])
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(produk.nama) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('kategori', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(kategori.nama) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $allProduks = $query->get();

        // Group products by normalized product name so catalog shows one card per unique product name
        $grouped = $allProduks
            ->groupBy(function ($produk) {
                return mb_strtolower(trim($produk->nama), 'UTF-8');
            })
            ->map(function ($variants) {
                $first = $variants->first();
                return [
                    'id' => $first->id,
                    'nama' => trim($first->nama),
                    'kategori' => $first->kategori?->nama ?? '',
                    'harga_min' => $variants->min('harga'),
                    'harga_max' => $variants->max('harga'),
                    'stok' => $variants->sum(fn($v) => $v->bahan?->stok ?? 0),
                    'stok_minimum' => $variants->sum(fn($v) => $v->bahan?->stok_minimum ?? 0),
                    'gambar' => ($variants->firstWhere('gambar', '!=', '-')?->gambar) ?? $first->gambar,
                    'status' => $first->is_active ? 'Aktif' : 'Non-Aktif',
                    'warna' => $first->warna,
                    'ukuran' => $first->ukuran,
                ];
            })
            ->values();

        return Inertia::render('Katalog', [
            'produks' => $grouped,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Produk $produk)
    {
        if (!Auth::check()) {
            return redirect()->route('katalog')->with('openLogin', true);
        }

        $produk->load(['bahan.kategori', 'bahan.warna', 'bahan.ukuran']);

        // Get variants with the exact same product name
        $variants = Produk::where('nama', '=', $produk->nama)
            ->where('is_active', true)
            ->with(['warna', 'ukuran'])
            ->get();

        // Get all unique colors and sizes for products with the same name
        $warnaOptions = $variants->pluck('warna.nama')->filter()->unique()->values();
        $ukuranOptions = $variants->pluck('ukuran.nama')->filter()->unique()->values();

        return Inertia::render('produk/DetailProduk', [
            'produk' => $produk,
            'variants' => $variants,
            'warnaOptions' => $warnaOptions,
            'ukuranOptions' => $ukuranOptions,
        ]);
    }

    public function kustomisasi(Produk $produk)
    {
        if (!Auth::check()) {
            return redirect()->route('katalog')->with('openLogin', true);
        }

        if (!$produk->is_customizable) {
            abort(404, 'Produk ini tidak dapat dikustomisasi.');
        }

        $produk->load(['bahan.kategori', 'bahan.warna', 'bahan.ukuran']);

        // Ambil semua warna aktif dari tabel warna untuk palet kustomisasi
        $warnaOptions = Warna::where('is_active', true)
            ->select(['id', 'nama', 'kode'])
            ->get()
            ->map(fn($w) => [
                'id'   => $w->id,
                'nama' => $w->nama,
                'kode' => $w->kode, // Format CMYK: "C,M,Y,K"
            ]);

        return Inertia::render('produk/KustomisasiProduk', [
            'produk'       => $produk,
            'warnaOptions' => $warnaOptions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_bahan'  => 'required|integer|exists:bahan,id',
            'nama'      => 'required|string|max:255',
            'harga'     => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'durasi_produksi' => 'required|integer|min:0',
            'is_customizable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $validated['gambar'] = '/storage/' . $path;
        } else {
            $validated['gambar'] = '-';
        }

        $validated['deskripsi'] = $validated['deskripsi'] ?: '-';
        $validated['is_customizable'] = filter_var($request->input('is_customizable', false), FILTER_VALIDATE_BOOLEAN);
        $validated['is_active'] = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);

        Produk::create($validated);

        return redirect()->route('master.produk')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'id_bahan'  => 'required|integer|exists:bahan,id',
            'nama'      => 'required|string|max:255',
            'harga'     => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'durasi_produksi' => 'required|integer|min:0',
            'is_customizable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $validated['gambar'] = '/storage/' . $path;
        } else {
            unset($validated['gambar']);
        }

        $validated['deskripsi'] = $validated['deskripsi'] ?: '-';
        $validated['is_customizable'] = filter_var($request->input('is_customizable', $produk->is_customizable), FILTER_VALIDATE_BOOLEAN);
        $validated['is_active'] = filter_var($request->input('is_active', $produk->is_active), FILTER_VALIDATE_BOOLEAN);

        $produk->update([
            'id_bahan'  => $validated['id_bahan'],
            'nama'      => $validated['nama'],
            'harga'     => $validated['harga'],
            'deskripsi' => $validated['deskripsi'],
            'gambar'    => $validated['gambar'] ?? $produk->gambar,
            'durasi_produksi' => $validated['durasi_produksi'],
            'is_customizable' => $validated['is_customizable'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('master.produk')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $produk->update(['is_active' => $validated['is_active']]);

        return redirect()->back()->with('success', 'Status produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $produk->deleteOrFail();

        return redirect()->route('master.produk')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function stokMenipis(Request $request)
    {
        $bahan = Bahan::with(['kategori', 'warna', 'ukuran'])
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->where('is_active', true)
            ->orderBy('stok', 'asc')
            ->paginate(10);

        return Inertia::render('produksi/StokMenipis', [
            'produks' => $bahan
        ]);
    }
}
