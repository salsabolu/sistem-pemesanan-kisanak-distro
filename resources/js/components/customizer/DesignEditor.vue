<!--
  Komponen DesignEditor — Editor Desain 2D (SVG Pattern + Fabric.js)
  ===================================================================
  Canvas yang menampilkan SVG pattern kaos dalam layout flat.
  User bisa melihat dan mengedit:
  - Zona warna (dari SVG pattern)
  - Teks (dari SVG pattern)
  - Gambar overlay (via Fabric.js)

  Output canvas ini diterapkan sebagai texture pada model 3D.
-->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, shallowRef, watch, nextTick } from 'vue';
import { Canvas as FabricCanvas, FabricImage, IText } from 'fabric';
import {
    loadSvgFromUrl,
    parseSvgZones,
    parseSvgTexts,
    updateSvgZoneColor,
    updateAllZoneColors,
    updateSvgText,
    renderSvgToCanvas,
    type SvgZone,
    type SvgText,
} from '@/lib/svgPatternUtils';

// ─── Props ───
const props = defineProps<{
    /** Path ke file SVG pattern (contoh: '/patterns/tshirt-default.svg') */
    patternPath: string;
    /** Path ke texture overlay opsional */
    texturePath?: string;
    /** Warna yang dipilih dari palet */
    selectedColor?: string;
}>();

// ─── Emits ───
const emit = defineEmits<{
    (e: 'canvas-update', canvas: HTMLCanvasElement): void;
    (e: 'zones-loaded', zones: SvgZone[]): void;
    (e: 'texts-loaded', texts: SvgText[]): void;
    (e: 'selection', obj: any): void;
}>();

// ─── Refs ───
const containerRef = ref<HTMLDivElement | null>(null);
const stackRef = ref<HTMLDivElement | null>(null);
const svgCanvasRef = ref<HTMLCanvasElement | null>(null);
const fabricCanvasRef = ref<HTMLCanvasElement | null>(null);
const compositeCanvasRef = ref<HTMLCanvasElement | null>(null);

const svgDoc = shallowRef<XMLDocument | null>(null);
const fabricCanvas = shallowRef<FabricCanvas | null>(null);
const zones = ref<SvgZone[]>([]);
const texts = ref<SvgText[]>([]);
const isLoading = ref(true);
const loadError = ref<string | null>(null);

let objectOffset = 0;

/** Ukuran internal texture canvas (diextract dari SVG) */
const canvasWidth = ref(2048);
const canvasHeight = ref(2048);

let lastContainerWidth = 0;
let lastContainerHeight = 0;

function updateStackSize() {
    if (!stackRef.value || canvasHeight.value <= 0 || lastContainerWidth <= 0) return;
    const ratio = canvasWidth.value / canvasHeight.value;
    let w = lastContainerWidth;
    let h = lastContainerWidth / ratio;
    if (h > lastContainerHeight) {
        h = lastContainerHeight;
        w = lastContainerHeight * ratio;
    }
    const finalW = Math.floor(w);
    const finalH = Math.floor(h);
    stackRef.value.style.width = `${finalW}px`;
    stackRef.value.style.height = `${finalH}px`;

    // SVG canvas — atur ukuran CSS secara manual
    if (svgCanvasRef.value) {
        svgCanvasRef.value.style.width = `${finalW}px`;
        svgCanvasRef.value.style.height = `${finalH}px`;
    }

    // Fabric.js — beritahu ukuran CSS-nya agar koordinat mouse tepat
    if (fabricCanvas.value) {
        fabricCanvas.value.setDimensions(
            { width: finalW, height: finalH },
            { cssOnly: true },
        );
    }
}

watch(() => props.selectedColor, (newColor) => {
    if (newColor) {
        setAllZonesColor(newColor);
    }
});

// ─── Inisialisasi ───

onMounted(() => {
    // Observasi containerRef untuk menjaga stackRef tetap proporsional sesuai SVG
    const resizeObserver = new ResizeObserver((entries) => {
        for (const entry of entries) {
            lastContainerWidth = entry.contentRect.width;
            lastContainerHeight = entry.contentRect.height;
            updateStackSize();
        }
    });

    if (containerRef.value) {
        resizeObserver.observe(containerRef.value);
    }

    initEditor();

    onBeforeUnmount(() => {
        resizeObserver.disconnect();
    });
});

/**
 * Memuat SVG pattern, parse zona & teks, render ke canvas,
 * dan inisialisasi Fabric.js untuk overlay gambar.
 */
