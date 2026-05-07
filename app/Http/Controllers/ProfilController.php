<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use App\Models\Pesanan;

class ProfilController extends Controller
{
    public function riwayatPesanan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('home');
        }

        $pesanan = Pesanan::with(['produk.warna', 'produk.ukuran', 'pembayaran'])
            ->where('id_pembeli', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('profil/RiwayatPesanan', [
            'pesanan' => $pesanan,
        ]);
    }

    public function edit(Request $request)
    {
        if (!$request->user()) return redirect()->route('home');
        return Inertia::render('profil/EditProfil');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'whatsapp' => 'required|string|max:20|unique:users,whatsapp,' . $user->id,
            'alamat' => 'nullable|string',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function editPassword(Request $request)
    {
        if (!$request->user()) return redirect()->route('home');
        return Inertia::render('profil/UbahKataSandi');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui!');
    }
}
