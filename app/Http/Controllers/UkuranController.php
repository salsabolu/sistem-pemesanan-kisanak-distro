<?php

namespace App\Http\Controllers;

use App\Models\Ukuran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UkuranController extends Controller
{
    public function index()
    {
        $ukuran = Ukuran::paginate(10);

        return Inertia::render('master/Ukuran', [
            'ukuran' => $ukuran,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        Ukuran::create($validated);

        return redirect()->route('master.ukuran')
            ->with('success', 'Ukuran berhasil ditambahkan.');
    }

    public function update(Request $request, Ukuran $ukuran)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        $ukuran->update($validated);

        return redirect()->route('master.ukuran')
            ->with('success', 'Ukuran berhasil diperbarui.');
    }

    public function destroy(Ukuran $ukuran)
    {
        $ukuran->deleteOrFail();

        return redirect()->route('master.ukuran')
            ->with('success', 'Ukuran berhasil dihapus.');
    }
}
