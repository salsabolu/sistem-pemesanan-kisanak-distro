<!--
  Halaman KustomisasiProduk — T-Shirt Customization Studio
  ==========================================================
  Layout 3 kolom:
  1. Sidebar Kiri  = Panel kontrol (Warna, Teks, Gambar)
  2. Tengah        = Canvas Editor 2D (SVG Pattern)
  3. Kanan         = 3D Preview (Three.js + OBJ)

  Fitur:
  - Warna kaos dari tabel warna (seeder)
  - Tambah & edit teks SVG
  - Upload gambar overlay
  - Simpan desain ke database (desain_json, teks, gambar)
  - Download screenshot PNG
  - Rotasi & zoom model 3D
-->
<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    PhArrowLeft,
    PhCamera,
    PhArrowCounterClockwise,
    PhPalette,
    PhTextT,
    PhImage,
    PhFloppyDisk,
    PhTrash,
    PhTextBolder,
    PhTextItalic,
    PhShoppingCart,
    PhArrowsLeftRight,
    PhUploadSimple,
} from '@phosphor-icons/vue';
import TshirtViewer from '@/components/customizer/TshirtViewer.vue';
import DesignEditor from '@/components/customizer/DesignEditor.vue';
import Button from '@/components/Button.vue';
import Alert from '@/components/Alert.vue';
import { cmykToHex } from '@/lib/colorUtils';
import type { SvgZone, SvgText } from '@/lib/svgPatternUtils';
import type * as THREE from 'three';

// ─── Tipe Data ───

/** Opsi warna dari tabel warna (dikirim oleh controller) */
type WarnaOption = {
    id: number;
    nama: string;
    kode: string; // Format CMYK: "C,M,Y,K"
};

/** Opsi ukuran dari tabel ukuran */
type UkuranOption = {
    id: number;
    nama: string;
};

/** Varian produk */
type ProdukVariant = {
    id: number;
    harga: number | string;
    warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string } | null;
};

/** Data produk yang sedang dikustomisasi */
type ProdukData = {
    id: number;
    nama: string;
    harga: number | string;
    gambar: string | null;
    deskripsi: string | null;
};

const page = usePage<any>();

const props = defineProps<{
    produk?: ProdukData;
    warnaOptions?: WarnaOption[];
    ukuranOptions?: UkuranOption[];
    selectedWarna?: string;
    selectedUkuran?: string;
    variants?: ProdukVariant[];
}>();

// ─── Referensi Komponen ───
const viewerRef = ref<InstanceType<typeof TshirtViewer> | null>(null);
const editorRef = ref<InstanceType<typeof DesignEditor> | null>(null);
const threeRenderer = ref<THREE.WebGLRenderer | null>(null);

// ─── Tab Aktif ───
type TabId = 'warna' | 'teks' | 'gambar';
const activeTab = ref<TabId>('warna');

const showAlert = ref(false);
const alertMessage = ref('');
const alertType = ref<'success' | 'error' | 'warning' | 'info'>('info');

// ─── Variant Selection ───
const currentWarna = ref(props.selectedWarna || '');
const currentUkuran = ref(props.selectedUkuran || '');

const availableWarnas = computed(() => {
    if (!props.variants) return [];
    const warnas = new Set(props.variants.map(v => v.warna?.nama).filter(Boolean));
    return Array.from(warnas);
});

const availableUkurans = computed(() => {
    if (!props.variants) return [];
    const ukurans = new Set(props.variants
        .filter(v => v.warna?.nama === currentWarna.value)
        .map(v => v.ukuran?.nama)
        .filter(Boolean));
    if (ukurans.size === 0) {
        return Array.from(new Set(props.variants.map(v => v.ukuran?.nama).filter(Boolean)));
    }
    return Array.from(ukurans);
});

