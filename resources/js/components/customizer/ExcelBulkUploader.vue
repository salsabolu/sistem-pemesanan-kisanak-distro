<script lang="ts">
/** Type exported for parent components to use */
export type BulkOrderRow = {
    no: number;
    ukuran: string;
    warna: string;
    teksKustom: string;
    logoFileName: string;
    jumlah: number;
    // resolved
    variantId: number | null;
    hargaSatuan: number;
    subtotal: number;
    logoFile: File | null;
    valid: boolean;
    errors: string[];
};
</script>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    PhDownloadSimple,
    PhUploadSimple,
    PhFileXls,
    PhTrash,
    PhCheckCircle,
    PhWarningCircle,
    PhImage,
    PhInfo,
} from '@phosphor-icons/vue';
import * as XLSX from 'xlsx';
import Button from '@/components/Button.vue';

// ─── Types ───

type ProdukVariant = {
    id: number;
    harga: number | string;
    warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string } | null;
};

const props = defineProps<{
    variants: ProdukVariant[];
    availableWarnas: string[];
    availableUkurans: string[];
}>();

const emit = defineEmits<{
    (e: 'update', rows: BulkOrderRow[], file: File | null): void;
    (e: 'logo-files', files: File[]): void;
}>();

// ─── State ───

const excelFile = ref<File | null>(null);
const parsedRows = ref<BulkOrderRow[]>([]);
const logoFiles = ref<File[]>([]);
const parseError = ref('');
const isDragOver = ref(false);

// ─── Template Download ───

function downloadTemplate() {
    const wb = XLSX.utils.book_new();

    // Main sheet with example data
    const headerRow = ['No', 'Ukuran Kaos', 'Warna Kaos', 'Nama (Teks Kustom)', 'Logo (Nama File)', 'Jumlah'];
    const exampleRows = [
        [1, props.availableUkurans[0] ?? 'L', props.availableWarnas[0] ?? 'Hitam', 'Budi', 'logo_budi.png', 1],
        [2, props.availableUkurans[1] ?? 'XL', props.availableWarnas[1] ?? 'Putih', 'Andi', '', 2],
        [3, props.availableUkurans[0] ?? 'L', props.availableWarnas[0] ?? 'Hitam', '', '', 1],
    ];
    const wsData = [headerRow, ...exampleRows];
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Set column widths
    ws['!cols'] = [
        { wch: 5 },   // No
        { wch: 15 },  // Ukuran
        { wch: 15 },  // Warna
        { wch: 25 },  // Nama
        { wch: 25 },  // Logo
        { wch: 10 },  // Jumlah
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Pesanan');

    // Reference sheet with available options
    const refHeader = ['Warna Tersedia', 'Ukuran Tersedia'];
    const maxLen = Math.max(props.availableWarnas.length, props.availableUkurans.length);
    const refRows: (string | undefined)[][] = [];
    for (let i = 0; i < maxLen; i++) {
        refRows.push([
            props.availableWarnas[i] ?? '',
            props.availableUkurans[i] ?? '',
        ]);
    }
    const wsRef = XLSX.utils.aoa_to_sheet([refHeader, ...refRows]);
    wsRef['!cols'] = [{ wch: 20 }, { wch: 20 }];
    XLSX.utils.book_append_sheet(wb, wsRef, 'Referensi');

    XLSX.writeFile(wb, 'template-pesanan-bulk.xlsx');
}

// ─── Excel Parsing ───

function handleExcelUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    excelFile.value = file;
    parseExcelFile(file);
    target.value = '';
}

function handleDrop(event: DragEvent) {
    isDragOver.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (!file) return;
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext ?? '')) {
        parseError.value = 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv';
        return;
    }
    excelFile.value = file;
    parseExcelFile(file);
}

