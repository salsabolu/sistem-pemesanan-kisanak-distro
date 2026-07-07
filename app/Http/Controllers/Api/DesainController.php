<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Desain;
use App\Models\Teks;
use App\Models\Gambar;
use Illuminate\Http\Request;
use App\Http\Resources\DesainResource;
use Illuminate\Support\Facades\DB;

class DesainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Biasanya desain terhubung ke pesanan user, jadi kita batasi jika diperlukan.
        // Untuk saat ini tampilkan semua (atau bisa dipaginasi).
        return DesainResource::collection(Desain::with(['teks', 'gambar'])->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_detail_pesanan' => 'nullable|exists:detail_pesanan,id',
            'desain_json' => 'required|string',
            'teks' => 'nullable|array',
            'teks.*.teks' => 'required|string|max:255',
            'gambar_files' => 'nullable|array',
            'gambar_files.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $desain = Desain::create([
                'id_detail_pesanan' => $validated['id_detail_pesanan'] ?? null,
                'desain_json' => $validated['desain_json'],
            ]);

            if (!empty($validated['teks'])) {
                foreach ($validated['teks'] as $teksData) {
                    Teks::create([
                        'id_desain' => $desain->id,
                        'teks' => $teksData['teks'],
                    ]);
                }
            }

            if ($request->hasFile('gambar_files')) {
                foreach ($request->file('gambar_files') as $file) {
                    $path = $file->store('desain-gambar', 'public');
                    Gambar::create([
                        'id_desain' => $desain->id,
                        'file' => '/storage/' . $path,
                    ]);
                }
            }

            DB::commit();

            return new DesainResource($desain->load(['teks', 'gambar']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan desain', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Desain $desain)
    {
        return new DesainResource($desain->load(['teks', 'gambar']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Desain $desain)
    {
        // Update desain json
        $validated = $request->validate([
            'desain_json' => 'sometimes|string',
        ]);

        $desain->update($validated);

        return new DesainResource($desain->load(['teks', 'gambar']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Desain $desain)
    {
        $desain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Desain berhasil dihapus'
        ]);
    }
}