watch(currentWarna, (newWarna, oldWarna) => {
    if (newWarna) {
        const idx = colorPalette.value.findIndex(c => c.name === newWarna);
        if (idx !== -1 && idx !== selectedColorIndex.value) {
            selectedColorIndex.value = idx;
        }

        if (oldWarna && newWarna !== oldWarna) {
            alertMessage.value = `Warna kaos diubah menjadi ${newWarna}.`;
            alertType.value = 'info';
            showAlert.value = true;
        }

        const validVariants = props.variants?.filter(v => v.warna?.nama === newWarna) || [];
        const validUkurans = validVariants.map(v => v.ukuran?.nama).filter(Boolean);
        if (!validUkurans.includes(currentUkuran.value) && validUkurans.length > 0) {
            currentUkuran.value = validUkurans[0] as string;
        }
    }
});

watch(currentUkuran, (newUkuran, oldUkuran) => {
    if (newUkuran && oldUkuran && newUkuran !== oldUkuran) {
        alertMessage.value = `Ukuran kaos diubah menjadi ${newUkuran}.`;
        alertType.value = 'info';
        showAlert.value = true;
    }
});

const tabs: { id: TabId; label: string; icon: any }[] = [
    { id: 'warna', label: 'Warna', icon: PhPalette },
    { id: 'teks', label: 'Teks', icon: PhTextT },
    { id: 'gambar', label: 'Gambar', icon: PhImage },
];

// ═══════════════════════════════════════════
// TAB WARNA — Palet warna dari database (seeder)
// ═══════════════════════════════════════════

/** Palet warna dari tabel warna */
const colorPalette = computed(() => {
    if (!props.warnaOptions) return [];
    return props.warnaOptions.map((w) => {
        return {
            id: w.id,
            name: w.nama,
            hex: cmykToHex(w.kode),
        };
    });
});

const selectedColorIndex = ref(-1);

watch(colorPalette, (newPalette) => {
    if (newPalette.length > 0 && selectedColorIndex.value === -1 && currentWarna.value) {
        const initialIdx = newPalette.findIndex(c => c.name.trim().toLowerCase() === currentWarna.value.trim().toLowerCase());
        if (initialIdx !== -1) selectedColorIndex.value = initialIdx;
    }
}, { immediate: true });

const selectedColor = computed(() => {
    if (selectedColorIndex.value < 0) return undefined;
    return colorPalette.value[selectedColorIndex.value]?.hex;
});

function selectColor(index: number) {
    selectedColorIndex.value = index;
    const colorName = colorPalette.value[index]?.name;
    if (colorName && currentWarna.value !== colorName) {
        currentWarna.value = colorName;
    }
}

// ═══════════════════════════════════════════
// TAB TEKS — Kontrol teks SVG
// ═══════════════════════════════════════════

const hasSelectedText = ref(false);
const textInput = ref('');
const textFontFamily = ref('Arial');
const textFontSize = ref('80');
const textColor = ref('#FFFFFF');
const textBold = ref(false);
const textItalic = ref(false);
const textRotation = ref(0);
const textFlipX = ref(false);

/** Daftar font yang tersedia */
const fontFamilies = [
    'Arial', 'Helvetica', 'Times New Roman', 'Georgia',
    'Courier New', 'Verdana', 'Impact', 'Comic Sans MS',
    'Advent Pro', 'Kumar One', 'Roboto', 'Raleway',
];

/** Ukuran font yang tersedia */
const fontSizes = ['24', '32', '40', '48', '56', '64', '72', '80', '96', '120', '160', '200'];

function handleUpdateText() {
    if (hasSelectedText.value && editorRef.value) {
        editorRef.value.updateActiveText({
            text: textInput.value,
            fontFamily: textFontFamily.value,
            fontSize: textFontSize.value,
            fill: textColor.value,
        });
    }
}

function handleToggleBold() {
    textBold.value = !textBold.value;
    editorRef.value?.setActiveBold(textBold.value);
}

