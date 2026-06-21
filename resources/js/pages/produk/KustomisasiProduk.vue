<!--
  Halaman KustomisasiProduk — Kustomisasi Desain Produk 3D
  ==========================================================
  Layout: Kiri = Model 3D (Three.js), Kanan = Panel kontrol bertab

  Tab yang tersedia:
  1. Warna  — Pilih warna kaos dari palet (data dari tabel warna, format CMYK)
  2. Teks   — Tambah & edit teks (font, ukuran, style, alignment, warna)
  3. Gambar — Upload gambar, resize, rotate

  Fitur umum:
  - Reset desain
  - Download screenshot PNG
  - Rotasi & zoom model 3D (orbit controls)
  - Tombol kembali ke halaman detail produk
-->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    PhArrowLeft,
    PhCamera,
    PhArrowCounterClockwise,
} from '@phosphor-icons/vue';
import TshirtViewer from '@/components/customizer/TshirtViewer.vue';
import DesignEditor from '@/components/customizer/DesignEditor.vue';
import Button from '@/components/Button.vue';
import { cmykToHex, PRESET_COLORS } from '@/lib/colorUtils';
import type * as THREE from 'three';

// ─── Tipe Data ───

/** Opsi warna dari tabel warna (dikirim oleh controller) */
type WarnaOption = {
    id: number;
    nama: string;
    kode: string; // Format CMYK: "C,M,Y,K"
};

/** Data produk yang sedang dikustomisasi */
type ProdukData = {
    id: number;
    nama: string;
    harga: number | string;
    gambar: string | null;
    deskripsi: string | null;
};

const props = defineProps<{
    produk?: ProdukData;
    warnaOptions?: WarnaOption[];
}>();

// ─── Referensi Komponen ───
const viewerRef = ref<InstanceType<typeof TshirtViewer> | null>(null);
const editorRef = ref<InstanceType<typeof DesignEditor> | null>(null);
const threeRenderer = ref<THREE.WebGLRenderer | null>(null);

// ─── Tab Aktif ───
type TabId = 'warna' | 'teks' | 'gambar';
const activeTab = ref<TabId>('warna');

const tabs: { id: TabId; label: string }[] = [
    { id: 'warna', label: 'Warna' },
    { id: 'teks', label: 'Teks' },
    { id: 'gambar', label: 'Gambar' },
];

// ═══════════════════════════════════════════
// TAB WARNA — Palet warna dari database
// ═══════════════════════════════════════════

/** Palet warna: gunakan data dari database, fallback ke preset */
const colorPalette = computed(() => {
    if (props.warnaOptions && props.warnaOptions.length > 0) {
        return props.warnaOptions.map((w) => ({
            id: w.id,
            name: w.nama,
            cmyk: w.kode,
            hex: cmykToHex(w.kode),
        }));
    }
    return PRESET_COLORS.map((c, i) => ({
        id: i,
        name: c.name,
        cmyk: '',
        hex: c.hex,
    }));
});

const selectedColorIndex = ref(0);
const selectedColor = computed(() => colorPalette.value[selectedColorIndex.value]?.hex ?? '#CCCCCC');

function selectColor(index: number) {
    selectedColorIndex.value = index;
}

// ═══════════════════════════════════════════
// TAB TEKS — Kontrol teks lengkap
// ═══════════════════════════════════════════

const textInput = ref('Teks Anda');
const textFontFamily = ref('Arial');
const textFontSize = ref(40);
const textFontWeight = ref<'normal' | 'bold'>('normal');
const textFontStyle = ref<'normal' | 'italic'>('normal');
const textAlign = ref<'left' | 'center' | 'right'>('center');
const textColor = ref('#FFFFFF');

/** Daftar font yang tersedia */
const fontFamilies = [
    'Arial',
    'Helvetica',
    'Times New Roman',
    'Georgia',
    'Courier New',
    'Verdana',
    'Impact',
    'Comic Sans MS',
];

/** Ukuran font yang tersedia */
const fontSizes = [12, 16, 20, 24, 28, 32, 36, 40, 48, 56, 64, 72, 80, 96];

/** Tambahkan teks baru ke canvas */
function handleAddText() {
    editorRef.value?.addText({
        text: textInput.value,
        fontFamily: textFontFamily.value,
        fontSize: textFontSize.value,
        fontWeight: textFontWeight.value,
        fontStyle: textFontStyle.value,
        textAlign: textAlign.value,
        fill: textColor.value,
    });
}

