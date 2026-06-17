<?php

namespace App\Services;

use App\Models\DetailPesanan;
use App\Models\Distro;
use App\Models\Pesanan;
use Carbon\Carbon;

/**
 * EstimasiService — Rule-Based System (Mesin Inferensi)
 *
 * Menghitung estimasi_selesai pesanan dengan mempertimbangkan:
 *   1. WPD  – Waktu Produksi Dasar   = Σ (bahan.durasi_produksi × jumlah)     [menit]
 *   2. WTB  – Waktu Tunggu Bahan     = max(bahan.durasi_restok) jika stok < 0 (stok dikurangi sebelumnya) [menit]
 *   3. WTA  – Waktu Tunggu Antrean   = Σ WPD pesanan lain "Dalam Produksi"    [menit]
 *   4. Jam operasional distro dipatuhi; produksi dijeda di luar jam kerja dan
 *      dilanjutkan pada hari kerja berikutnya.
 *
 * Formula: estimasi_selesai = sekarang (WIB) + WTA + WTB + WPD (mematuhi jam operasional)
 *
 * Satuan: durasi_produksi & durasi_restok disimpan dalam MENIT di database.
 */
class EstimasiService
{
    // Timezone lokal distro — selalu WIB agar cocok dengan jam_buka/jam_tutup di DB
    private const TZ = 'Asia/Jakarta';

    // Persiapan sebelum produksi bisa dimulai (bersih-bersih, nyalakan mesin)
    private const MENIT_PERSIAPAN = 15;

    // Nama hari Indonesia → nomor Carbon ISO
    private const HARI_MAP = [
        'senin'   => Carbon::MONDAY,
        'selasa'  => Carbon::TUESDAY,
        'rabu'    => Carbon::WEDNESDAY,
        'kamis'   => Carbon::THURSDAY,
        'jumat'   => Carbon::FRIDAY,
        'jum\'at' => Carbon::FRIDAY,
        'sabtu'   => Carbon::SATURDAY,
        'minggu'  => Carbon::SUNDAY,
        'ahad'    => Carbon::SUNDAY,
    ];

    /**
     * Hitung estimasi selesai untuk sebuah pesanan.
     *
     * @param  Pesanan  $pesanan  Pesanan yang baru dikonfirmasi
     * @return Carbon             Waktu estimasi selesai (dalam timezone UTC, siap disimpan ke DB)
     */
    public static function hitungEstimasi(Pesanan $pesanan): Carbon
    {
        $pesanan->loadMissing('detailPesanan.produk.bahan');

        // Ambil konfigurasi jam operasional
        $distro       = Distro::first();
        $jamBuka      = $distro?->jam_buka   ?? '08:00:00';   // WIB
        $jamTutup     = $distro?->jam_tutup  ?? '17:00:00';   // WIB
        $hariBuka     = strtolower(trim($distro?->hari_buka  ?? 'senin'));
        $hariTutup    = strtolower(trim($distro?->hari_tutup ?? 'sabtu'));

        $hariBukaNum  = self::HARI_MAP[$hariBuka]  ?? Carbon::MONDAY;
        $hariTutupNum = self::HARI_MAP[$hariTutup] ?? Carbon::SATURDAY;

        // 1. WPD (Waktu Produksi Dasar)
        $wpd = 0;
        foreach ($pesanan->detailPesanan as $detail) {
            $bahan = $detail->produk?->bahan;
            if ($bahan) {
                $wpd += $bahan->durasi_produksi * $detail->jumlah;
            }
        }

        // 2. WTB (Waktu Tunggu Bahan)
        // Aturan: IF bahan.stok < 0 THEN WTB = bahan.durasi_restok (stok dikurangi sebelumnya)
        // Ambil nilai terbesar karena restok berjalan paralel
        $wtb = 0;
        foreach ($pesanan->detailPesanan as $detail) {
            $bahan = $detail->produk?->bahan;
            // Stok sudah dikurangi sebelumnya di controller.
            // Jika stok < 0, berarti stok aslinya sebelum dikurangi tidak mencukupi.
            if ($bahan && $bahan->stok < 0) {
                $wtb = max($wtb, $bahan->durasi_restok);
            }
        }

        // 3. WTA (Waktu Tunggu Antrean)
        $wta = self::hitungWTA($pesanan->id);

        // 4. Hitung titik waktu akhir, menggunakan timezone WIB
        $totalMenit = $wta + $wtb + $wpd;

        // Carbon::now(self::TZ) → waktu sekarang dalam WIB
        // Ini krusial karena app.timezone = UTC, sedangkan jam_buka/jam_tutup adalah WIB.
        $estimasiWib = self::tambahMenitOperasional(
            Carbon::now(self::TZ),
            $totalMenit,
            $jamBuka,
            $jamTutup,
            $hariBukaNum,
            $hariTutupNum
        );

        // Konversi ke UTC sebelum disimpan agar konsisten dengan kolom datetime di DB
        return $estimasiWib->utc();
    }