function handleToggleItalic() {
    textItalic.value = !textItalic.value;
    editorRef.value?.setActiveItalic(textItalic.value);
}

function handleTextRotation() {
    editorRef.value?.setActiveRotation(textRotation.value);
}

function handleToggleTextFlip() {
    textFlipX.value = !textFlipX.value;
    editorRef.value?.setActiveFlipX(textFlipX.value);
}

function addNewText() {
    editorRef.value?.addText('Teks Baru');
}

function handleSelection(obj: any) {
    if (obj && obj.type === 'i-text') {
        textInput.value = obj.text;
        textFontFamily.value = obj.fontFamily;
        textFontSize.value = String(Math.round(obj.fontSize));
        textColor.value = obj.fill;
        textBold.value = obj.fontWeight === 'bold';
        textItalic.value = obj.fontStyle === 'italic';
        textRotation.value = Math.round(obj.angle || 0);
        textFlipX.value = !!obj.flipX;
        hasSelectedText.value = true;
        activeTab.value = 'teks';
    } else {
        hasSelectedText.value = false;
    }

    if (obj && obj.type === 'image') {
        imageScale.value = obj.scaleX;
        imageRotation.value = Math.round(obj.angle || 0);
        imageFlipX.value = !!obj.flipX;
        activeTab.value = 'gambar';
    }
}

function onTextsLoaded(texts: SvgText[]) {
    // Kita tidak menggunakan elemen SVG text lagi,
    // sekarang kita menggunakan text interaktif Fabric.js
}

// ═══════════════════════════════════════════
// TAB GAMBAR — Upload, resize, rotate
// ═══════════════════════════════════════════

const fileInputRef = ref<HTMLInputElement | null>(null);
const imageScale = ref(1.0);
const imageRotation = ref(0);
const imageFlipX = ref(false);
const uploadedFiles = ref<File[]>([]);

function triggerUpload() {
    fileInputRef.value?.click();
}

function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file && editorRef.value) {
        const fileId = Math.random().toString(36).substring(2, 10);
        (file as any).kisanakId = fileId;
        editorRef.value.addImage(file, fileId);
        uploadedFiles.value.push(file);
        imageScale.value = 1.0;
        imageRotation.value = 0;
    }
    if (target) target.value = '';
}

function handleImageScale() {
    editorRef.value?.setActiveScale(imageScale.value);
}

function handleImageRotation() {
    editorRef.value?.setActiveRotation(imageRotation.value);
}

function handleToggleImageFlip() {
    imageFlipX.value = !imageFlipX.value;
    editorRef.value?.setActiveFlipX(imageFlipX.value);
}

// ═══════════════════════════════════════════
// AKSI UMUM
// ═══════════════════════════════════════════

const designCanvas = ref<HTMLCanvasElement | null>(null);
const isSaving = ref(false);

function onCanvasUpdate(canvas: HTMLCanvasElement) {
    designCanvas.value = canvas;
    viewerRef.value?.updateTexture();
}

function onRendererReady(r: THREE.WebGLRenderer) {
    threeRenderer.value = r;
}

/** Hapus objek terpilih di canvas */
function deleteSelected() {
    editorRef.value?.deleteSelected();
}

