<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Sidebar from '../../components/Sidebar.vue';
import DesignPreviewModal from '@/components/DesignPreviewModal.vue';
import Button from '@/components/Button.vue';

type PembayaranDB = {
    id: number;
    id_pembeli: number;
    id_pesanan: number;
    bukti_pembayaran: string | null;
    rekening: string | null;
    status: string;
    pembeli?: { id: number; nama: string; whatsapp: string };
    pesanan?: {
        id: number;
        total: number;
        produk?: Array<{
            id: number;
            nama: string;
            warna?: { nama: string };
            ukuran?: { nama: string };
            pivot?: { jumlah: number; subtotal: number };
            detail_pesanan?: any[];
        }>;
    };
};

type PaginatedResponse = {
    data: PembayaranDB[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    pembayaran?: PaginatedResponse;
}>();

const statusOptions = ['Belum Konfirmasi', 'Terkonfirmasi'] as const;

const page = usePage<any>();
const roles = computed<string[]>(() => page.props?.auth?.roles ?? []);
const isKasir = computed(() => roles.value.includes('kasir'));
const isPemilik = computed(() => roles.value.includes('pemilik'));

const currentPage = computed(() => props.pembayaran?.current_page ?? 1);
const totalPages = computed(() => props.pembayaran?.last_page ?? 1);

const isDetailOpen = ref(false);
const selectedItem = ref<any>(null);
const isLightboxOpen = ref(false);
const lightboxUrl = ref('');

// ── Design Preview ──
const isDesignPreviewOpen = ref(false);
const previewDesainJson = ref<string | null>(null);
const previewProductName = ref('');
const previewTeksList = ref<Array<{ id: number; teks: string }>>([]);
const previewGambarList = ref<Array<{ id: number; file: string }>>([]);
const previewFileExcel = ref<string | null>(null);

function openDesignPreview(produk: any, detailPesananList: any[]) {
    const detail = detailPesananList.find((dp: any) => dp.id_produk === produk.id);
    if (detail?.desain?.desain_json) {
        previewDesainJson.value = detail.desain.desain_json;
        previewProductName.value = produk.nama ?? '';
        previewTeksList.value = detail.desain.teks ?? [];
        previewGambarList.value = detail.desain.gambar ?? [];
        previewFileExcel.value = detail.desain.file_excel ?? null;
        isDesignPreviewOpen.value = true;
    }
}

function hasDesain(produk: any, detailPesananList: any[]): boolean {
    const detail = detailPesananList.find((dp: any) => dp.id_produk === produk.id);
    return !!(detail?.desain?.desain_json);
}

function openDetail(item: any) {
    selectedItem.value = item;
    isDetailOpen.value = true;
}

function closeDetail() {
    isDetailOpen.value = false;
    selectedItem.value = null;
}

function openLightbox(path: string) {
    lightboxUrl.value = buktiUrl(path);
    isLightboxOpen.value = true;
}

function closeLightbox() {
    isLightboxOpen.value = false;
    lightboxUrl.value = '';
}

const items = computed(() => {
    if (!props.pembayaran?.data) return [];
    return props.pembayaran.data.map((p, idx) => {
        const produkList = p.pesanan?.produk ?? [];
        const produkCount = produkList.length;

        return {
            id: p.id,
            no: (currentPage.value - 1) * (props.pembayaran?.per_page ?? 10) + idx + 1,
            avatarInitial: (p.pembeli?.nama ?? '?')[0].toUpperCase(),
            nama: p.pembeli?.nama ?? '-',
            whatsapp: p.pembeli?.whatsapp ?? '-',
            produkText: produkCount > 0 ? `${produkCount} Produk` : '-',
            totalHarga: formatRupiah(p.pesanan?.total ?? 0),
            rekening: p.rekening ?? '-',
            buktiPembayaran: p.bukti_pembayaran,
            statusPembayaran: p.status as 'Belum Konfirmasi' | 'Terkonfirmasi',
            produk: produkList,
            detailPesanan: p.pesanan?.detail_pesanan ?? [],
            pesananId: p.id_pesanan,
        };
    });
});

function formatRupiah(value: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
}

function updateStatus(item: any, event: Event) {
    const status = (event.target as HTMLSelectElement).value as (typeof statusOptions)[number];
    router.patch(`/produksi/pembayaran/${item.id}/status`, { status }, { preserveScroll: true });
}

function statusColor(status: string): string {
    switch (status) {
        case 'Terkonfirmasi': return 'bg-green text-white';
        case 'Belum Konfirmasi': return 'bg-red text-white';
        default: return 'bg-yellow text-black';
    }
}

function buktiUrl(path: string): string {
    if (!path) return '#';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('/')) return path;
    return `/storage/${path}`;
}

