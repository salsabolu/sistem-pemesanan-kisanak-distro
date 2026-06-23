/**
 * Utilitas SVG Pattern — Manipulasi SVG untuk Texture Mapping 3D
 * ================================================================
 *
 * Modul ini menyediakan fungsi-fungsi untuk:
 * 1. Memuat file SVG pattern dari URL
 * 2. Parse zona warna (ZONE(...)) dan elemen teks (TEXT(...))
 * 3. Update warna zona secara dinamis
 * 4. Update teks (isi, font, ukuran, warna)
 * 5. Render SVG ke canvas untuk digunakan sebagai texture Three.js
 *
 * Alur kerja (sesuai referensi 3D-TShirt-Design):
 *   SVG → Parse → Edit (warna/teks) → Render ke Canvas → CanvasTexture Three.js
 */

// ─── Tipe Data ───

/** Representasi zona warna dalam SVG pattern */
export interface SvgZone {
    id: string;         // Full ID, contoh: "ZONE(base)"
    name: string;       // Nama zona, contoh: "base"
    fill: string;       // Warna fill saat ini
    element: Element;   // Referensi ke elemen SVG DOM
}

/** Representasi elemen teks dalam SVG pattern */
export interface SvgText {
    id: string;         // Full ID, contoh: "TEXT(team-name)"
    name: string;       // Nama teks, contoh: "team-name"
    text: string;       // Konten teks saat ini
    fontFamily: string;
    fontSize: string;
    fill: string;
    element: Element;   // Referensi ke elemen SVG DOM
}

// ─── Fungsi Utama ───

/**
 * Memuat file SVG dari URL dan parse menjadi XMLDocument.
 * @param url - Path ke file SVG (contoh: '/patterns/tshirt-default.svg')
 * @returns Promise<XMLDocument>
 */