/** Reset semua desain */
function clearDesign() {
    editorRef.value?.resetDesign();
    selectedColorIndex.value = -1; // Reset warna aktif agar bisa diklik lagi
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

/** Simpan desain ke database */
async function saveDesign() {
    if (!editorRef.value || isSaving.value) return;
    isSaving.value = true;

    try {
        const designJson = editorRef.value.getDesignJson() as any;
        const texts = editorRef.value.getTexts();

        // Cari semua ID gambar yang masih ada di canvas
        const activeImageIds = designJson.fabricObjects?.objects
            ?.filter((o: any) => o.type === 'image' && o.kisanakId)
            ?.map((o: any) => o.kisanakId) || [];

        // Saring file yang diupload, HANYA ambil file yang belum dihapus dari canvas
        const finalFiles = uploadedFiles.value.filter(f => activeImageIds.includes((f as any).kisanakId));

        // Kirim data ke backend via Inertia
        router.post(`/katalog/produk/${props.produk?.id}/kustomisasi/simpan`, {
            desain_json: JSON.stringify(designJson),
            teks: texts.map((t) => ({ teks: t.text })),
            gambar_files: Array.from(finalFiles),
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    } catch (err) {
        console.error('[KustomisasiProduk] Gagal menyimpan desain:', err);
        isSaving.value = false;
    }
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

/** Path model — gunakan OBJ jika tersedia, fallback ke GLB */
const modelPath = computed(() => '/models/tshirt1.obj');
const patternPath = computed(() => '/patterns/pattern-tshirt1.svg');

// ═══════════════════════════════════════════
// CHECKOUT — Simpan desain lalu ke keranjang
// ═══════════════════════════════════════════

const isCheckingOut = ref(false);
const savedDesainId = ref<number | null>(null);

// Watch for desainId flash dari simpanDesain
watch(
    () => page.props.flash?.desainId,
    (id) => {
        if (id) savedDesainId.value = id;
    },
    { immediate: true }
);

function formatRupiah(value: number) {
    const rounded = Math.max(0, Math.round(value));
    const parts = String(rounded).split('');
    const out: string[] = [];
    for (let i = 0; i < parts.length; i += 1) {
        const idxFromEnd = parts.length - i;
        out.push(parts[i]);
        if (idxFromEnd > 1 && idxFromEnd % 3 === 1) out.push('.');
    }
    return `Rp${out.join('')}`;
}

/** Cari variant yang cocok berdasarkan warna & ukuran yang dipilih */
const matchedVariant = computed(() => {
    if (!props.variants || props.variants.length === 0) return undefined;
    return props.variants.find(
        (v) => (v.warna?.nama ?? '') === currentWarna.value &&
               (v.ukuran?.nama ?? '') === currentUkuran.value
    ) ?? props.variants[0];
});

const unitPrice = computed(() => {
    const raw = matchedVariant.value?.harga ?? props.produk?.harga;
    const n = typeof raw === 'number' ? raw : Number(raw);
    return Number.isFinite(n) ? n : 0;
});

/** Checkout: simpan desain → tambah ke cart → redirect ke keranjang */
async function handleCheckout() {
    if (!editorRef.value || isCheckingOut.value) return;
    isCheckingOut.value = true;

    try {
        // Jika desain belum disimpan, simpan dulu
        if (!savedDesainId.value) {
            const designJson = editorRef.value.getDesignJson();
            const texts = editorRef.value.getTexts();

            router.post(`/katalog/produk/${props.produk?.id}/kustomisasi/simpan`, {
                desain_json: JSON.stringify(designJson),
                teks: texts.map((t) => ({ teks: t.text })),
                gambar_files: uploadedFiles.value,
            }, {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const desainId = page.props.flash?.desainId;
                    if (desainId) {
                        savedDesainId.value = desainId;
                    }
                    addToCartAndRedirect();
                },
                onError: () => {
                    isCheckingOut.value = false;
                },
            });
        } else {
            addToCartAndRedirect();
        }
    } catch (err) {
        console.error('[KustomisasiProduk] Checkout gagal:', err);
        isCheckingOut.value = false;
    }
}

function addToCartAndRedirect() {
    const variantId = matchedVariant.value?.id ?? props.produk?.id ?? 0;
    const price = unitPrice.value;
    const cartItem = {
        id: `${variantId}_${currentWarna.value}_${currentUkuran.value}_custom_${Date.now()}`,
        productId: variantId,
        imageSrc: props.produk?.gambar ?? '/images/kaos-1.png',
        productName: productName.value,
        color: currentWarna.value,
        size: currentUkuran.value,
        unitPrice: price,
        unitPriceText: formatRupiah(price),
        quantity: 1,
        desainId: savedDesainId.value,
    };

    const raw = localStorage.getItem('kisanak_cart');
    const cart = raw ? JSON.parse(raw) : [];
    cart.push(cartItem);
    localStorage.setItem('kisanak_cart', JSON.stringify(cart));

    router.visit('/keranjang');
}
</script>

<template>

    <Head :title="`Kustomisasi - ${productName}`" />

    <Alert v-model:show="showAlert" :message="alertMessage" :type="alertType" />

    <div class="studio">
        <!-- ═══ SIDEBAR KIRI: Panel Kontrol ═══ -->
        <aside class="studio__sidebar">
            <!-- Header -->
            <div class="studio__sidebar-header">
                <button class="studio__icon-btn" @click="goBack" title="Kembali">
                    <PhArrowLeft :size="18" weight="bold" />
                </button>
                <h2 class="studio__title">{{ productName }}</h2>
                <div class="studio__header-actions">
                    <button class="studio__icon-btn" @click="downloadScreenshot" title="Screenshot">
                        <PhCamera :size="18" />
                    </button>
                    <button class="studio__icon-btn" @click="saveDesign" :disabled="isSaving" title="Simpan Desain">
                        <PhFloppyDisk :size="18" weight="bold" />
                    </button>
                </div>
            </div>

            <!-- Variant Info -->
            <div class="studio__variant-info">
                <div class="studio__variant-item" style="padding-top: 6px; padding-bottom: 6px;">
                    <span class="studio__variant-label">Warna</span>
                    <select v-model="currentWarna" class="studio__variant-select">
                        <option v-for="w in availableWarnas" :key="w" :value="w">{{ w }}</option>
                    </select>
                </div>
                <div class="studio__variant-item" style="padding-top: 6px; padding-bottom: 6px;">
                    <span class="studio__variant-label">Ukuran</span>
                    <select v-model="currentUkuran" class="studio__variant-select">
                        <option v-for="u in availableUkurans" :key="u" :value="u">{{ u }}</option>
                    </select>
                </div>
                <div class="studio__variant-item">
                    <span class="studio__variant-label">Harga</span>
                    <span class="studio__variant-value">{{ formatRupiah(unitPrice) }}</span>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="studio__tabs">
                <button v-for="tab in tabs" :key="tab.id" class="studio__tab"
                    :class="{ 'studio__tab--active': activeTab === tab.id }" @click="activeTab = tab.id">
                    <component :is="tab.icon" :size="18" />
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="studio__tab-content">

                <!-- ─── WARNA ─── -->
                <div v-show="activeTab === 'warna'" class="studio__panel">
                    <div class="studio__panel-title">Pilih Warna Kaos</div>
                    <p class="studio__panel-desc">Warna akan diterapkan ke seluruh zona pattern</p>

                    <div class="color-grid">
                        <button v-for="(c, i) in colorPalette" :key="c.id" class="color-swatch"
                            :class="{ 'color-swatch--active': selectedColorIndex === i }" :title="c.name"
                            @click="selectColor(i)">
                            <span class="color-swatch__dot" :style="{ backgroundColor: c.hex }"></span>
                            <span class="color-swatch__label">{{ c.name }}</span>
                        </button>
                    </div>

                    <div v-if="colorPalette.length === 0" class="studio__empty">
                        Tidak ada data warna tersedia.
                    </div>
                </div>

                <!-- ─── TEKS ─── -->
                <div v-show="activeTab === 'teks'" class="studio__panel">
                    <div class="studio__panel-title">Teks & Tipografi</div>
                    <p class="studio__panel-desc">Pilih teks di canvas untuk mengedit, atau tambah teks baru.</p>

                    <!-- Tombol Tambah Teks Baru (selalu visible) -->
                    <div class="form-group">
                        <button class="upload-btn" @click="addNewText">
                            <PhTextT :size="20" />
                            <span>Tambah Teks Baru</span>
                        </button>
                    </div>

                    <div v-if="hasSelectedText" class="text-editor">
                        <div class="studio__panel-subtitle">Edit Teks Terpilih</div>
                        <!-- Isi Teks -->
                        <div class="form-group">
                            <label class="form-label">Isi Teks</label>
                            <input id="text-input" v-model="textInput" type="text" class="form-input"
                                placeholder="Ketik teks..." @input="handleUpdateText" />
                        </div>

                        <!-- Bold & Italic -->
                        <div class="form-group">
                            <label class="form-label">Gaya Teks</label>
                            <div class="style-toggles">
                                <button class="style-toggle-btn" :class="{ active: textBold }" title="Bold"
                                    @click="handleToggleBold">
                                    <PhTextBolder :size="18" />
                                </button>
                                <button class="style-toggle-btn" :class="{ active: textItalic }" title="Italic"
                                    @click="handleToggleItalic">
                                    <PhTextItalic :size="18" />
                                </button>
                                <button class="style-toggle-btn" :class="{ active: textFlipX }" title="Mirror (Flip Horizontal)"
                                    @click="handleToggleTextFlip">
                                    <PhArrowsLeftRight :size="18" />
                                </button>
                            </div>
                        </div>

                        <!-- Text Rotation -->
                        <div class="form-group">
                            <label class="form-label">Rotasi Teks</label>
                            <div class="slider-row">
                                <input id="text-rotation" v-model.number="textRotation" type="range" min="0" max="360"
                                    step="1" class="form-range" @input="handleTextRotation" />
                                <span class="slider-val">{{ textRotation }}°</span>
                            </div>
                        </div>

                        <!-- Font Family -->
                        <div class="form-group">
                            <label class="form-label">Font</label>
                            <select id="text-font" v-model="textFontFamily" class="form-select"
                                @change="handleUpdateText">
                                <option v-for="f in fontFamilies" :key="f" :value="f">{{ f }}</option>
                            </select>
                        </div>

                        <!-- Font Size -->
                        <div class="form-group">
                            <label class="form-label">Ukuran</label>
                            <select id="text-size" v-model="textFontSize" class="form-select"
                                @change="handleUpdateText">
                                <option v-for="s in fontSizes" :key="s" :value="s">{{ s }}px</option>
                            </select>
                        </div>

                        <!-- Warna Teks -->
                        <div class="form-group">
                            <label class="form-label">Warna Teks</label>
                            <input id="text-color" v-model="textColor" type="color" class="form-color"
                                @input="handleUpdateText" />
                        </div>

                        <div class="form-group mt-2">
                            <button class="delete-btn" @click="deleteSelected">
                                <PhTrash :size="16" />
                                Hapus Teks
                            </button>
                        </div>
                    </div>

                    <div v-else class="studio__empty">
                        <div>Klik teks di canvas untuk mengedit.</div>
                    </div>
                </div>

                <!-- ─── GAMBAR ─── -->
                <div v-show="activeTab === 'gambar'" class="studio__panel">
                    <div class="studio__panel-title">Tambah Gambar</div>

                    <!-- Upload -->
                    <div class="form-group">
                        <button class="upload-btn" @click="triggerUpload">
                            <PhUploadSimple :size="20" />
                            <span>Pilih File Gambar</span>
                        </button>
                        <input ref="fileInputRef" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden-input" @change="handleFileUpload" />
                    </div>

                    <!-- Resize -->
                    <div class="form-group">
                        <label class="form-label">Ukuran Gambar</label>
                        <div class="slider-row">
                            <input id="image-scale" v-model.number="imageScale" type="range" min="0.1" max="3"
                                step="0.05" class="form-range" @input="handleImageScale" />
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

                    <!-- Delete -->
                    <div class="form-group">
                        <button class="delete-btn" @click="deleteSelected">
                            <PhTrash :size="16" />
                            Hapus Terpilih
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Toolbar -->
            <div class="studio__sidebar-footer">
                <button class="studio__checkout-btn" @click="handleCheckout" :disabled="isCheckingOut">
                    <PhShoppingCart :size="16" weight="bold" />
                    {{ isCheckingOut ? 'Memproses...' : 'Checkout' }}
                </button>
                <button class="studio__reset-btn" @click="clearDesign">
                    <PhArrowCounterClockwise :size="16" />
                    Reset Desain
                </button>
            </div>
        </aside>

        <!-- ═══ TENGAH: Canvas Editor 2D ═══ -->
        <main class="studio__canvas">
            <div class="studio__canvas-header">
                <h3 class="studio__canvas-title">Canvas Editor</h3>
            </div>
            <div class="studio__canvas-body">
                <DesignEditor ref="editorRef" :pattern-path="patternPath" :selected-color="selectedColor"
                    @canvas-update="onCanvasUpdate" @texts-loaded="onTextsLoaded" @selection="handleSelection" />
            </div>
        </main>

        <!-- ═══ KANAN: 3D Preview ═══ -->
        <section class="studio__preview">
            <div class="studio__preview-header">
                <h3 class="studio__preview-title">3D Preview</h3>
            </div>
            <div class="studio__preview-body">
                <TshirtViewer ref="viewerRef" :design-canvas="designCanvas" :model-path="modelPath"
                    @renderer-ready="onRendererReady" />
            </div>
        </section>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   STUDIO LAYOUT — 3 Column
   ═══════════════════════════════════════════ */

.studio {
    display: flex;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: #0f0f1a;
    color: #e2e8f0;
}

/* ─── Sidebar Kiri ─── */
.studio__sidebar {
    width: 280px;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    background: #16162a;
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    overflow: hidden;
}

.studio__sidebar-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.studio__title {
    flex: 1;
    font-size: 13px;
    font-weight: 700;
    color: #f1f5f9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}

.studio__header-actions {
    display: flex;
    gap: 6px;
}

.studio__icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.04);
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s ease;
}

.studio__icon-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f1f5f9;
    border-color: rgba(255, 255, 255, 0.2);
}

.studio__icon-btn--accent {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.3);
    color: #818cf8;
}