/** Update teks yang sedang aktif/terpilih di canvas */
function handleUpdateText() {
    editorRef.value?.updateActiveText({
        text: textInput.value,
        fontFamily: textFontFamily.value,
        fontSize: textFontSize.value,
        fontWeight: textFontWeight.value,
        fontStyle: textFontStyle.value,
        textAlign: textAlign.value,
        fill: textColor.value,
    });
}

// ═══════════════════════════════════════════
// TAB GAMBAR — Upload, resize, rotate
// ═══════════════════════════════════════════

const fileInputRef = ref<HTMLInputElement | null>(null);
const imageScale = ref(1.0);
const imageRotation = ref(0);

/** Buka dialog pilih file */
function triggerUpload() {
    fileInputRef.value?.click();
}

/** Handle file yang dipilih */
function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file && editorRef.value) {
        editorRef.value.addImage(file);
        // Reset slider ke default
        imageScale.value = 1.0;
        imageRotation.value = 0;
    }
    if (target) target.value = '';
}

/** Terapkan perubahan skala ke gambar aktif */
function handleImageScale() {
    editorRef.value?.setActiveImageScale(imageScale.value);
}

/** Terapkan perubahan rotasi ke gambar aktif */
function handleImageRotation() {
    editorRef.value?.setActiveImageRotation(imageRotation.value);
}

// ═══════════════════════════════════════════
// AKSI UMUM
// ═══════════════════════════════════════════

/** Canvas Fabric.js untuk texture 3D */
const designCanvas = ref<HTMLCanvasElement | null>(null);

function onCanvasUpdate(canvas: HTMLCanvasElement) {
    designCanvas.value = canvas;
}

function onRendererReady(renderer: THREE.WebGLRenderer) {
    threeRenderer.value = renderer;
}

/** Hapus objek terpilih di canvas */
function deleteSelected() {
    editorRef.value?.deleteSelected();
}

/** Reset semua desain */
function clearDesign() {
    editorRef.value?.clearCanvas();
}

/** Download screenshot model 3D sebagai PNG */
function downloadScreenshot() {
    if (!threeRenderer.value) return;
    threeRenderer.value.domElement.toBlob((blob) => {
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `kustomisasi-${props.produk?.nama ?? 'produk'}.png`;
        a.click();
        URL.revokeObjectURL(url);
    }, 'image/png');
}

/** Kembali ke halaman detail produk */
function goBack() {
    if (props.produk?.id) {
        router.visit(`/katalog/produk/${props.produk.id}`);
    } else {
        router.visit('/katalog');
    }
}

const productName = computed(() => props.produk?.nama ?? 'Kustomisasi Produk');
</script>

