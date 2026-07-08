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
    PhSun,
    PhMoon,
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
    PhTable,
} from '@phosphor-icons/vue';
import TshirtViewer from '@/components/customizer/TshirtViewer.vue';
import DesignEditor from '@/components/customizer/DesignEditor.vue';
import ExcelBulkUploader from '@/components/customizer/ExcelBulkUploader.vue';
import type { BulkOrderRow } from '@/components/customizer/ExcelBulkUploader.vue';
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
type TabId = 'warna' | 'teks' | 'gambar' | 'excel';
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
    { id: 'excel', label: 'Excel', icon: PhTable },
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
// EXCEL BULK ORDER
// ═══════════════════════════════════════════

const bulkOrderRows = ref<BulkOrderRow[]>([]);
const bulkLogoFiles = ref<File[]>([]);
const bulkExcelFile = ref<File | null>(null);

function handleBulkUpdate(rows: BulkOrderRow[], file: File | null = null) {
    bulkOrderRows.value = rows;
    bulkExcelFile.value = file;
}

function handleBulkLogoFiles(files: File[]) {
    bulkLogoFiles.value = files;
}

/** All available sizes (not filtered by current color) for bulk order */
const allAvailableUkurans = computed(() => {
    if (!props.variants) return [];
    const ukurans = new Set(props.variants.map(v => v.ukuran?.nama).filter(Boolean));
    return Array.from(ukurans) as string[];
});

const hasBulkOrder = computed(() =>
    bulkOrderRows.value.filter(r => r.valid).length > 0
);

const bulkTotalHarga = computed(() =>
    bulkOrderRows.value.reduce((sum, r) => sum + (r.valid ? r.subtotal : 0), 0)
);

const bulkTotalItems = computed(() =>
    bulkOrderRows.value.reduce((sum, r) => sum + (r.valid ? r.jumlah : 0), 0)
);

// ═══════════════════════════════════════════
// CHECKOUT — Simpan desain lalu ke keranjang
// ═══════════════════════════════════════════

const isCheckingOut = ref(false);
const savedDesainId = ref<number | null>(null);
const savedLogoMap = ref<Record<string, string>>({});