.studio__icon-btn--accent:hover {
    background: rgba(99, 102, 241, 0.25);
    color: #a5b4fc;
}

.studio__icon-btn--sm {
    width: 28px;
    height: 28px;
    border-radius: 6px;
}

/* ─── Tabs ─── */
.studio__tabs {
    display: flex;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.studio__tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.15s ease;
    border-bottom: 2px solid transparent;
}

.studio__tab:hover {
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.02);
}

.studio__tab--active {
    color: #818cf8;
    border-bottom-color: #818cf8;
    background: rgba(99, 102, 241, 0.05);
}

/* ─── Tab Content ─── */
.studio__tab-content {
    flex: 1;
    overflow-y: auto;
}

.studio__panel {
    padding: 16px;
}

.studio__panel-title {
    font-size: 12px;
    font-weight: 700;
    color: #cbd5e1;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
}

.studio__panel-desc {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 16px;
    margin-top: 0;
}

.studio__empty {
    font-size: 12px;
    color: #475569;
    text-align: center;
    padding: 24px 16px;
}

/* ─── Color Grid ─── */
.color-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
}

.color-swatch {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.02);
    cursor: pointer;
    transition: all 0.15s ease;
}

.color-swatch:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.12);
}

.color-swatch--active {
    background: rgba(99, 102, 241, 0.1);
    border-color: #818cf8;
}