function parseExcelFile(file: File) {
    parseError.value = '';
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target?.result as ArrayBuffer);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            if (!sheetName) {
                parseError.value = 'File Excel kosong.';
                return;
            }
            const sheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json<Record<string, any>>(sheet, { defval: '' });

            if (jsonData.length === 0) {
                parseError.value = 'Tidak ada data di dalam file Excel.';
                return;
            }

            const rows: BulkOrderRow[] = jsonData.map((row, idx) => {
                const ukuran = normalizeStr(findColumnValue(row, ['ukuran kaos', 'ukuran', 'size']));
                const warna = normalizeStr(findColumnValue(row, ['warna kaos', 'warna', 'color']));
                const teksKustom = String(findColumnValue(row, ['nama (teks kustom)', 'nama', 'teks kustom', 'teks', 'name', 'custom text']) ?? '').trim();
                const logoFileName = String(findColumnValue(row, ['logo (nama file)', 'logo', 'logo file']) ?? '').trim();
                const jumlahRaw = findColumnValue(row, ['jumlah', 'qty', 'quantity']);
                const jumlah = Math.max(1, parseInt(String(jumlahRaw), 10) || 1);

                const errors: string[] = [];

                // Validate color
                const matchedWarna = props.availableWarnas.find(
                    w => w.trim().toLowerCase() === warna.toLowerCase()
                );
                if (!matchedWarna && warna) {
                    errors.push(`Warna "${warna}" tidak tersedia`);
                }
                if (!warna) {
                    errors.push('Warna kaos wajib diisi');
                }

                // Validate size
                const matchedUkuran = props.availableUkurans.find(
                    u => u.trim().toLowerCase() === ukuran.toLowerCase()
                );
                if (!matchedUkuran && ukuran) {
                    errors.push(`Ukuran "${ukuran}" tidak tersedia`);
                }
                if (!ukuran) {
                    errors.push('Ukuran kaos wajib diisi');
                }

                // Find matching variant
                let variantId: number | null = null;
                let hargaSatuan = 0;
                if (matchedWarna && matchedUkuran) {
                    const variant = props.variants.find(
                        v => (v.warna?.nama ?? '').trim().toLowerCase() === warna.toLowerCase()
                            && (v.ukuran?.nama ?? '').trim().toLowerCase() === ukuran.toLowerCase()
                    );
                    if (variant) {
                        variantId = variant.id;
                        hargaSatuan = typeof variant.harga === 'number' ? variant.harga : Number(variant.harga);
                    } else {
                        errors.push(`Varian warna "${warna}" ukuran "${ukuran}" tidak ditemukan`);
                    }
                }

                return {
                    no: idx + 1,
                    ukuran: matchedUkuran ?? ukuran,
                    warna: matchedWarna ?? warna,
                    teksKustom,
                    logoFileName,
                    jumlah,
                    variantId,
                    hargaSatuan,
                    subtotal: hargaSatuan * jumlah,
                    logoFile: null,
                    valid: errors.length === 0,
                    errors,
                };
            });

            parsedRows.value = rows;
            matchLogoFiles();
            emit('update', parsedRows.value, excelFile.value);
        } catch (err) {
            parseError.value = 'Gagal membaca file Excel. Pastikan format file benar.';
            console.error('[ExcelBulkUploader] Parse error:', err);
        }
    };
    reader.readAsArrayBuffer(file);
}

/** Find column value using flexible header matching */
function findColumnValue(row: Record<string, any>, possibleHeaders: string[]): any {
    for (const key of Object.keys(row)) {
        const normalizedKey = key.trim().toLowerCase();
        for (const header of possibleHeaders) {
            if (normalizedKey === header.toLowerCase()) {
                return row[key];
            }
        }
    }
    return '';
}

function normalizeStr(val: any): string {
    return String(val ?? '').trim();
}

// ─── Logo File Upload ───

const logoInputRef = ref<HTMLInputElement | null>(null);

function triggerLogoUpload() {
    logoInputRef.value?.click();
}

function handleLogoUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (!files || files.length === 0) return;

    // Merge with existing files (avoid duplicates by name)
    const existingNames = new Set(logoFiles.value.map(f => f.name.toLowerCase()));
    for (const file of Array.from(files)) {
        if (!existingNames.has(file.name.toLowerCase())) {
            logoFiles.value.push(file);
            existingNames.add(file.name.toLowerCase());
        }
    }

    matchLogoFiles();
    emit('logo-files', logoFiles.value);
    target.value = '';
}

function removeLogoFile(index: number) {
    logoFiles.value.splice(index, 1);
    matchLogoFiles();
    emit('logo-files', logoFiles.value);
}

/** Match uploaded logo files to parsed rows by filename */
function matchLogoFiles() {
    if (parsedRows.value.length === 0) return;
    for (const row of parsedRows.value) {
        row.logoFile = null;
        if (row.logoFileName) {
            const match = logoFiles.value.find(
                f => f.name.toLowerCase() === row.logoFileName.toLowerCase()
            );
            row.logoFile = match ?? null;
        }
    }
}

// ─── Computed ───