export async function loadSvgFromUrl(url: string): Promise<XMLDocument> {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Gagal memuat SVG: ${response.status} ${response.statusText}`);
    }
    const svgText = await response.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(svgText, 'image/svg+xml');

    // Cek apakah parsing berhasil
    const parserError = doc.querySelector('parsererror');
    if (parserError) {
        throw new Error(`SVG parsing error: ${parserError.textContent}`);
    }

    return doc;
}

/**
 * Parse semua zona warna dari SVG document.
 * Zona diidentifikasi oleh elemen dengan id yang dimulai dengan "ZONE("
 * @param svgDoc - XMLDocument dari SVG yang sudah dimuat
 * @returns Array dari SvgZone
 */
export function parseSvgZones(svgDoc: XMLDocument): SvgZone[] {
    const zones: SvgZone[] = [];
    const allElements = svgDoc.querySelectorAll('[id^="ZONE("]');

    allElements.forEach((el) => {
        const id = el.getAttribute('id') || '';
        const match = id.match(/^ZONE\((.+)\)$/);
        if (match) {
            zones.push({
                id,
                name: match[1],
                fill: el.getAttribute('fill') || '#FFFFFF',
                element: el,
            });
        }
    });

    return zones;
}

/**
 * Parse semua elemen teks dari SVG document.
 * Teks diidentifikasi oleh elemen <text> dengan id yang dimulai dengan "TEXT("
 * @param svgDoc - XMLDocument dari SVG yang sudah dimuat
 * @returns Array dari SvgText
 */
export function parseSvgTexts(svgDoc: XMLDocument): SvgText[] {
    const texts: SvgText[] = [];
    const allTexts = svgDoc.querySelectorAll('text[id^="TEXT("]');

    allTexts.forEach((el) => {
        const id = el.getAttribute('id') || '';
        const match = id.match(/^TEXT\((.+)\)$/);
        if (match) {
            texts.push({
                id,
                name: match[1],
                text: el.textContent || '',
                fontFamily: el.getAttribute('font-family') || 'Arial',
                fontSize: el.getAttribute('font-size') || '40',
                fill: (el as HTMLElement).style?.fill || el.getAttribute('fill') || '#FFFFFF',
                element: el,
            });
        }
    });

    return texts;
}

/**
 * Ubah warna fill zona tertentu dalam SVG.
 * @param svgDoc - XMLDocument SVG
 * @param zoneId - ID zona (contoh: "ZONE(base)")
 * @param color - Warna baru (HEX string, contoh: "#FF0000")
 */
export function updateSvgZoneColor(svgDoc: XMLDocument, zoneId: string, color: string): void {
    const el = svgDoc.getElementById(zoneId);
    if (el) {
        el.setAttribute('fill', color);
    }
}

/**
 * Ubah semua zona warna dalam SVG sekaligus ke satu warna.
 * Berguna untuk "ganti warna kaos" secara keseluruhan.
 * @param svgDoc - XMLDocument SVG
 * @param color - Warna baru
 * @param excludeZones - Zona yang dikecualikan (contoh: ['collar-trim'])
 */
export function updateAllZoneColors(
    svgDoc: XMLDocument,
    color: string,
    excludeZones: string[] = [],
): void {
    const zones = parseSvgZones(svgDoc);
    if (zones.length > 0) {
        zones.forEach((zone) => {
            if (!excludeZones.includes(zone.name)) {
                zone.element.setAttribute('fill', color);
                (zone.element as HTMLElement).style.fill = color;
            }
        });
    } else {
        // Fallback: Jika tidak ada ZONE(...), warnai semua elemen path
        // Menggunakan getElementsByTagNameNS atau iterasi all elements untuk menghindari isu namespace di XMLDocument
        const allElements = svgDoc.querySelectorAll('*');
        allElements.forEach(el => {
            if (el.localName === 'path') {
                el.setAttribute('fill', color);
                // Hapus atribut style fill jika ada agar setAttribute fill bisa berlaku
                const style = el.getAttribute('style');
                if (style) {
                    el.setAttribute('style', style.replace(/fill\s*:\s*[^;]+;?/gi, ''));
                }
            }
        });
    }
}

/**
 * Ubah properti teks dalam SVG.
 * @param svgDoc - XMLDocument SVG
 * @param textId - ID teks (contoh: "TEXT(team-name)")
 * @param options - Properti yang ingin diubah
 */
export function updateSvgText(
    svgDoc: XMLDocument,
    textId: string,
    options: {
        text?: string;
        fontFamily?: string;
        fontSize?: string;
        fill?: string;
        strokeColor?: string;
    },
): void {
    const el = svgDoc.getElementById(textId);
    if (!el) return;

    if (options.text !== undefined) {
        el.textContent = options.text;
    }
    if (options.fontFamily !== undefined) {
        el.setAttribute('font-family', options.fontFamily);
        (el as HTMLElement).style.fontFamily = options.fontFamily;
    }
    if (options.fontSize !== undefined) {
        el.setAttribute('font-size', options.fontSize);
        (el as HTMLElement).style.fontSize = options.fontSize + 'px';
    }
    if (options.fill !== undefined) {
        (el as HTMLElement).style.fill = options.fill;
    }
    if (options.strokeColor !== undefined) {
        (el as HTMLElement).style.stroke = options.strokeColor;
    }
}

/**
 * Render SVG document ke canvas HTML dengan opsional texture overlay.
 *
 * Proses (sesuai referensi main_s.js):
 * 1. Serialize SVG (tanpa teks) → render ke canvas
 * 2. Overlay texture pattern (opsional, semi-transparan)
 * 3. Render teks SVG di atas
 *
 * @param svgDoc - XMLDocument SVG
 * @param canvas - HTMLCanvasElement target
 * @param textureUrl - URL texture pattern opsional
 * @returns Promise<void>
 */
export async function renderSvgToCanvas(
    svgDoc: XMLDocument,
    canvas: HTMLCanvasElement,
    textureUrl?: string,
): Promise<void> {
    const svgEl = svgDoc.querySelector('svg');
    if (!svgEl) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Ambil dimensi SVG
    const svgWidth = parseInt(svgEl.getAttribute('width') || '2048', 10);
    const svgHeight = parseInt(svgEl.getAttribute('height') || '2048', 10);
    canvas.width = svgWidth;
    canvas.height = svgHeight;

    // ─── Step 1: Render SVG path (tanpa teks) ───
    // Clone SVG dan hapus teks agar bisa render terpisah
    const pathSvg = svgEl.cloneNode(true) as Element;
    const pathTexts = pathSvg.querySelectorAll('text');
    pathTexts.forEach((t) => t.remove());

    const pathSvgData = new XMLSerializer().serializeToString(pathSvg);
    const pathImg = await svgStringToImage(pathSvgData, svgWidth, svgHeight);
    ctx.drawImage(pathImg, 0, 0);

    // ─── Step 2: Overlay texture (opsional) ───
    if (textureUrl) {
        try {
            const texImg = await loadImageFromUrl(textureUrl);
            ctx.save();
            ctx.globalAlpha = 0.4;
            // Buat repeating pattern
            const pattern = ctx.createPattern(texImg, 'repeat');
            if (pattern) {
                ctx.fillStyle = pattern;
                ctx.fillRect(0, 0, svgWidth, svgHeight);
            }
            ctx.restore();
        } catch (err) {
            console.warn('[svgPatternUtils] Gagal memuat texture overlay:', err);
        }
    }

    // ─── Step 3: Render teks SVG ───
    const textSvg = svgEl.cloneNode(true) as Element;
    const textPaths = textSvg.querySelectorAll('path');
    textPaths.forEach((p) => p.remove());
    // Hapus juga elemen <g> yang bukan berisi teks
    const textGs = textSvg.querySelectorAll('g');
    textGs.forEach((g) => {
        if (!g.querySelector('text')) {
            g.remove();
        }
    });

    const textSvgData = new XMLSerializer().serializeToString(textSvg);
    const textImg = await svgStringToImage(textSvgData, svgWidth, svgHeight);
    ctx.drawImage(textImg, 0, 0);
}

// ─── Helper Functions ───

/**
 * Konversi string SVG menjadi HTMLImageElement yang siap di-draw ke canvas.
 */
function svgStringToImage(svgData: string, width: number, height: number): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.width = width;
        img.height = height;
        img.onload = () => resolve(img);
        img.onerror = (e) => reject(e);
        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    });
}

/**
 * Memuat gambar dari URL.
 */
function loadImageFromUrl(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = (e) => reject(e);
        img.src = url;
    });
}

/**
 * Mendapatkan serialized SVG string dari XMLDocument.
 */
export function serializeSvg(svgDoc: XMLDocument): string {
    const svgEl = svgDoc.querySelector('svg');
    if (!svgEl) return '';
    return new XMLSerializer().serializeToString(svgEl);
}

/**
 * Membuat salinan deep clone dari SVG document.
 */
export function cloneSvgDoc(svgDoc: XMLDocument): XMLDocument {
    const serialized = serializeSvg(svgDoc);
    const parser = new DOMParser();
    return parser.parseFromString(serialized, 'image/svg+xml');
}