<template>

    <Head :title="`Kustomisasi - ${productName}`" />

    <div class="customizer-page">
        <!-- ═══ KIRI: Model 3D ═══ -->
        <div class="customizer-left">
            <!-- Logo -->
            <div class="customizer-logo">
                <Link href="/" aria-label="Beranda">
                    <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="logo-img" />
                </Link>
            </div>

            <!-- Penampil 3D -->
            <div class="viewer-container">
                <TshirtViewer ref="viewerRef" :color="selectedColor" :design-canvas="designCanvas"
                    model-path="/models/tshirt2.glb" @renderer-ready="onRendererReady" />
            </div>

            <!-- Toolbar bawah kiri -->
            <div class="toolbar-bottom">
                <Button variant="primary" @click="clearDesign">
                    <PhArrowCounterClockwise :size="16" />
                </Button>
            </div>
        </div>

        <!-- ═══ KANAN: Panel Kontrol ═══ -->
        <div class="customizer-right">
            <!-- Header: Tombol Go Back & Download -->
            <div class="panel-header">
                <Button variant="primary" @click="goBack">
                    <PhArrowLeft :size="16" />
                </Button>
                <Button variant="primary" @click="downloadScreenshot">
                    <PhCamera :size="16" />
                </Button>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button v-for="tab in tabs" :key="tab.id" :id="`tab-${tab.id}`" class="tab-btn"
                    :class="{ 'tab-btn--active': activeTab === tab.id }" @click="activeTab = tab.id">
                    {{ tab.label }}
                </button>
            </div>

            <!-- ─── Tab Content: WARNA ─── -->
            <div v-show="activeTab === 'warna'" class="tab-content">
                <div class="section-label">Pilih Warna Kaos</div>
                <div class="color-list">
                    <button v-for="(c, i) in colorPalette" :key="c.id" :id="`color-${c.id}`" class="color-item"
                        :class="{ 'color-item--active': selectedColorIndex === i }" @click="selectColor(i)">
                        <span class="color-dot" :style="{ backgroundColor: c.hex }"></span>
                        <span class="color-info">
                            <span class="color-name">{{ c.name }}</span>
                            <span v-if="c.cmyk" class="color-cmyk">({{ c.cmyk }})</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- ─── Tab Content: TEKS ─── -->
            <div v-show="activeTab === 'teks'" class="tab-content">
                <div class="section-label">Tambah / Edit Teks</div>

                <!-- Input Teks -->
                <div class="form-group">
                    <label class="form-label">Isi Teks</label>
                    <input id="text-input" v-model="textInput" type="text" class="form-input"
                        placeholder="Ketik teks di sini..." />
                </div>

                <!-- Font Family -->
                <div class="form-group">
                    <label class="form-label">Font</label>
                    <select id="text-font" v-model="textFontFamily" class="form-select">
                        <option v-for="f in fontFamilies" :key="f" :value="f">{{ f }}</option>
                    </select>
                </div>

                <!-- Font Size -->
                <div class="form-group">
                    <label class="form-label">Ukuran Font</label>
                    <select id="text-size" v-model.number="textFontSize" class="form-select">
                        <option v-for="s in fontSizes" :key="s" :value="s">{{ s }}px</option>
                    </select>
                </div>

                <!-- Font Style: Bold, Italic -->
                <div class="form-group">
                    <label class="form-label">Gaya Font</label>
                    <div class="btn-row">
                        <Button id="text-bold" variant="option" :active="textFontWeight === 'bold'"
                            @click="textFontWeight = textFontWeight === 'bold' ? 'normal' : 'bold'">
                            <strong>B</strong>
                        </Button>
                        <Button id="text-italic" variant="option" :active="textFontStyle === 'italic'"
                            @click="textFontStyle = textFontStyle === 'italic' ? 'normal' : 'italic'">
                            <em>I</em>
                        </Button>
                    </div>
                </div>

                <!-- Alignment -->
                <div class="form-group">
                    <label class="form-label">Perataan</label>
                    <div class="btn-row">
                        <Button id="text-align-left" variant="option" :active="textAlign === 'left'"
                            @click="textAlign = 'left'">
                            Kiri
                        </Button>
                        <Button id="text-align-center" variant="option" :active="textAlign === 'center'"
                            @click="textAlign = 'center'">
                            Tengah
                        </Button>
                        <Button id="text-align-right" variant="option" :active="textAlign === 'right'"
                            @click="textAlign = 'right'">
                            Kanan
                        </Button>
                    </div>
                </div>

                <!-- Warna Teks -->
                <div class="form-group">
                    <label class="form-label">Warna Teks</label>
                    <input id="text-color" v-model="textColor" type="color" class="form-color" />
                </div>

                <!-- Tombol Aksi Teks -->
                <div class="form-group btn-row">
                    <Button variant="solid" @click="handleAddText">
                        Tambah Teks Baru
                    </Button>
                    <Button variant="primary" @click="handleUpdateText">
                        Perbarui Terpilih
                    </Button>
                </div>
            </div>

            <!-- ─── Tab Content: GAMBAR ─── -->
            <div v-show="activeTab === 'gambar'" class="tab-content">
                <div class="section-label">Tambah / Edit Gambar</div>

                <!-- Upload -->
                <div class="form-group">
                    <label class="form-label">Pilih Gambar</label>
                    <Button variant="primary" @click="triggerUpload">
                        Pilih File
                    </Button>
                    <input ref="fileInputRef" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                        class="hidden-input" @change="handleFileUpload" />
                </div>

                <!-- Resize -->
                <div class="form-group">
                    <label class="form-label">Ukuran Gambar</label>
                    <div class="slider-row">
                        <input id="image-scale" v-model.number="imageScale" type="range" min="0.1" max="3" step="0.05"
                            class="form-range" @input="handleImageScale" />
                        <span class="slider-val">{{ (imageScale * 100).toFixed(0) }}%</span>
                    </div>
                </div>

                <!-- Rotate -->
                <div class="form-group">
                    <label class="form-label">Rotasi Gambar</label>
                    <div class="slider-row">
                        <input id="image-rotation" v-model.number="imageRotation" type="range" min="0" max="360"
                            step="1" class="form-range" @input="handleImageRotation" />
                        <span class="slider-val">{{ imageRotation }}°</span>
                    </div>
                </div>

                <!-- Hapus Gambar Terpilih -->
                <div class="form-group">
                    <Button variant="primary" @click="deleteSelected">
                        Hapus Terpilih
                    </Button>
                </div>
            </div>
        </div>

        <!-- DesignEditor tersembunyi (canvas Fabric.js) -->
        <DesignEditor ref="editorRef" @canvas-update="onCanvasUpdate" />
    </div>