async function initEditor() {
    isLoading.value = true;
    loadError.value = null;

    try {
        // 1. Muat SVG pattern
        const doc = await loadSvgFromUrl(props.patternPath);
        svgDoc.value = doc;

        const svgEl = doc.querySelector('svg');
        if (svgEl) {
            canvasWidth.value = parseInt(svgEl.getAttribute('width') || '2048', 10);
            canvasHeight.value = parseInt(svgEl.getAttribute('height') || '2048', 10);
        }
        updateStackSize();

        // 2. Parse zona warna dan teks
        zones.value = parseSvgZones(doc);
        texts.value = parseSvgTexts(doc);

        if (props.selectedColor) {
            updateAllZoneColors(doc, props.selectedColor);
            zones.value.forEach((z) => {
                z.fill = props.selectedColor!;
            });
        }

        emit('zones-loaded', zones.value);
        emit('texts-loaded', texts.value);

        // 3. Render SVG ke canvas
        await renderSvg();

        // 4. Inisialisasi Fabric.js untuk overlay gambar
        initFabric();
        updateStackSize(); // FIX: Update CSS size after fabric canvas is created

        // 5. Compose final texture
        composeTexture();

        isLoading.value = false;
    } catch (err) {
        console.error('[DesignEditor] Gagal memuat SVG pattern:', err);
        loadError.value = (err as Error).message || 'Gagal memuat pattern';
        isLoading.value = false;
    }
}

/**
 * Render SVG ke svgCanvas (layer bawah — path + texture + teks).
 */
async function renderSvg() {
    if (!svgDoc.value || !svgCanvasRef.value) return;
    await renderSvgToCanvas(svgDoc.value, svgCanvasRef.value, props.texturePath);
}

/**
 * Inisialisasi Fabric.js canvas untuk overlay gambar.
 * Canvas ini transparan dan di-layer di atas SVG canvas.
 */
function initFabric() {
    if (!fabricCanvasRef.value || fabricCanvas.value) return;

    const fc = new FabricCanvas(fabricCanvasRef.value, {
        width: canvasWidth.value,
        height: canvasHeight.value,
        backgroundColor: 'transparent',
        selection: true,
    });

    fabricCanvas.value = fc;

    // Re-compose texture setiap kali Fabric di-render
    fc.on('after:render', () => composeTexture());

    // Emit selection events
    fc.on('selection:created', (e) => emit('selection', e.selected?.[0] || null));
    fc.on('selection:updated', (e) => emit('selection', e.selected?.[0] || null));
    fc.on('selection:cleared', () => emit('selection', null));
}

/**
 * Gabungkan SVG canvas + Fabric canvas menjadi satu composite canvas.
 * Composite canvas inilah yang dijadikan texture untuk model 3D.
 */
function composeTexture() {
    if (!svgCanvasRef.value || !compositeCanvasRef.value) return;

    const composite = compositeCanvasRef.value;
    composite.width = canvasWidth.value;
    composite.height = canvasHeight.value;
    const ctx = composite.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, canvasWidth.value, canvasHeight.value);

    // Layer 1: SVG pattern (path + warna + texture + teks)
    ctx.drawImage(svgCanvasRef.value, 0, 0);

    // Layer 2: Fabric.js overlay (gambar yang ditambahkan user)
    if (fabricCanvas.value) {
        const fc = fabricCanvas.value as any;
        const fabricEl = fc.getElement();
        if (fabricEl) {
            // Gunakan dw, dh secara eksplisit untuk mencegah bug scaling devicePixelRatio (Retina display)
            // yang menyebabkan gambar menjadi raksasa dan bergeser di model 3D
            ctx.drawImage(fabricEl, 0, 0, canvasWidth.value, canvasHeight.value);
        }
    }

    // Emit composite canvas ke parent untuk texture 3D
    emit('canvas-update', composite);
}

// ─── Aksi Publik ───

/**
 * Ubah warna zona tertentu dalam SVG.
 */
async function setZoneColor(zoneId: string, color: string) {
    if (!svgDoc.value) return;
    updateSvgZoneColor(svgDoc.value, zoneId, color);
    // Update ref lokal
    const zone = zones.value.find((z) => z.id === zoneId);
    if (zone) zone.fill = color;
    await renderSvg();
    composeTexture();
}

/**
 * Ubah semua zona warna sekaligus (ganti warna kaos keseluruhan).
 */
async function setAllZonesColor(color: string) {
    if (!svgDoc.value) return;
    updateAllZoneColors(svgDoc.value, color);
    zones.value.forEach((z) => { z.fill = color; });
    await renderSvg();
    composeTexture();
}

/**
 * Ubah properti teks dalam SVG.
 */