    /**
     * Hitung total WPD dari semua pesanan yang sedang "Dalam Produksi",
     * kecuali pesanan yang sedang dihitung.
     */
    private static function hitungWTA(int $excludePesananId): int
    {
        $detailAntrean = DetailPesanan::whereHas('pesanan', function ($q) use ($excludePesananId) {
                $q->where('status', 'Dalam Produksi')
                  ->where('id', '!=', $excludePesananId);
            })
            ->with('produk.bahan')
            ->get();

        $wta = 0;
        foreach ($detailAntrean as $detail) {
            $bahan = $detail->produk?->bahan;
            if ($bahan) {
                $wta += $bahan->durasi_produksi * $detail->jumlah;
            }
        }

        return $wta;
    }

    /**
     * Tambahkan sejumlah menit ke $start hanya dalam jam operasional.
     * Produksi dijeda saat di luar jam kerja dan dilanjutkan hari berikutnya.
     *
     * Semua Carbon object di dalam method ini wajib ber-timezone WIB (Asia/Jakarta)
     * agar perbandingan jam_buka/jam_tutup selalu akurat.
     *
     * @param  Carbon  $start        Waktu awal dalam WIB
     * @param  int     $menitSisa    Menit produksi yang belum terpakai
     * @param  string  $jamBuka      Format "HH:MM" atau "HH:MM:SS"
     * @param  string  $jamTutup     Format "HH:MM" atau "HH:MM:SS"
     * @param  int     $hariBukaNum  Carbon day constant ISO (1=Senin … 7=Minggu)
     * @param  int     $hariTutupNum Carbon day constant ISO
     * @return Carbon  Hasil dalam WIB
     */
    private static function tambahMenitOperasional(
        Carbon $start,
        int    $menitSisa,
        string $jamBuka,
        string $jamTutup,
        int    $hariBukaNum,
        int    $hariTutupNum
    ): Carbon {
        // Pastikan selalu dalam WIB
        $current = $start->copy()->setTimezone(self::TZ);

        // Maju ke slot jam kerja pertama jika saat ini di luar jam operasional
        $current = self::majuKeJamKerja($current, $jamBuka, $jamTutup, $hariBukaNum, $hariTutupNum);

        while ($menitSisa > 0) {
            [$hTutup, $mTutup] = self::parseJam($jamTutup);

            // Jam tutup pada hari $current (dalam WIB)
            $tutupHariIni = $current->copy()->setHour($hTutup)->setMinute($mTutup)->setSecond(0);

            // Menit tersedia dari posisi saat ini sampai jam tutup
            $menitTersedia = (int) $current->diffInMinutes($tutupHariIni, false);

            if ($menitTersedia <= 0) {
                // Sudah melewati jam tutup, lanjutkan hari berikutnya
                $current = self::awalHariKerjaBerikut($current, $jamBuka, $hariBukaNum, $hariTutupNum);
                continue;
            }

            if ($menitSisa <= $menitTersedia) {
                // Cukup selesai hari ini
                $current->addMinutes($menitSisa);
                $menitSisa = 0;
            } else {
                // Tidak cukup hari ini, lanjutkan besok
                $menitSisa -= $menitTersedia;
                $current = self::awalHariKerjaBerikut($current, $jamBuka, $hariBukaNum, $hariTutupNum);
            }
        }

        return $current;
    }