.color-swatch__dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.15);
}

.color-swatch__label {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── Text List ─── */
.text-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
}

.text-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.02);
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.text-item:hover {
    background: rgba(255, 255, 255, 0.06);
}

.text-item--active {
    background: rgba(99, 102, 241, 0.1);
    border-color: #818cf8;
}

.text-item__name {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.text-item__preview {
    font-size: 13px;
    font-weight: 500;
    color: #e2e8f0;
}

.text-editor {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ─── Form Controls ─── */
.form-group {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.form-input,
.form-select {
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.04);
    color: #e2e8f0;
    outline: none;
    transition: border-color 0.15s ease;
}

.form-input:focus,
.form-select:focus {
    border-color: #818cf8;
}

.form-select {
    cursor: pointer;
}

.form-select option {
    background: #1e1e3a;
    color: #e2e8f0;
}

.form-color {
    width: 48px;
    height: 34px;
    padding: 2px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.04);
}

/* ─── Upload Button ─── */
.upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px 16px;
    border: 2px dashed rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.02);
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.upload-btn:hover {
    border-color: #818cf8;
    background: rgba(99, 102, 241, 0.05);
    color: #a5b4fc;
}

/* ─── Delete Button ─── */
.delete-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 6px;
    background: rgba(239, 68, 68, 0.08);
    color: #f87171;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.delete-btn:hover {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.5);
}

