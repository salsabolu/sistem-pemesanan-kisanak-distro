<!--
  Komponen DesignEditor — Editor Desain 2D (Fabric.js)
  =====================================================
  Canvas Fabric.js tersembunyi yang digunakan untuk membuat desain.
  Output canvas ini diterapkan sebagai texture pada model 3D.

  Fitur:
  - Tambah teks dengan pengaturan font, ukuran, style, alignment, warna
  - Tambah gambar dengan resize & rotate
  - Hapus objek terpilih
  - Reset semua desain
-->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, shallowRef } from 'vue';
import { Canvas as FabricCanvas, FabricImage, FabricText } from 'fabric';

const emit = defineEmits<{
    (e: 'canvas-update', canvas: HTMLCanvasElement): void;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const fabricCanvas = shallowRef<FabricCanvas | null>(null);

/** Ukuran canvas desain (resolusi texture) */
const CANVAS_SIZE = 512;

/** Inisialisasi canvas Fabric.js */
function initFabric() {
    if (!canvasRef.value) return;

    const fc = new FabricCanvas(canvasRef.value, {
        width: CANVAS_SIZE,
        height: CANVAS_SIZE,
        backgroundColor: 'transparent',
        selection: true,
    });

    fabricCanvas.value = fc;

    // Emit update setiap kali canvas di-render
    fc.on('after:render', () => emitCanvasUpdate());

    // Emit awal
    emitCanvasUpdate();
}

/** Kirim referensi canvas ke parent untuk digunakan sebagai texture */
function emitCanvasUpdate() {
    if (!canvasRef.value) return;
    emit('canvas-update', canvasRef.value);
}

/**
 * Tambahkan gambar ke canvas dari file yang dipilih user.
 * Gambar otomatis di-scale agar muat dalam canvas.
 */
async function addImage(file: File) {
    if (!fabricCanvas.value) return;

    const url = URL.createObjectURL(file);
    try {
        const img = await FabricImage.fromURL(url);
        const maxDim = CANVAS_SIZE * 0.5;
        const scale = Math.min(maxDim / (img.width || 1), maxDim / (img.height || 1));
        img.set({
            scaleX: scale,
            scaleY: scale,
            left: CANVAS_SIZE / 2,
            top: CANVAS_SIZE / 2,
            originX: 'center',
            originY: 'center',
        });
        fabricCanvas.value.add(img);
        fabricCanvas.value.setActiveObject(img);
        fabricCanvas.value.renderAll();
    } catch (err) {
        console.error('[DesignEditor] Gagal memuat gambar:', err);
    }
}

/**
 * Tambahkan teks ke canvas dengan opsi yang bisa dikustomisasi.
 */
function addText(options: {
    text?: string;
    fontFamily?: string;
    fontSize?: number;
    fontWeight?: string;
    fontStyle?: string;
    textAlign?: 'left' | 'center' | 'right';
    fill?: string;
} = {}) {
    if (!fabricCanvas.value) return;

    const fabricText = new FabricText(options.text || 'Teks Anda', {
        left: CANVAS_SIZE / 2,
        top: CANVAS_SIZE / 2,
        originX: 'center',
        originY: 'center',
        fontSize: options.fontSize || 40,
        fontFamily: options.fontFamily || 'Arial',
        fill: options.fill || '#FFFFFF',
        fontWeight: (options.fontWeight as '' | 'bold' | 'normal') || 'normal',
        fontStyle: (options.fontStyle as '' | 'italic' | 'normal') || 'normal',
        textAlign: options.textAlign || 'center',
    });

    fabricCanvas.value.add(fabricText);
    fabricCanvas.value.setActiveObject(fabricText);
    fabricCanvas.value.renderAll();
}

/**
 * Perbarui properti objek teks yang sedang aktif/terpilih.
 */
function updateActiveText(props: Record<string, unknown>) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof FabricText) {
        active.set(props);
        fabricCanvas.value.renderAll();
    }
}

/**
 * Atur skala gambar yang sedang aktif/terpilih.
 * @param scale - Nilai skala (0.1 - 3.0)
 */
function setActiveImageScale(scale: number) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof FabricImage) {
        active.set({ scaleX: scale, scaleY: scale });
        fabricCanvas.value.renderAll();
    }
}

/**
 * Atur rotasi gambar yang sedang aktif/terpilih.
 * @param angle - Sudut rotasi dalam derajat
 */
function setActiveImageRotation(angle: number) {
    if (!fabricCanvas.value) return;
    const active = fabricCanvas.value.getActiveObject();
    if (active && active instanceof FabricImage) {
        active.set({ angle });
        fabricCanvas.value.renderAll();
    }
}

/** Hapus semua objek yang sedang terpilih */
function deleteSelected() {
    if (!fabricCanvas.value) return;
    const objs = fabricCanvas.value.getActiveObjects();
    if (objs.length > 0) {
        objs.forEach((o) => fabricCanvas.value!.remove(o));
        fabricCanvas.value.discardActiveObject();
        fabricCanvas.value.renderAll();
    }
}

/** Bersihkan seluruh canvas (reset desain) */
function clearCanvas() {
    if (!fabricCanvas.value) return;
    fabricCanvas.value.clear();
    fabricCanvas.value.backgroundColor = 'transparent';
    fabricCanvas.value.renderAll();
}

/** Mendapatkan referensi elemen canvas HTML */
function getCanvasElement(): HTMLCanvasElement | null {
    return canvasRef.value;
}

/** Mendapatkan tipe objek yang sedang aktif */
function getActiveObjectType(): 'text' | 'image' | null {
    if (!fabricCanvas.value) return null;
    const active = fabricCanvas.value.getActiveObject();
    if (!active) return null;
    if (active instanceof FabricText) return 'text';
    if (active instanceof FabricImage) return 'image';
    return null;
}

onMounted(() => initFabric());

onBeforeUnmount(() => {
    if (fabricCanvas.value) fabricCanvas.value.dispose();
});

defineExpose({
    addImage,
    addText,
    updateActiveText,
    setActiveImageScale,
    setActiveImageRotation,
    deleteSelected,
    clearCanvas,
    getCanvasElement,
    getActiveObjectType,
});
</script>

<template>
    <!-- Canvas tersembunyi (offscreen), dirender oleh Fabric.js -->
    <div class="design-editor-wrapper">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>

<style scoped>
.design-editor-wrapper {
    position: absolute;
    left: -9999px;
    top: -9999px;
    width: 512px;
    height: 512px;
    pointer-events: none;
    opacity: 0;
}
</style>