</template>

<style scoped>
/* ─── Layout Utama ─── */
.customizer-page {
    display: flex;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    font-family: 'Neue Montreal', 'Inter', sans-serif;
    background-color: #f5f0e8;
}

/* ─── Sisi Kiri: Model 3D ─── */
.customizer-left {
    position: relative;
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.customizer-logo {
    position: absolute;
    top: 24px;
    left: 28px;
    z-index: 10;
}

.logo-img {
    height: 36px;
    width: auto;
    filter: brightness(0);
}

.viewer-container {
    width: 100%;
    height: 100%;
}

.toolbar-bottom {
    position: absolute;
    bottom: 28px;
    left: 28px;
    z-index: 10;
    display: flex;
    gap: 8px;
}

.tool-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(31, 27, 30, 0.06);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(31, 27, 30, 0.1);
    border-radius: 8px;
    color: #1f1b1e;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tool-btn:hover {
    background: rgba(31, 27, 30, 0.14);
    transform: translateY(-1px);
}

/* ─── Sisi Kanan: Panel Kontrol ─── */
.customizer-right {
    width: 380px;
    min-width: 380px;
    background: #ffffff;
    border-left: 1px solid #e8e4df;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ─── Header Panel ─── */
.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e8e4df;
}

.customizer-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background-color: #e8a94e;
    color: #1f1b1e;
    border: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    cursor: pointer;
    transition: all 0.15s ease;
    text-transform: uppercase;
}

.customizer-btn:hover {
    background-color: #d4962e;
}

/* ─── Tab Navigation ─── */
.tab-nav {
    display: flex;
    border-bottom: 1px solid #e8e4df;
}

.tab-btn {
    flex: 1;
    padding: 12px 8px;
    font-size: 13px;
    font-weight: 600;
    color: #888;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.tab-btn:hover {
    color: #1f1b1e;
}

.tab-btn--active {
    color: #1f1b1e;
    border-bottom-color: #1f1b1e;
}

/* ─── Tab Content ─── */
.tab-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}

.section-label {
    font-size: 13px;
    font-weight: 700;
    color: #1f1b1e;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 14px;
}

/* ─── Color List (Tab Warna) ─── */
.color-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.color-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    transition: all 0.12s ease;
    text-align: left;
}

.color-item:hover {
    background: #f5f0e8;
}

.color-item--active {
    background: #f5f0e8;
    border-color: #1f1b1e;
}

.color-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 2px solid rgba(0, 0, 0, 0.1);
}

.color-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.color-name {
    font-size: 13px;
    font-weight: 600;
    color: #1f1b1e;
    line-height: 1.2;
}

.color-cmyk {
    font-size: 11px;
    color: #888;
    line-height: 1.2;
}

/* ─── Form Controls (Tab Teks & Gambar) ─── */
.form-group {
    margin-bottom: 14px;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.form-input,
.form-select {
    width: 100%;
    padding: 8px 10px;
    font-size: 13px;
    border: 1px solid #d4d0cb;
    border-radius: 4px;
    background: #fff;
    color: #1f1b1e;
    outline: none;
    transition: border-color 0.15s ease;
}

.form-input:focus,
.form-select:focus {
    border-color: #1f1b1e;
}

.form-color {
    width: 48px;
    height: 32px;
    padding: 0;
    border: 1px solid #d4d0cb;
    border-radius: 4px;
    cursor: pointer;
    background: none;
}

.btn-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.slider-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-range {
    flex: 1;
    accent-color: #1f1b1e;
}

.slider-val {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    min-width: 48px;
    text-align: right;
}

/* ─── Utility ─── */
.hidden-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0;
    height: 0;
}
</style>