const totalHarga = computed(() =>
    parsedRows.value.reduce((sum, r) => sum + (r.valid ? r.subtotal : 0), 0)
);

const totalItems = computed(() =>
    parsedRows.value.reduce((sum, r) => sum + (r.valid ? r.jumlah : 0), 0)
);

const validCount = computed(() => parsedRows.value.filter(r => r.valid).length);
const invalidCount = computed(() => parsedRows.value.filter(r => !r.valid).length);

const unmatchedLogos = computed(() =>
    parsedRows.value.filter(r => r.logoFileName && !r.logoFile).map(r => r.logoFileName)
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

function removeRow(index: number) {
    parsedRows.value.splice(index, 1);
    // Re-number
    parsedRows.value.forEach((r, i) => r.no = i + 1);
    emit('update', parsedRows.value, excelFile.value);
}

function clearAll() {
    excelFile.value = null;
    parsedRows.value = [];
    parseError.value = '';
    emit('update', [], null);
}

// Watch for variant changes and re-validate
watch(() => props.variants, () => {
    if (parsedRows.value.length > 0 && excelFile.value) {
        parseExcelFile(excelFile.value);
    }
}, { deep: true });
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div>
            <div class="text-xs font-medium uppercase tracking-wider mb-1 text-gray-900 dark:text-white">
                Pesanan Massal (Excel)
            </div>
            <p class="text-[11px] text-gray-500 mb-2">
                Unggah file Excel berisi data variasi kaos untuk pesanan dalam jumlah banyak.
            </p>
        </div>

        <!-- Step 1: Download Template -->
        <div class="border border-gray-200 dark:border-gray-700 p-3">
            <div class="text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-2">
                Langkah 1 — Unduh Template
            </div>
            <Button variant="primary" class="w-full flex items-center justify-center gap-2" @click="downloadTemplate">
                <PhDownloadSimple :size="16" />
                Unduh Template Excel
            </Button>
            <p class="text-[10px] text-gray-400 mt-1.5">
                Template berisi kolom: Ukuran, Warna, Nama, Logo, Jumlah
            </p>
        </div>

        <!-- Step 2: Upload Excel -->
        <div class="border border-gray-200 dark:border-gray-700 p-3">
            <div class="text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-2">
                Langkah 2 — Unggah File Excel
            </div>

            <!-- Drop zone -->
            <div
                class="border-2 border-dashed rounded p-4 text-center transition-colors cursor-pointer"
                :class="isDragOver
                    ? 'border-black bg-gray-100 dark:border-indigo-400 dark:bg-indigo-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
                @dragover.prevent="isDragOver = true"
                @dragleave="isDragOver = false"
                @drop.prevent="handleDrop"
                @click="($refs.excelInput as HTMLInputElement)?.click()"
            >
                <PhFileXls :size="28" class="mx-auto mb-1.5 text-gray-400" />
                <div v-if="excelFile" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ excelFile.name }}
                </div>
                <div v-else class="text-[11px] text-gray-500">
                    Seret file atau klik untuk mengunggah
                </div>
                <div class="text-[10px] text-gray-400 mt-1">.xlsx, .xls, .csv</div>
            </div>
            <input
                ref="excelInput"
                type="file"
                accept=".xlsx,.xls,.csv"
                class="hidden"
                @change="handleExcelUpload"
            />

            <!-- Parse Error -->
            <div v-if="parseError" class="mt-2 text-[11px] text-red-600 flex items-center gap-1">
                <PhWarningCircle :size="14" />
                {{ parseError }}
            </div>
        </div>

        <!-- Step 3: Upload Logo Files (optional) -->
        <div v-if="parsedRows.length > 0" class="border border-gray-200 dark:border-gray-700 p-3">
            <div class="text-[10px] font-medium uppercase tracking-wider text-gray-500 mb-2">
                Langkah 3 — Unggah File Logo (Opsional)
            </div>
            <Button variant="primary" class="w-full flex items-center justify-center gap-2 border-dashed" @click="triggerLogoUpload">
                <PhImage :size="16" />
                Pilih File Logo
            </Button>
            <input
                ref="logoInputRef"
                type="file"
                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                multiple
                class="hidden"
                @change="handleLogoUpload"
            />

            <!-- Uploaded logos list -->
            <div v-if="logoFiles.length > 0" class="mt-2 flex flex-col gap-1">
                <div v-for="(f, idx) in logoFiles" :key="f.name" class="flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-400">
                    <PhCheckCircle :size="12" class="text-green-500 shrink-0" />
                    <span class="truncate flex-1">{{ f.name }}</span>
                    <button type="button" @click="removeLogoFile(idx)" class="text-red-500 hover:text-red-700 shrink-0">
                        <PhTrash :size="12" />
                    </button>
                </div>
            </div>

            <!-- Unmatched logo warnings -->
            <div v-if="unmatchedLogos.length > 0" class="mt-2">
                <div v-for="name in unmatchedLogos" :key="name" class="text-[10px] text-yellow-600 flex items-center gap-1">
                    <PhWarningCircle :size="12" />
                    Logo "{{ name }}" belum diunggah
                </div>
            </div>
        </div>

        <!-- Preview Table -->
        <div v-if="parsedRows.length > 0" class="border border-gray-200 dark:border-gray-700">
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                    Preview Data ({{ parsedRows.length }} baris)
                </div>
                <button type="button" @click="clearAll" class="text-[10px] text-red-500 hover:text-red-700 flex items-center gap-0.5">
                    <PhTrash :size="10" />
                    Hapus Semua
                </button>
            </div>

            <!-- Validation Summary -->
            <div class="px-3 py-2 bg-white dark:bg-[#16162a] border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <div class="text-[10px] flex items-center gap-1 text-green-600">
                    <PhCheckCircle :size="12" />
                    {{ validCount }} valid
                </div>
                <div v-if="invalidCount > 0" class="text-[10px] flex items-center gap-1 text-red-600">
                    <PhWarningCircle :size="12" />
                    {{ invalidCount }} invalid
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto max-h-[280px] overflow-y-auto">
                <table class="w-full text-[10px]">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 uppercase">#</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 uppercase">Ukuran</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 uppercase">Warna</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500 uppercase">Jml</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500 uppercase">Harga</th>
                            <th class="px-2 py-1.5 text-center font-medium text-gray-500 uppercase w-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr
                            v-for="(row, idx) in parsedRows"
                            :key="idx"
                            :class="row.valid
                                ? 'bg-white dark:bg-[#16162a]'
                                : 'bg-red-50 dark:bg-red-900/10'"
                        >
                            <td class="px-2 py-1.5 text-gray-500">{{ row.no }}</td>
                            <td class="px-2 py-1.5">
                                <span :class="row.valid ? 'text-gray-900 dark:text-gray-200' : 'text-red-600'">
                                    {{ row.ukuran || '-' }}
                                </span>
                            </td>
                            <td class="px-2 py-1.5">
                                <span :class="row.valid ? 'text-gray-900 dark:text-gray-200' : 'text-red-600'">
                                    {{ row.warna || '-' }}
                                </span>
                            </td>
                            <td class="px-2 py-1.5 text-gray-700 dark:text-gray-300 truncate max-w-[80px]" :title="row.teksKustom">
                                {{ row.teksKustom || '-' }}
                            </td>
                            <td class="px-2 py-1.5 text-right text-gray-900 dark:text-gray-200">
                                {{ row.jumlah }}
                            </td>
                            <td class="px-2 py-1.5 text-right font-medium" :class="row.valid ? 'text-gray-900 dark:text-gray-200' : 'text-red-600'">
                                {{ row.valid ? formatRupiah(row.subtotal) : '-' }}
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" @click="removeRow(idx)" class="text-red-400 hover:text-red-600">
                                    <PhTrash :size="11" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Error details for invalid rows -->
            <div v-if="invalidCount > 0" class="px-3 py-2 bg-red-50 dark:bg-red-900/10 border-t border-gray-200 dark:border-gray-700">
                <div class="text-[10px] font-medium text-red-600 mb-1">Detail Error:</div>
                <div v-for="row in parsedRows.filter(r => !r.valid)" :key="row.no" class="text-[10px] text-red-500">
                    Baris {{ row.no }}: {{ row.errors.join(', ') }}
                </div>
            </div>

            <!-- Total -->
            <div class="px-3 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Total Item</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ totalItems }} pcs</span>
                </div>
                <div class="flex justify-between text-xs mt-1">
                    <span class="text-gray-500">Total Harga</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ formatRupiah(totalHarga) }}</span>
                </div>
                <div v-if="invalidCount > 0" class="mt-2 text-[10px] text-yellow-600 flex items-center gap-1">
                    <PhInfo :size="12" />
                    {{ invalidCount }} baris invalid tidak akan diproses
                </div>
            </div>
        </div>
    </div>
</template>