    /**
     * Jika $current di luar jam kerja/hari kerja, majukan ke awal jam kerja berikutnya.
     * $current harus dalam timezone WIB.
     */
    private static function majuKeJamKerja(
        Carbon $current,
        string $jamBuka,
        string $jamTutup,
        int    $hariBukaNum,
        int    $hariTutupNum
    ): Carbon {
        // Jika hari libur, maju ke hari kerja pertama
        if (!self::isHariKerja($current, $hariBukaNum, $hariTutupNum)) {
            return self::awalHariKerjaBerikut($current, $jamBuka, $hariBukaNum, $hariTutupNum);
        }

        [$hBuka, $mBuka]   = self::parseJam($jamBuka);
        [$hTutup, $mTutup] = self::parseJam($jamTutup);

        // Jam buka pada hari ini (WIB) + waktu persiapan
        $bukaHariIni  = $current->copy()->setHour($hBuka)->setMinute($mBuka)->setSecond(0)
                                        ->addMinutes(self::MENIT_PERSIAPAN);
        $tutupHariIni = $current->copy()->setHour($hTutup)->setMinute($mTutup)->setSecond(0);

        if ($current->lt($bukaHariIni)) {
            // Sebelum jam buka hari ini → tunggu jam buka
            return $bukaHariIni;
        }

        if ($current->gte($tutupHariIni)) {
            // Sudah lewat jam tutup → maju ke hari berikutnya
            return self::awalHariKerjaBerikut($current, $jamBuka, $hariBukaNum, $hariTutupNum);
        }

        return $current;
    }

    /**
     * Dapatkan awal jam kerja pada hari kerja berikutnya.
     * $current harus dalam timezone WIB.
     */
    private static function awalHariKerjaBerikut(
        Carbon $current,
        string $jamBuka,
        int    $hariBukaNum,
        int    $hariTutupNum
    ): Carbon {
        [$hBuka, $mBuka] = self::parseJam($jamBuka);

        // addDay() + startOfDay() dalam timezone WIB
        $next = $current->copy()->addDay()->startOfDay();

        // Lewati hari libur
        while (!self::isHariKerja($next, $hariBukaNum, $hariTutupNum)) {
            $next->addDay();
        }

        return $next->setHour($hBuka)->setMinute($mBuka)->setSecond(0)
                    ->addMinutes(self::MENIT_PERSIAPAN);
    }

    /**
     * Periksa apakah tanggal $date adalah hari kerja.
     * Mendukung rentang Senin–Sabtu maupun Senin–Jumat, dll.
     * Menggunakan dayOfWeekIso: 1=Senin … 7=Minggu.
     */
    private static function isHariKerja(Carbon $date, int $hariBukaNum, int $hariTutupNum): bool
    {
        $dayOfWeek = $date->dayOfWeekIso;

        if ($hariBukaNum <= $hariTutupNum) {
            // Rentang normal, misal Senin (1) – Sabtu (6)
            return $dayOfWeek >= $hariBukaNum && $dayOfWeek <= $hariTutupNum;
        } else {
            // Melewati akhir minggu (jarang, tapi ditangani)
            return $dayOfWeek >= $hariBukaNum || $dayOfWeek <= $hariTutupNum;
        }
    }

    /**
     * Parse string jam "HH:MM" atau "HH:MM:SS" ke [int $jam, int $menit].
     */
    private static function parseJam(string $jam): array
    {
        $parts = explode(':', $jam);
        return [(int) ($parts[0] ?? 8), (int) ($parts[1] ?? 0)];
    }
}