function uploadBukti(item: any, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    router.post(
        `/produksi/pembayaran/${item.id}/bukti`,
        { bukti_pembayaran: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                input.value = '';
            },
        },
    );
}

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        router.get(`/produksi/konfirmasi-pembayaran?page=${page}`, {}, { preserveScroll: true });
    }
}

function paginationPages(): (number | string)[] {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages: (number | string)[] = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1, 2);
        if (current > 3) pages.push('...');
        if (current > 2 && current < total - 1) pages.push(current);
        if (current < total - 2) pages.push('...');
        pages.push(total - 1, total);
    }

    return pages;
}
</script>

<template>

    <Head title="Konfirmasi Pembayaran" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h1 class="text-black text-xl font-medium mb-6">Konfirmasi Pembayaran</h1>

            <div class="border border-black/10 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-white-hover/50">
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">No</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">
                                <span class="inline-flex items-center gap-1">
                                    Pembeli
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 15l5 5 5-5" />
                                        <path d="M7 9l5-5 5 5" />
                                    </svg>
                                </span>
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Produk</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Total Harga</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Rekening</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Bukti Pembayaran
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Status Pembayaran
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-black/50">Belum ada pembayaran yang perlu
                                dikonfirmasi.</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id"
                            class="border-t border-black/5 cursor-pointer hover:bg-black/5" @click="openDetail(item)">
                            <td class="px-4 py-3 text-black text-sm">{{ item.no }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-medium text-black">{{ item.avatarInitial }}</span>
                                    </div>
                                    <div>
                                        <div class="text-black text-sm font-medium">{{ item.nama }}</div>
                                        <div class="text-black/50 text-xs">{{ item.whatsapp }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-black text-sm font-medium uppercase">{{ item.produkText }}</div>
                            </td>
                            <td class="px-4 py-3 text-black text-sm">{{ item.totalHarga }}</td>
                            <td class="px-4 py-3 text-black text-sm">{{ item.rekening }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button v-if="item.buktiPembayaran" type="button"
                                        class="px-3 py-1 rounded-full text-xs text-black bg-yellow"
                                        @click.stop="openLightbox(item.buktiPembayaran)">
                                        Lihat
                                    </button>

                                    <label v-if="isKasir"
                                        class="px-3 py-1 rounded-full text-xs text-black bg-yellow cursor-pointer"
                                        @click.stop>
                                        Upload
                                        <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                                            @change="uploadBukti(item, $event)" />
                                    </label>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <select class="px-3 py-1 rounded-full text-xs"
                                    :class="statusColor(item.statusPembayaran)" :value="item.statusPembayaran"
                                    :disabled="!isPemilik" @click.stop @change="updateStatus(item, $event)">
                                    <option v-for="opt in statusOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center gap-1">
                <button v-for="(page, idx) in paginationPages()" :key="idx" type="button"
                    class="min-w-8 h-8 px-2 text-sm border"
                    :class="page === currentPage ? 'border-black bg-black text-white' : 'border-black/20 bg-white text-black hover:bg-black/5'"
                    :disabled="typeof page === 'string'" @click="typeof page === 'number' && goToPage(page)">
                    {{ page }}
                </button>
            </div>

            <!-- Detail Modal -->
            <div v-if="isDetailOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="closeDetail" />
                <div class="relative bg-white text-black w-full max-w-xl mx-4 flex flex-col"
                    style="box-shadow: 0 8px 32px rgba(0,0,0,0.18); max-height: 85vh">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-black/10">
                        <div>
                            <div class="text-base uppercase tracking-wide">Detail Pesanan</div>
                            <div class="mt-0.5 text-xs text-black/40">ID Pesanan: #{{ selectedItem?.pesananId ?? '-' }}
                            </div>
                        </div>
                        <button type="button"
                            class="px-4 py-1.5 text-xs font-medium border border-black hover:bg-black hover:text-white transition-colors"
                            @click="closeDetail">Tutup</button>
                    </div>

                    <!-- Info Pembeli -->
                    <div class="px-6 py-3 bg-black/[0.03] border-b border-black/10 flex gap-6 text-xs">
                        <div>
                            <span class="text-black/40 uppercase">Pembeli</span>
                            <div class="mt-0.5 ">{{ selectedItem?.nama ?? '-' }}</div>
                        </div>
                        <div>
                            <span class="text-black/40 uppercase">WhatsApp</span>
                            <div class="mt-0.5 ">{{ selectedItem?.whatsapp ?? '-' }}</div>
                        </div>
                        <div>
                            <span class="text-black/40 uppercase">Rekening</span>
                            <div class="mt-0.5 ">{{ selectedItem?.rekening ?? '-' }}</div>
                        </div>
                    </div>

                    <!-- Produk List -->
                    <div class="overflow-y-auto flex-1 divide-y divide-black/10 px-6">
                        <div v-for="prod in (selectedItem?.produk ?? [])" :key="prod.id" class="py-4">
                            <!-- Nama Produk -->
                            <div class="text-sm font-medium uppercase mb-3">{{ prod.nama ?? '-' }}</div>

                            <!-- Grid Label-Value -->
                            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                                <div class="flex items-center justify-between border-b border-black/5 pb-1.5">
                                    <span class="text-black/40 uppercase">Warna</span>
                                    <span class=" uppercase">{{ prod.warna?.nama ?? '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-black/5 pb-1.5">
                                    <span class="text-black/40 uppercase">Ukuran</span>
                                    <span class=" uppercase">{{ prod.ukuran?.nama ?? '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-black/5 pb-1.5">
                                    <span class="text-black/40 uppercase">Jumlah</span>
                                    <span class="">{{ prod.pivot?.jumlah ?? 0 }} pcs</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-black/5 pb-1.5">
                                    <span class="text-black/40 uppercase">Harga Satuan</span>
                                    <span class="">{{ formatRupiah((prod.pivot?.subtotal ?? 0) / Math.max(1,
                                        prod.pivot?.jumlah ?? 1)) }}</span>
                                </div>
                                <div class="col-span-2 flex items-center justify-between pt-0.5">
                                    <span class="text-black/40 uppercase">Subtotal</span>
                                    <span class="font-medium text-sm">{{ formatRupiah(prod.pivot?.subtotal ?? 0)
                                        }}</span>
                                </div>
                            </div>

                            <Button v-if="hasDesain(prod, selectedItem?.detailPesanan ?? [])" variant="solid"
                                class="mt-3 w-fit px-3 py-1.5 text-[10px] uppercase"
                                @click.stop="openDesignPreview(prod, selectedItem?.detailPesanan ?? [])">
                                Lihat Hasil Kustom Desain
                            </Button>
                        </div>
                        <div v-if="(selectedItem?.produk?.length ?? 0) === 0"
                            class="py-6 text-black/40 text-sm text-center">
                            Tidak ada produk.
                        </div>
                    </div>

                    <!-- Total Footer -->
                    <div class="px-6 py-4 border-t border-black/10 flex items-center justify-between">
                        <span class="text-xs uppercase font-medium text-black/50">Total Pembayaran</span>
                        <span class="text-base font-medium">{{ selectedItem?.totalHarga ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Lightbox Bukti Pembayaran -->
    <div v-if="isLightboxOpen" class="fixed inset-0 z-[60] flex flex-col bg-black/90" @click.self="closeLightbox">
        <!-- Header dengan logo Kisanak -->
        <div class="flex items-center justify-between px-6 py-4 bg-white shrink-0">
            <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="h-8 w-auto" />
            <button type="button"
                class="text-black text-sm px-4 py-1 border border-black hover:bg-black hover:text-white transition-colors"
                @click="closeLightbox">
                Tutup
            </button>
        </div>

        <!-- Preview gambar / PDF -->
        <div class="flex-1 flex items-center justify-center p-6 overflow-auto">
            <img v-if="!lightboxUrl.endsWith('.pdf')" :src="lightboxUrl" alt="Bukti Pembayaran"
                class="max-h-full max-w-full object-contain shadow-lg" />
            <iframe v-else :src="lightboxUrl" class="w-full h-full min-h-[70vh]" frameborder="0"
                title="Bukti Pembayaran PDF" />
        </div>
    </div>

    <!-- Modal Kustom Desain -->
    <DesignPreviewModal :open="isDesignPreviewOpen" :desain-json="previewDesainJson" :product-name="previewProductName"
        :teks-list="previewTeksList" :gambar-list="previewGambarList" :file-excel="previewFileExcel"
        @close="isDesignPreviewOpen = false" />
</template>
