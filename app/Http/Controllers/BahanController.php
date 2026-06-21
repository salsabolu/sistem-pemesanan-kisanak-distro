<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Kategori;
use App\Models\Warna;
use App\Models\Ukuran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BahanController extends Controller
{
    public function index()
    {
        $bahan = Bahan::with(['kategori', 'warna', 'ukuran'])->paginate(15);
        $kategori = Kategori::where('is_active', true)->get();
        $warna = Warna::where('is_active', true)->get();
        $ukuran = Ukuran::where('is_active', true)->get();

        return Inertia::render('master/Bahan', [
            'bahan'   => $bahan,
            'kategori' => $kategori,
            'warna'    => $warna,
            'ukuran'   => $ukuran,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kategori'    => 'required|integer|exists:kategori,id',
            'id_warna'       => 'nullable|integer|exists:warna,id',
            'id_ukuran'      => 'required|integer|exists:ukuran,id',
            'nama'           => 'required|string|max:255',
            'stok'           => 'required|integer|min:0',
            'stok_minimum'   => 'required|integer|min:0',
            'durasi_restok'  => 'required|integer|min:0',
            'is_active'      => 'required|boolean',
        ]);

        Bahan::create($validated);

        return redirect()->route('master.bahan')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function update(Request $request, Bahan $bahan)
    {
        $validated = $request->validate([
            'id_kategori'    => 'required|integer|exists:kategori,id',
            'id_warna'       => 'nullable|integer|exists:warna,id',
            'id_ukuran'      => 'required|integer|exists:ukuran,id',
            'nama'           => 'required|string|max:255',
            'stok'           => 'required|integer|min:0',
            'stok_minimum'   => 'required|integer|min:0',
            'durasi_restok'  => 'required|integer|min:0',
            'is_active'      => 'required|boolean',
        ]);

        $bahan->update($validated);

        return redirect()->route('master.bahan')
            ->with('success', 'Bahan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Bahan $bahan)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $bahan->update(['is_active' => $validated['is_active']]);

        return redirect()->back()->with('success', 'Status bahan berhasil diperbarui.');
    }

    public function destroy(Bahan $bahan)
    {
        $bahan->deleteOrFail();

        return redirect()->route('master.bahan')
            ->with('success', 'Bahan berhasil dihapus.');
    }
}