async function setSvgText(
    textId: string,
    options: {
        text?: string;
        fontFamily?: string;
        fontSize?: string;
        fill?: string;
    },
) {
    if (!svgDoc.value) return;
    updateSvgText(svgDoc.value, textId, options);
    // Update ref lokal
    const t = texts.value.find((tx) => tx.id === textId);
    if (t) {
        if (options.text !== undefined) t.text = options.text;
        if (options.fontFamily !== undefined) t.fontFamily = options.fontFamily;
        if (options.fontSize !== undefined) t.fontSize = options.fontSize;
        if (options.fill !== undefined) t.fill = options.fill;
    }
    await renderSvg();
    composeTexture();
}

/**
 * Tambahkan gambar ke overlay (Fabric.js)
 */
async function addImage(file: File, fileId?: string) {
    if (!fabricCanvas.value) return;

    const reader = new FileReader();
    reader.onload = async (e) => {
        const dataUrl = e.target?.result as string;
        if (!dataUrl) return;

        try {
            const img = await FabricImage.fromURL(dataUrl);
            const maxDim = Math.min(canvasWidth.value, canvasHeight.value) * 0.25;
            const scale = Math.min(maxDim / (img.width || 1), maxDim / (img.height || 1));

            objectOffset = (objectOffset + 100) % 600;

            img.set({
                scaleX: scale,
                scaleY: scale,
                left: (canvasWidth.value / 2) + objectOffset - 300,
                top: (canvasHeight.value / 2) + objectOffset - 300,
                originX: 'center',
                originY: 'center',
                kisanakId: fileId,
            });
            fabricCanvas.value?.add(img);
            fabricCanvas.value?.setActiveObject(img);
            fabricCanvas.value?.renderAll();
        } catch (err) {
            console.error('[DesignEditor] Gagal memuat gambar:', err);
        }
    };
    reader.readAsDataURL(file);
}

/**
 * Tambahkan teks baru (Fabric.js)
 */
function addText(text: string) {
    if (!fabricCanvas.value) return;
    objectOffset = (objectOffset + 100) % 600;
    const t = new IText(text, {
        left: (canvasWidth.value / 2) + objectOffset - 300,
        top: (canvasHeight.value / 2) + objectOffset - 300,
        originX: 'center',
        originY: 'center',
        fontFamily: 'Arial',
        fontSize: 80,
        fill: '#ffffff',
    });
    fabricCanvas.value.add(t);
    fabricCanvas.value.setActiveObject(t);
    fabricCanvas.value.renderAll();
}

/**
 * Update text dari active object
 */
function updateActiveText(options: { text?: string; fontFamily?: string; fontSize?: string; fill?: string }) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof IText) {
        if (options.text !== undefined) active.set('text', options.text);
        if (options.fontFamily !== undefined) active.set('fontFamily', options.fontFamily);
        if (options.fontSize !== undefined) active.set('fontSize', parseFloat(options.fontSize));
        if (options.fill !== undefined) active.set('fill', options.fill);
        fabricCanvas.value.renderAll();
    }
}

/**
 * Toggle bold pada teks aktif
 */
function setActiveBold(bold: boolean) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof IText) {
        active.set('fontWeight', bold ? 'bold' : 'normal');
        fabricCanvas.value.renderAll();
    }
}

/**
 * Toggle italic pada teks aktif
 */
function setActiveItalic(italic: boolean) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof IText) {
        active.set('fontStyle', italic ? 'italic' : 'normal');
        fabricCanvas.value.renderAll();
    }
}

/**
 * Atur skala gambar/teks aktif.
 */
function setActiveScale(scale: number) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active) {
        active.set({ scaleX: scale, scaleY: scale });
        fabricCanvas.value.renderAll();
    }
}

/**
 * Atur rotasi gambar/teks aktif.
 */
function setActiveRotation(angle: number) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active) {
        active.set({ angle });
        fabricCanvas.value.renderAll();
    }
}

/**
 * Atur cermin horizontal (Flip X) untuk gambar/teks aktif.
 */
function setActiveFlipX(flip: boolean) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active) {
        active.set({ flipX: flip });
        fabricCanvas.value.renderAll();
    }
}


/**
 * Hapus objek terpilih di Fabric canvas.
 */
function deleteSelected() {
    if (!fabricCanvas.value) return;
    const objs = fabricCanvas.value.getActiveObjects();
    if (objs.length > 0) {
        objs.forEach((o) => fabricCanvas.value!.remove(o));
        fabricCanvas.value.discardActiveObject();
        fabricCanvas.value.renderAll();
    }
}

/**
 * Bersihkan Fabric canvas (hapus semua gambar overlay).
 */
