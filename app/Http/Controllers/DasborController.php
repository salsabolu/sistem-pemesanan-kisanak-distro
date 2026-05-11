<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Pembayaran;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DasborController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        // 1. Pesanan Baru Count
        $pesananBaruCount = Pesanan::where('status', '=', 'Pesanan Baru', 'and')->count();

        // 2. Konfirmasi Pembayaran Count
        $konfirmasiPembayaranCount = Pembayaran::where('status', '=', 'Belum Konfirmasi', 'and')->count();

        // 3. Antrean Produksi Count
        $antreanProduksiCount = Pesanan::where('status', '=', 'Dalam Produksi', 'and')->count();

        // 4. Stok Menipis Count
        $stokMenipisCount = Produk::whereColumn('stok', '<=', 'stok_minimum', 'and')->where('status', '=', 'Aktif', 'and')->count();

        // 5. Chart Data (Total Sales Volume per Day for the selected month)
        $daysInMonth = $startDate->daysInMonth;
        $chartData = array_fill(0, $daysInMonth, 0);

        $salesRows = Pesanan::whereBetween('created_at', [$startDate, $endDate], 'and', false)
            ->where('status', '!=', 'Dibatalkan', 'and')
            ->select('created_at', 'jumlah')
            ->get();

        $salesPerDay = [];
        foreach ($salesRows as $row) {
            $day = Carbon::parse($row->created_at)->day;
            $salesPerDay[$day] = ($salesPerDay[$day] ?? 0) + (int) $row->jumlah;
        }

        foreach ($salesPerDay as $day => $total) {
            $chartData[$day - 1] = $total;
        }

        // 6. Top Buyers for the selected month
        // Hitung manual dari tabel `pesanan` agar field `total_pesanan` pasti ada di JSON.
        $pembeliTeratas = DB::table('pesanan')
            ->join('users', 'users.id', '=', 'pesanan.id_pembeli')
            ->where('pesanan.status', '!=', 'Dibatalkan', 'and')
            ->whereBetween('pesanan.created_at', [$startDate, $endDate], 'and', false)
            ->select(
                'users.id',
                'users.nama',
                'users.whatsapp',
                DB::raw('COUNT(pesanan.id) as total_pesanan')
            )
            ->groupBy('users.id', 'users.nama', 'users.whatsapp')
            ->orderByDesc('total_pesanan')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nama' => $row->nama,
                    'whatsapp' => $row->whatsapp,
                    'total_pesanan' => (int) $row->total_pesanan,
                ];
            })
            ->values();

        return Inertia::render('Dasbor', [
            'pesananBaruCount' => $pesananBaruCount,
            'konfirmasiPembayaranCount' => $konfirmasiPembayaranCount,
            'antreanProduksiCount' => $antreanProduksiCount,
            'stokMenipisCount' => $stokMenipisCount,
            'chartData' => $chartData,
            'pembeliTeratas' => $pembeliTeratas,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
