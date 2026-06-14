<?php

namespace App\Http\Controllers;

use App\Models\Warna;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarnaController extends Controller
{
    public function index()
    {
        $warna = Warna::paginate(10);

        return Inertia::render('master/Warna', [
            'warna' => $warna,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Warna::create($validated);

        return redirect()->route('master.warna')
            ->with('success', 'Warna berhasil ditambahkan.');
    }

    public function update(Request $request, Warna $warna)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $warna->update($validated);

        return redirect()->route('master.warna')
            ->with('success', 'Warna berhasil diperbarui.');
    }

    public function destroy(Warna $warna)
    {
        $warna->deleteOrFail();

        return redirect()->route('master.warna')
            ->with('success', 'Warna berhasil dihapus.');
    }
}