function clearImages() {
    if (!fabricCanvas.value) return;
    fabricCanvas.value.clear();
    fabricCanvas.value.backgroundColor = 'transparent';
    fabricCanvas.value.renderAll();
}

/**
 * Reset seluruh desain (warna, teks, gambar).
 */
async function resetDesign() {
    clearImages();
}

/**
 * Mendapatkan data desain dalam format JSON untuk disimpan ke database.
 */
function getDesignJson(): object {
    const zoneColors: Record<string, string> = {};
    zones.value.forEach((z) => { zoneColors[z.id] = z.fill; });

    const fabricObjects = fabricCanvas.value ? fabricCanvas.value.toJSON() : null;

    return {
        patternPath: props.patternPath,
        texturePath: props.texturePath,
        baseColor: props.selectedColor,
        zoneColors,
        fabricObjects,
    };
}

/**
 * Mendapatkan daftar teks yang ada di canvas (untuk disimpan ke tabel teks).
 */
function getTexts(): { text: string }[] {
    if (!fabricCanvas.value) return [];

    return fabricCanvas.value.getObjects()
        .filter(obj => obj.type === 'i-text' && (obj as any).text)
        .map(obj => ({ text: (obj as any).text }));
}

/**
 * Mendapatkan composite canvas element.
 */
function getCompositeCanvas(): HTMLCanvasElement | null {
    return compositeCanvasRef.value;
}

// ─── Lifecycle ───

onBeforeUnmount(() => {
    if (fabricCanvas.value) fabricCanvas.value.dispose();
});

// ─── Expose API ───
defineExpose({
    setZoneColor,
    setAllZonesColor,
    setSvgText,
    addText,
    updateActiveText,
    setActiveBold,
    setActiveItalic,
    addImage,
    setActiveScale,
    setActiveRotation,
    setActiveFlipX,
    deleteSelected,
    clearImages,
    resetDesign,
    getDesignJson,
    getTexts,
    getCompositeCanvas,
});
</script>

<template>
    <div ref="containerRef" class="design-editor">
        <!-- Loading state -->
        <div v-if="isLoading" class="design-editor__loading">
            <div class="design-editor__spinner"></div>
            <span>Memuat pattern...</span>
        </div>

        <!-- Error state -->
        <div v-else-if="loadError" class="design-editor__error">
            <span>⚠️ {{ loadError }}</span>
        </div>

        <!-- Canvas layers (stacked via CSS) -->
        <div ref="stackRef" class="design-editor__canvas-stack">
            <!-- Layer 1: SVG pattern render (visible preview) -->
            <canvas ref="svgCanvasRef" class="design-editor__layer design-editor__layer--svg"></canvas>

            <!-- Layer 2: Fabric.js overlay (gambar user) — interactive -->
            <canvas ref="fabricCanvasRef" class="design-editor__layer--fabric"></canvas>
        </div>

        <!-- Composite canvas (offscreen, untuk texture 3D) -->
        <canvas ref="compositeCanvasRef" class="design-editor__composite"></canvas>
    </div>
</template>

<style scoped>
.design-editor {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a1a2e;
}

.design-editor__loading,
.design-editor__error {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #94a3b8;
    font-size: 14px;
    z-index: 5;
}

.design-editor__spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(148, 163, 184, 0.2);
    border-top-color: #94a3b8;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.design-editor__error {
    color: #f87171;
}

.design-editor__canvas-stack {
    position: relative;
    /* Lebar/tinggi dikontrol oleh ResizeObserver + updateStackSize() */
    margin: 0 auto;
}

/* Layer SVG — hanya visual, tidak interaktif */
.design-editor__layer--svg {
    position: absolute;
    top: 0;
    left: 0;
    /* Ukuran CSS dikontrol oleh updateStackSize() via JS */
    z-index: 1;
    pointer-events: none;
}

/*
 * Layer Fabric — JANGAN override width/height di sini!
 * Fabric.js mengontrol ukurannya sendiri via setDimensions().
 * Jika kita paksa dengan CSS !important, koordinat mouse akan rusak.
 */
.design-editor__layer--fabric {
    /* Kelas ini ada di elemen canvas asli sebelum di-wrap Fabric */
}

/* Fabric.js wraps canvas in .canvas-container div */
.design-editor__canvas-stack :deep(.canvas-container) {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 2;
    /* width & height dikelola oleh Fabric.setDimensions({ cssOnly: true }) */
}

/* Composite canvas — offscreen, hanya untuk texture 3D */
.design-editor__composite {
    position: absolute;
    left: -9999px;
    top: -9999px;
    width: 0;
    height: 0;
    pointer-events: none;
    opacity: 0;
}
</style>
