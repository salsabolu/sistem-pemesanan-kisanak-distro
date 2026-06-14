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
        $produks = Produk::with(['kategori', 'warna', 'ukuran', 'bahan'])->paginate(10);
        $kategori = Kategori::all();
        $warna = Warna::all();
        $ukuran = Ukuran::all();

        return Inertia::render('master/Produk', [
            'produks' => $produks,
            'kategori' => $kategori,
            'warna' => $warna,
            'ukuran' => $ukuran,
        ]);
    }

    public function katalog(Request $request)
    {
        $query = Produk::with(['kategori', 'warna', 'ukuran', 'bahan'])
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nama) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('kategori', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(nama) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $allProduks = $query->get();

        // Group products by normalized nama so catalog shows one card per unique product
        $grouped = $allProduks
            ->groupBy(function ($produk) {
                return mb_strtolower(trim($produk->nama ?? ''), 'UTF-8');
            })
            ->map(function ($variants) {
                $first = $variants->first();
                return [
                    'id' => $first->id,
                    'nama' => trim($first->nama),
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

        $produk->load(['kategori', 'warna', 'ukuran', 'bahan']);

        $variants = Produk::where('nama', '=', $produk->nama, 'and')
            ->where('is_active', true)
            ->with(['warna', 'ukuran', 'bahan'])
            ->get();

        // Get all unique colors and sizes for products with the same name
        $warnaOptions = Produk::where('nama', '=', $produk->nama, 'and')
            ->where('is_active', true)
            ->with('warna')
            ->get()
            ->pluck('warna.nama')
            ->unique()
            ->values();

        $ukuranOptions = Produk::where('nama', '=', $produk->nama, 'and')
            ->where('is_active', true)
            ->with('ukuran')
            ->get()
            ->pluck('ukuran.nama')
            ->unique()
            ->values();

        return Inertia::render('produk/DetailProduk', [
            'produk' => $produk,
            'variants' => $variants,
            'warnaOptions' => $warnaOptions,
            'ukuranOptions' => $ukuranOptions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kategori' => 'required|integer|exists:kategori,id',
            'id_warna' => 'nullable|integer|exists:warna,id',
            'id_ukuran' => 'required|integer|exists:ukuran,id',
            'nama' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
            'durasi_produksi' => 'required|integer|min:0',
            'durasi_restok' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:Aktif,Non-Aktif',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $validated['gambar'] = '/storage/' . $path;
        } else {
            $validated['gambar'] = '-';
        }

        $validated['deskripsi'] = $validated['deskripsi'] ?: '-';

        $bahan = Bahan::create([
            'id_kategori' => $validated['id_kategori'],
            'id_warna' => $validated['id_warna'],
            'id_ukuran' => $validated['id_ukuran'],
            'nama' => $validated['nama'],
            'stok' => $validated['stok'],
            'stok_minimum' => $validated['stok_minimum'],
            'durasi_produksi' => $validated['durasi_produksi'],
            'durasi_restok' => $validated['durasi_restok'],
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        Produk::create([
            'id_bahan' => $bahan->id,
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
            'deskripsi' => $validated['deskripsi'],
            'gambar' => $validated['gambar'],
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        return redirect()->route('master.produk')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'id_kategori' => 'required|integer|exists:kategori,id',
            'id_warna' => 'nullable|integer|exists:warna,id',
            'id_ukuran' => 'required|integer|exists:ukuran,id',
            'nama' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
            'durasi_produksi' => 'required|integer|min:0',
            'durasi_restok' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:Aktif,Non-Aktif',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('produk', 'public');
            $validated['gambar'] = '/storage/' . $path;
        } else {
            unset($validated['gambar']);
        }

        $validated['deskripsi'] = $validated['deskripsi'] ?: '-';

        if ($produk->bahan) {
            $produk->bahan->update([
                'id_kategori' => $validated['id_kategori'],
                'id_warna' => $validated['id_warna'],
                'id_ukuran' => $validated['id_ukuran'],
                'nama' => $validated['nama'],
                'stok' => $validated['stok'],
                'stok_minimum' => $validated['stok_minimum'],
                'durasi_produksi' => $validated['durasi_produksi'],
                'durasi_restok' => $validated['durasi_restok'],
                'is_active' => $validated['status'] === 'Aktif',
            ]);
        }

        $produk->update([
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
            'deskripsi' => $validated['deskripsi'],
            'gambar' => $validated['gambar'] ?? $produk->gambar,
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        return redirect()->route('master.produk')
            ->with('success', 'Produk berhasil diperbarui.');
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
