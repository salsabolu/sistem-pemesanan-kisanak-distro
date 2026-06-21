/**
 * Utilitas Warna — Konversi CMYK ke format yang bisa digunakan di web
 * ====================================================================
 *
 * Tabel `warna` di database menyimpan kode warna dalam format CMYK
 * sebagai string "C,M,Y,K" (contoh: "0,100,100,0").
 *
 * CMYK (Cyan, Magenta, Yellow, Key/Black) adalah format warna yang
 * digunakan untuk proses sablon/cetak, sehingga menampilkan warna
 * dalam format ini mengurangi perbedaan antara tampilan layar
 * dengan hasil sablon.
 *
 * Fungsi di sini mengkonversi CMYK ke HEX agar bisa dipakai oleh
 * Three.js dan CSS untuk rendering di layar.
 */

/**
 * Konversi nilai CMYK (masing-masing 0-100) ke tuple RGB.
 *
 * @param c - Cyan (0-100)
 * @param m - Magenta (0-100)
 * @param y - Yellow (0-100)
 * @param k - Key/Black (0-100)
 * @returns Tuple [R, G, B] dengan nilai 0-255
 */
export function cmykToRgb(
    c: number,
    m: number,
    y: number,
    k: number,
): [number, number, number] {
    const c1 = c / 100;
    const m1 = m / 100;
    const y1 = y / 100;
    const k1 = k / 100;

    const r = Math.round(255 * (1 - c1) * (1 - k1));
    const g = Math.round(255 * (1 - m1) * (1 - k1));
    const b = Math.round(255 * (1 - y1) * (1 - k1));

    return [r, g, b];
}

/**
 * Konversi string CMYK "C,M,Y,K" ke string warna HEX "#RRGGBB".
 * Mengembalikan `fallback` jika input tidak valid.
 *
 * @param cmykString - String CMYK dari database (contoh: "0,100,100,0")
 * @param fallback - Warna default jika konversi gagal
 * @returns String HEX (contoh: "#FF0000")
 */
export function cmykToHex(cmykString: string, fallback = '#CCCCCC'): string {
    if (!cmykString || typeof cmykString !== 'string') return fallback;

    const parts = cmykString.split(',').map((s) => parseFloat(s.trim()));
    if (parts.length !== 4 || parts.some((n) => isNaN(n))) return fallback;

    const [c, m, y, k] = parts;
    const [r, g, b] = cmykToRgb(c, m, y, k);

    return (
        '#' +
        [r, g, b]
            .map((v) =>
                Math.max(0, Math.min(255, v))
                    .toString(16)
                    .padStart(2, '0'),
            )
            .join('')
    );
}

/**
 * Palet warna preset sebagai fallback ketika produk tidak memiliki data warna.
 * Digunakan jika tabel warna kosong atau data tidak tersedia.
 */
export const PRESET_COLORS: { name: string; hex: string }[] = [
    { name: 'Putih', hex: '#D9D9D9' },
    { name: 'Kuning', hex: '#E8A94E' },
    { name: 'Hijau', hex: '#4A7A3D' },
    { name: 'Biru', hex: '#5B6BBF' },
    { name: 'Merah', hex: '#D94444' },
    { name: 'Hitam', hex: '#2C2C2C' },
];