/* ─── Slider ─── */
.slider-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-range {
    flex: 1;
    accent-color: #818cf8;
    height: 4px;
}

.slider-val {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    min-width: 42px;
    text-align: right;
}

/* ─── Variant Info ─── */
.studio__variant-info {
    display: flex;
    gap: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.studio__variant-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 10px 8px;
    border-right: 1px solid rgba(255, 255, 255, 0.06);
}

.studio__variant-item:last-child {
    border-right: none;
}

.studio__variant-label {
    font-size: 9px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.studio__variant-value {
    font-size: 12px;
    font-weight: 600;
    color: #e2e8f0;
}

.studio__variant-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    border-radius: 4px;
    padding: 2px 4px;
    font-size: 11px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
    width: 100%;
    text-align: center;
}

.studio__variant-select option {
    background: #1a1a2e;
    color: #e2e8f0;
}

/* ─── Sidebar Footer ─── */
.studio__sidebar-footer {
    padding: 12px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.studio__checkout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.studio__checkout-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.studio__checkout-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.studio__reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 10px 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.03);
    color: #94a3b8;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.studio__reset-btn:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #f1f5f9;
}

/* ─── Canvas Editor (Tengah) ─── */
.studio__canvas {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255, 255, 255, 0.06);
}

.studio__canvas-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 255, 255, 0.02);
}

