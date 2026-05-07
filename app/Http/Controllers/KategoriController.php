<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::paginate(10);

        return Inertia::render('master/Kategori', [
            'kategori' => $kategori,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        Kategori::create($validated);

        return redirect()->route('master.kategori')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        $kategori->update($validated);

        return redirect()->route('master.kategori')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->deleteOrFail();

        return redirect()->route('master.kategori')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