// Watch for desainId dan logoMap flash dari simpanDesain
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.desainId) savedDesainId.value = flash.desainId;
        if (flash?.logoMap) savedLogoMap.value = flash.logoMap;
    },
    { immediate: true, deep: true }
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

            // Cari semua ID gambar yang masih ada di canvas
            const activeImageIds = (designJson as any).fabricObjects?.objects
                ?.filter((o: any) => o.type === 'image' && o.kisanakId)
                ?.map((o: any) => o.kisanakId) || [];

            // Saring file yang diupload, HANYA ambil file yang belum dihapus dari canvas
            const finalCanvasFiles = uploadedFiles.value.filter(f => activeImageIds.includes((f as any).kisanakId));

            const payload: any = {
                desain_json: JSON.stringify(designJson),
                teks: hasBulkOrder.value ? [] : texts.map((t) => ({ teks: t.text })),
                gambar_files: finalCanvasFiles.length > 0 ? Array.from(finalCanvasFiles) : [],
            };

            if (hasBulkOrder.value) {
                if (bulkExcelFile.value) {
                    payload.excel_file = bulkExcelFile.value;
                }
                if (bulkLogoFiles.value.length > 0) {
                    payload.logo_files = Array.from(bulkLogoFiles.value);
                }
            }

            router.post(`/katalog/produk/${props.produk?.id}/kustomisasi/simpan`, payload, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const flash = page.props.flash;
                    if (flash?.desainId) {
                        savedDesainId.value = flash.desainId;
                    }
                    if (flash?.logoMap) {
                        savedLogoMap.value = flash.logoMap;
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
    const raw = localStorage.getItem('kisanak_cart');
    const cart = raw ? JSON.parse(raw) : [];

    if (hasBulkOrder.value) {
        // ── Mode Bulk: Group by Variant ID ──
        const validRows = bulkOrderRows.value.filter(r => r.valid);
        const now = Date.now();
        
        // Map untuk mengelompokkan
        const grouped = new Map<number, any>();
        
        for (const row of validRows) {
            const vid = row.variantId;
            if (!grouped.has(vid)) {
                grouped.set(vid, {
                    id: `${vid}_${row.warna}_${row.ukuran}_bulk_${now}`,
                    productId: vid,
                    imageSrc: props.produk?.gambar ?? '/images/kaos-1.png',
                    productName: productName.value,
                    color: row.warna,
                    size: row.ukuran,
                    unitPrice: row.hargaSatuan,
                    unitPriceText: formatRupiah(row.hargaSatuan),
                    quantity: 0,
                    desainId: savedDesainId.value,
                    isBulkItem: true,
                    bulkData: [],
                });
            }
            const group = grouped.get(vid);
            group.quantity += row.jumlah;
            if (row.teksKustom || savedLogoMap.value[row.logoFileName]) {
                group.bulkData.push({
                    teksKustom: row.teksKustom || null,
                    logoPath: savedLogoMap.value[row.logoFileName] || null
                });
            }
        }
        
        // Push grouped items to cart
        for (const group of grouped.values()) {
            cart.push(group);
        }
    } else {
        // ── Mode Satuan: alur checkout biasa ──
        const variantId = matchedVariant.value?.id ?? props.produk?.id ?? 0;
        const price = unitPrice.value;
        cart.push({
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
        });
    }

    localStorage.setItem('kisanak_cart', JSON.stringify(cart));
    router.visit('/keranjang');
}

const isDarkMode = ref(false);
watch(isDarkMode, (val) => {
    if (val) document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
});
</script>

<template>

    <Head :title="`Kustomisasi - ${productName}`" />

    <Alert v-model:show="showAlert" :message="alertMessage" :type="alertType" />

    <div
        class="flex w-screen h-screen overflow-hidden font-sans bg-white text-black dark:bg-[#16162a] dark:text-gray-200 transition-colors duration-300">
        <!-- ═══ SIDEBAR KIRI: Panel Kontrol ═══ -->
        <aside
            class="w-[280px] min-w-[280px] flex flex-col bg-white border-r border-gray-200 dark:bg-[#16162a] dark:border-gray-800 transition-colors duration-300">
            <!-- Header -->
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                <Button variant="icon" @click="goBack" title="Kembali">
                    <PhArrowLeft :size="18" />
                </Button>
                <h2 class="flex-1 text-sm font-medium truncate text-gray-900 dark:text-white m-0">{{ productName }}</h2>
                <div class="flex gap-1">
                    <Button variant="icon" @click="isDarkMode = !isDarkMode" title="Ganti Tema">
                        <PhSun v-if="isDarkMode" :size="18" />
                        <PhMoon v-else :size="18" />
                    </Button>
                    <Button variant="icon" @click="downloadScreenshot" title="Screenshot">
                        <PhCamera :size="18" />
                    </Button>
                    <Button variant="icon" @click="saveDesign" :disabled="isSaving" :class="{'disabled:opacity-50': true}" title="Simpan Desain">
                        <PhFloppyDisk :size="18" />
                    </Button>
                </div>
            </div>

            <!-- Variant Info -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 text-xs">
                <div class="flex-1 flex flex-col items-center gap-1 p-2 border-r border-gray-200 dark:border-gray-800">
                    <span class="font-medium uppercase tracking-wider text-[9px] text-gray-500">Warna</span>
                    <Button variant="select" v-model="currentWarna">
                        <option v-for="w in availableWarnas" :key="w" :value="w"
                            class="text-black bg-white dark:bg-gray-900 dark:text-white">{{ w }}</option>
                    </Button>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1 p-2 border-r border-gray-200 dark:border-gray-800">
                    <span class="font-medium uppercase tracking-wider text-[9px] text-gray-500">Ukuran</span>
                    <Button variant="select" v-model="currentUkuran">
                        <option v-for="u in availableUkurans" :key="u" :value="u"
                            class="text-black bg-white dark:bg-gray-900 dark:text-white">{{ u }}</option>
                    </Button>
                </div>
                <div class="flex-1 flex flex-col items-center gap-1 p-2">
                    <span class="font-medium uppercase tracking-wider text-[9px] text-gray-500">Harga</span>
                    <span>{{ formatRupiah(unitPrice) }}</span>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-800">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                    class="flex-1 flex flex-col items-center gap-1 py-3 px-2 text-[10px] font-medium uppercase tracking-wider transition-colors border-b-2"
                    :class="activeTab === tab.id ? 'border-black text-black dark:border-indigo-400 dark:text-indigo-400 bg-gray-100 dark:bg-gray-800' : 'border-transparent text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'">
                    <component :is="tab.icon" :size="18" />
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- ─── WARNA ─── -->
                <div v-show="activeTab === 'warna'" class="p-4">
                    <div class="text-xs font-medium uppercase tracking-wider mb-1 text-gray-900 dark:text-white">Pilih
                        Warna Kaos</div>
                    <p class="text-[11px] text-gray-500 mb-4">Warna akan diterapkan ke seluruh zona pattern</p>

                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="(c, i) in colorPalette" :key="c.id" @click="selectColor(i)"
                            class="flex items-center gap-2 p-2 border transition-colors"
                            :class="selectedColorIndex === i ? 'border-black bg-gray-100 dark:border-indigo-400 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'">
                            <span class="w-5 h-5 rounded-full shrink-0 border border-black/10 dark:border-white/10"
                                :style="{ backgroundColor: c.hex }"></span>
                            <span class="text-xs font-medium truncate">{{ c.name }}</span>
                        </button>
                    </div>

                    <div v-if="colorPalette.length === 0" class="text-xs text-center text-gray-500 py-6">
                        Tidak ada data warna tersedia.
                    </div>
                </div>

                <!-- ─── TEKS ─── -->
                <div v-show="activeTab === 'teks'" class="p-4">
                    <div class="text-xs font-medium uppercase tracking-wider mb-1 text-gray-900 dark:text-white">Teks &
                        Tipografi</div>
                    <p class="text-[11px] text-gray-500 mb-4">Pilih teks di canvas untuk mengedit, atau tambah teks
                        baru.</p>

                    <!-- Tombol Tambah Teks Baru -->
                    <div class="mb-4">
                        <Button variant="primary" class="w-full flex items-center justify-center gap-2 border-dashed"
                            @click="addNewText">
                            <PhTextT :size="18" />
                            Tambah Teks Baru
                        </Button>
                    </div>

                    <div v-if="hasSelectedText" class="flex flex-col gap-3">
                        <div
                            class="text-[10px] font-medium uppercase tracking-wider text-gray-500 pb-1 border-b border-gray-200 dark:border-gray-800">
                            Edit Teks Terpilih</div>

                        <!-- Isi Teks -->
                        <div>
                            <label class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Isi
                                Teks</label>
                            <input v-model="textInput" type="text"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-900 text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-indigo-400 transition-colors"
                                placeholder="Ketik teks..." @input="handleUpdateText" />
                        </div>

                        <!-- Gaya Teks -->
                        <div>
                            <label
                                class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Gaya
                                Teks</label>
                            <div class="flex gap-1.5">
                                <button @click="handleToggleBold"
                                    class="w-9 h-9 flex items-center justify-center rounded border transition-colors"
                                    :class="textBold ? 'border-black bg-gray-100 dark:border-indigo-400 dark:bg-indigo-900/30 text-black dark:text-indigo-400' : 'border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'">
                                    <PhTextBolder :size="18" />
                                </button>
                                <button @click="handleToggleItalic"
                                    class="w-9 h-9 flex items-center justify-center rounded border transition-colors"
                                    :class="textItalic ? 'border-black bg-gray-100 dark:border-indigo-400 dark:bg-indigo-900/30 text-black dark:text-indigo-400' : 'border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'">
                                    <PhTextItalic :size="18" />
                                </button>
                                <button @click="handleToggleTextFlip"
                                    class="w-9 h-9 flex items-center justify-center rounded border transition-colors"
                                    :class="textFlipX ? 'border-black bg-gray-100 dark:border-indigo-400 dark:bg-indigo-900/30 text-black dark:text-indigo-400' : 'border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'">
                                    <PhArrowsLeftRight :size="18" />
                                </button>
                            </div>
                        </div>

                        <!-- Rotasi Teks -->
                        <div>
                            <label
                                class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Rotasi
                                Teks</label>
                            <div class="flex items-center gap-2">
                                <input v-model.number="textRotation" type="range" min="0" max="360" step="1"
                                    class="flex-1 accent-black dark:accent-indigo-400 h-1 cursor-pointer"
                                    @input="handleTextRotation" />
                                <span class="text-[11px] font-medium w-10 text-right">{{ textRotation }}°</span>
                            </div>
                        </div>

                        <!-- Font Family -->
                        <div>
                            <label
                                class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Font</label>
                            <select v-model="textFontFamily" @change="handleUpdateText"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-900 text-black dark:text-white outline-none cursor-pointer">
                                <option v-for="f in fontFamilies" :key="f" :value="f">{{ f }}</option>
                            </select>
                        </div>

                        <!-- Font Size -->
                        <div>
                            <label
                                class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Ukuran</label>
                            <select v-model="textFontSize" @change="handleUpdateText"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-900 text-black dark:text-white outline-none cursor-pointer">
                                <option v-for="s in fontSizes" :key="s" :value="s">{{ s }}px</option>
                            </select>
                        </div>

                        <!-- Warna Teks -->
                        <div>
                            <label
                                class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Warna
                                Teks</label>
                            <input v-model="textColor" type="color" @input="handleUpdateText"
                                class="w-12 h-8 p-0.5 border border-gray-300 dark:border-gray-700 rounded cursor-pointer bg-white dark:bg-gray-900" />
                        </div>

                        <div class="mt-2">
                            <Button variant="light"
                                class="w-full !border-red-600 !text-red-600 hover:!bg-red-50 flex items-center justify-center gap-2 py-2"
                                @click="deleteSelected">
                                <PhTrash :size="16" />
                                Hapus Teks
                            </Button>
                        </div>
                    </div>

                    <div v-else class="text-xs text-center text-gray-500 py-6">
                        Klik teks di canvas untuk mengedit.
                    </div>
                </div>

                <!-- ─── GAMBAR ─── -->
                <div v-show="activeTab === 'gambar'" class="p-4">
                    <div class="text-xs font-medium uppercase tracking-wider mb-4 text-gray-900 dark:text-white">Tambah
                        Gambar</div>

                    <!-- Upload -->
                    <div class="mb-4">
                        <Button variant="primary" class="w-full flex items-center justify-center gap-2 border-dashed"
                            @click="triggerUpload">
                            <PhUploadSimple :size="18" />
                            Pilih File Gambar
                        </Button>
                        <input ref="fileInputRef" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden" @change="handleFileUpload" />
                    </div>

                    <!-- Resize -->
                    <div class="mb-3">
                        <label class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Ukuran
                            Gambar</label>
                        <div class="flex items-center gap-2">
                            <input v-model.number="imageScale" type="range" min="0.1" max="3" step="0.05"
                                class="flex-1 accent-black dark:accent-indigo-400 h-1 cursor-pointer"
                                @input="handleImageScale" />
                            <span class="text-[11px] font-medium w-10 text-right">{{ (imageScale * 100).toFixed(0)
                                }}%</span>
                        </div>
                    </div>

                    <!-- Rotate -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-1">Rotasi
                            Gambar</label>
                        <div class="flex items-center gap-2">
                            <input v-model.number="imageRotation" type="range" min="0" max="360" step="1"
                                class="flex-1 accent-black dark:accent-indigo-400 h-1 cursor-pointer"
                                @input="handleImageRotation" />
                            <span class="text-[11px] font-medium w-10 text-right">{{ imageRotation }}°</span>
                        </div>
                    </div>

                    <!-- Delete -->
                    <div>
                        <Button variant="light"
                            class="w-full !border-red-600 !text-red-600 hover:!bg-red-50 flex items-center justify-center gap-2"
                            @click="deleteSelected">
                            <PhTrash :size="16" />
                            Hapus Terpilih
                        </Button>
                    </div>
                </div>

                <!-- ─── EXCEL BULK ORDER ─── -->
                <div v-show="activeTab === 'excel'" class="p-4">
                    <ExcelBulkUploader
                        :variants="props.variants ?? []"
                        :available-warnas="availableWarnas as string[]"
                        :available-ukurans="allAvailableUkurans"
                        @update="handleBulkUpdate"
                        @logo-files="handleBulkLogoFiles"
                    />
                </div>
            </div>

            <!-- Bottom Toolbar -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex flex-col gap-2">
                <!-- Bulk Order Summary -->
                <div v-if="hasBulkOrder" class="bg-gray-50 dark:bg-gray-800 p-2.5 border border-gray-200 dark:border-gray-700 mb-1">
                    <div class="flex justify-between text-[10px] text-gray-500">
                        <span>Mode Pesanan</span>
                        <span class="font-medium text-black dark:text-white">Bulk (Excel)</span>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-500 mt-1">
                        <span>Total Item</span>
                        <span class="font-medium text-black dark:text-white">{{ bulkTotalItems }} pcs</span>
                    </div>
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-gray-500">Total Harga</span>
                        <span class="font-bold text-black dark:text-white">{{ formatRupiah(bulkTotalHarga) }}</span>
                    </div>
                </div>

                <Button variant="solid" class="w-full flex justify-center items-center gap-2 tracking-wider text-xs"
                    @click="handleCheckout" :disabled="isCheckingOut">
                    <PhShoppingCart :size="16" weight="bold" />
                    {{ isCheckingOut ? 'Memproses...' : (hasBulkOrder ? `Checkout (${bulkTotalItems} pcs)` : 'Checkout') }}
                </Button>
                <Button variant="light" class="w-full flex justify-center items-center gap-2 text-xs"
                    @click="clearDesign">
                    <PhArrowCounterClockwise :size="16" />
                    Reset Desain
                </Button>
            </div>
        </aside>

        <!-- ═══ TENGAH: Canvas Editor 2D ═══ -->
        <main class="flex-1 flex flex-col min-w-0 border-r border-gray-200 dark:border-gray-800 relative z-10">
            <div
                class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white dark:bg-[#16162a] dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white m-0">Canvas Editor</h3>
            </div>
            <div class="flex-1 min-h-0 overflow-hidden relative">
                <DesignEditor ref="editorRef" :pattern-path="patternPath" :selected-color="selectedColor"
                    @canvas-update="onCanvasUpdate" @texts-loaded="onTextsLoaded" @selection="handleSelection" />
            </div>
        </main>

        <!-- ═══ KANAN: 3D Preview ═══ -->
        <section class="w-[420px] min-w-[420px] flex flex-col relative z-10">
            <div
                class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white dark:bg-[#16162a] dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white m-0">3D Preview</h3>
            </div>
            <div class="flex-1 min-h-0 overflow-hidden relative">
                <TshirtViewer ref="viewerRef" :design-canvas="designCanvas" :model-path="modelPath"
                    @renderer-ready="onRendererReady" />
            </div>
        </section>
    </div>
</template>
