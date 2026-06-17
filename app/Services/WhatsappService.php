<?php

namespace App\Services;

use App\Models\Pesanan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;
    protected string $token;
    protected string $instanceId;

    public function __construct()
    {
        $this->baseUrl = config('multichat.base_url');
        $this->token = config('multichat.token');
        $this->instanceId = config('multichat.instance_id');
    }

    // Format nomor telepon menjadi JID WhatsApp
    // Contoh: 08123456789 → 628123456789@s.whatsapp.net
    
    public function formatJid(string $phone): string
    {
        // Hapus karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // Ganti awalan 0 dengan 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Tambahkan 62 jika belum ada
        if (! str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone . '@s.whatsapp.net';
    }

    // Kirim pesan teks via Multichat API (GET request)
    
    public function sendText(string $phone, string $message): bool
    {
        if (empty($this->token) || empty($this->instanceId)) {
            Log::warning('WhatsApp: Token atau Instance ID belum dikonfigurasi.');
            return false;
        }

        $jid = $this->formatJid($phone);

        try {
            $response = Http::timeout(15)->get($this->baseUrl . '/send-text', [
                'token' => $this->token,
                'instance_id' => $this->instanceId,
                'jid' => $jid,
                'msg' => $message,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['success'] ?? false)) {
                Log::info("WhatsApp: Pesan terkirim ke {$jid}");
                return true;
            }

            Log::error("WhatsApp: Gagal kirim ke {$jid}", [
                'status' => $response->status(),
                'response' => $data,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp: Exception saat kirim ke {$jid}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // Kirim notifikasi perubahan status pesanan ke pembeli

    public function notifikasiStatusPesanan(Pesanan $pesanan, string $statusBaru): void
    {
        $pesanan->loadMissing(['pembeli', 'produk.warna', 'produk.ukuran', 'pembayaran']);

        $pembeli = $pesanan->pembeli;
        if (! $pembeli || empty($pembeli->whatsapp)) {
            Log::info("WhatsApp: Pembeli #{$pesanan->id} tidak memiliki nomor WA.");
            return;
        }

        $nama = $pembeli->nama ?? 'Pelanggan';
        $noPesanan = $pesanan->id;
        $total = number_format($pesanan->total, 0, ',', '.');

        // Bangun daftar item pesanan
        $items = $this->buildItemList($pesanan);

        $message = $this->buildStatusMessage($nama, $noPesanan, $total, $statusBaru, $pesanan, $items);

        $this->sendText($pembeli->whatsapp, $message);
    }

    // Bangun daftar item pesanan untuk pesan
    
    protected function buildItemList(Pesanan $pesanan): string
    {
        $lines = [];

        foreach ($pesanan->produk as $produk) {
            $namaProduk = $produk->nama;
            $warna = $produk->warna?->nama ?? '-';
            $ukuran = $produk->ukuran?->nama ?? '-';
            $jumlah = $produk->pivot->jumlah ?? 0;

            $lines[] = "  • {$namaProduk} ({$warna}, {$ukuran}) x{$jumlah}";
        }

        return ! empty($lines) ? implode("\n", $lines) : '  (tidak ada item)';
    }

    // Buat template pesan berdasarkan status
    
    protected function buildStatusMessage(
        string $nama,
        int $noPesanan,
        string $total,
        string $status,
        Pesanan $pesanan,
        string $items
    ): string {
        $appName = config('app.name', 'Kisanak Distro');

        return match ($status) {
            'Dalam Produksi' => implode("\n", [
                "Halo {$nama}",
                "Pesanan Anda *#{$noPesanan}* sedang dalam proses produksi.",
                "",
                "*Detail Pesanan:*",
                $items,
                "",
                "Total: Rp{$total}",
                "Status Pembayaran: " . ($pesanan->pembayaran?->status ?? 'Belum Dibayar'),
                $pesanan->estimasi_selesai
                    ? "Estimasi Selesai: " . $pesanan->estimasi_selesai->timezone('Asia/Jakarta')->format('H:i, d-m-Y')
                    : "",
                "",
                "Kami akan menghubungi Anda kembali saat pesanan selesai.",
                "",
                "Terima kasih🙏",
                "*{$appName}*",
            ]),

            'Selesai' => implode("\n", [
                "Halo {$nama}",
                "Pesanan Anda *#{$noPesanan}* telah *SELESAI*!",
                "",
                "*Detail Pesanan:*",
                $items,
                "",
                "Total: Rp{$total}",
                "Status Pembayaran: " . ($pesanan->pembayaran?->status ?? 'Belum Dibayar'),
                "",
                "Silakan datang ke toko kami untuk mengambil pesanan Anda.",
                "",
                "Terima kasih telah mempercayakan pesanan kepada kami🙏",
                "*{$appName}*",
            ]),

            'Dibatalkan' => implode("\n", [
                "Halo {$nama}",
                "Mohon maaf, pesanan Anda *#{$noPesanan}* telah *dibatalkan*.",
                "",
                "*Detail Pesanan:*",
                $items,
                "",
                "Total: Rp{$total}",
                "Status Pembayaran: " . ($pesanan->pembayaran?->status ?? 'Belum Dibayar'),
                "Waktu Pemesanan: " . ($pesanan->created_at ? $pesanan->created_at->timezone('Asia/Jakarta')->format('H:i, d-m-Y') : '-'),
                "",
                "Untuk prosedur refund, silakan hubungi kami dengan melampirkan bukti pembayaran.",
                "",
                "Terima kasih atas pengertiannya🙏",
                "*{$appName}*",
            ]),

            default => implode("\n", [
                "Halo {$nama},",
                "",
                "Status pesanan Anda *#{$noPesanan}* telah diperbarui menjadi: *{$status}*.",
                "",
                "*Detail Pesanan:*",
                $items,
                "",
                "Total: Rp{$total}",
                "",
                "Terima kasih🙏",
                "*{$appName}*",
            ]),
        };
    }

    // Kirim notifikasi peringatan stok menipis ke Pemilik dan Kasir.

    public function notifikasiStokMenipis(\App\Models\Bahan $bahan): void
    {
        // Cari user kasir & pemilik yang punya nomor WA
        $users = \App\Models\User::role(['pemilik', 'kasir'])
            ->whereNotNull('whatsapp')
            ->where('whatsapp', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $appName = config('app.name', 'Kisanak Distro');
        $namaBahan = $bahan->nama;
        $sisaStok = $bahan->stok;
        $stokMinimum = $bahan->stok_minimum;

        $message = implode("\n", [
            "⚠️ *PERINGATAN STOK MENIPIS* ⚠️",
            "",
            "Stok bahan berikut sudah mencapai atau di bawah batas minimum.",
            "",
            "*Item:* {$namaBahan}",
            "*Sisa Stok:* {$sisaStok}",
            "*Batas Minimum:* {$stokMinimum}",
            "",
            "Silakan lakukan restok agar proses pesanan tidak terhambat.",
            "*{$appName}*",
        ]);

        foreach ($users as $user) {
            $this->sendText($user->whatsapp, $message);
        }
    }
}