.studio__canvas-title {
    font-size: 13px;
    font-weight: 600;
    color: #cbd5e1;
    margin: 0;
}

.studio__canvas-controls {
    display: flex;
    gap: 4px;
}

.studio__canvas-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* ─── 3D Preview (Kanan) ─── */
.studio__preview {
    width: 420px;
    min-width: 420px;
    display: flex;
    flex-direction: column;
}

.studio__preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 255, 255, 0.02);
}

.studio__preview-title {
    font-size: 13px;
    font-weight: 600;
    color: #cbd5e1;
    margin: 0;
}

.studio__preview-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* ─── Utility ─── */
.hidden-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0;
    height: 0;
}

/* ─── Scrollbar ─── */
.studio__tab-content::-webkit-scrollbar {
    width: 4px;
}

.studio__tab-content::-webkit-scrollbar-track {
    background: transparent;
}

.studio__tab-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.studio__tab-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* ─── Bold/Italic Toggle ─── */
.studio__panel-subtitle {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.style-toggles {
    display: flex;
    gap: 6px;
}

.style-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s ease;
}

.style-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #e2e8f0;
}

.style-toggle-btn.active {
    background: rgba(99, 102, 241, 0.2);
    border-color: #6366f1;
    color: #a5b4fc;
}
</style>