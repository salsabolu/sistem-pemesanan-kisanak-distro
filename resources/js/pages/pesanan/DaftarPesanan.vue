<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

type PesananDB = {
    id: number;
    id_pembeli: number;
    total: number;
    prioritas: string;
    status: string;
    tenggat_waktu?: string | null;
    estimasi_selesai?: string | null;
    pembeli?: { id: number; nama: string; whatsapp: string };
    produk?: Array<{
        id: number; nama: string;
        warna?: { nama: string }; ukuran?: { nama: string };
        pivot?: { jumlah: number; subtotal: number };
    }>;
    pembayaran?: { id: number; status: string } | null;
};

type PaginatedResponse = {
    data: PesananDB[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    pesanan?: PaginatedResponse;
}>();

const statusPembayaranOptions = ['Menunggu', 'Belum Konfirmasi', 'Terkonfirmasi'] as const;
const prioritasOptions = ['Normal', 'Tinggi'] as const;
const statusPesananOptions = ['Pesanan Baru', 'Dalam Produksi', 'Selesai', 'Dibatalkan'] as const;

const currentPage = computed(() => props.pesanan?.current_page ?? 1);
const totalPages = computed(() => props.pesanan?.last_page ?? 1);

const isDetailOpen = ref(false);
const selectedItem = ref<any>(null);

function openDetail(item: any) {
    selectedItem.value = item;
    isDetailOpen.value = true;
}

function closeDetail() {
    isDetailOpen.value = false;
    selectedItem.value = null;
}

const items = computed(() => {
    if (!props.pesanan?.data) return [];
    return props.pesanan.data.map((p, idx) => {
        const produkCount = (p.produk ?? []).length;

        return {
            id: p.id,
            no: (currentPage.value - 1) * (props.pesanan?.per_page ?? 10) + idx + 1,
            nama: p.pembeli?.nama ?? '-',
            whatsapp: p.pembeli?.whatsapp ?? '-',
            produkText: produkCount > 0 ? `${produkCount} Produk` : '-',
            totalHarga: formatRupiah(p.total),
            statusPembayaran: (p.pembayaran?.status ?? 'Menunggu') as 'Menunggu' | 'Belum Konfirmasi' | 'Terkonfirmasi',
            prioritas: p.prioritas as 'Normal' | 'Tinggi',
            tenggatWaktu: formatDate(p.tenggat_waktu ?? null),
            estimasiSelesai: formatDate(p.estimasi_selesai ?? null),
            statusPesanan: p.status as 'Pesanan Baru' | 'Dalam Produksi' | 'Selesai' | 'Dibatalkan',
            pembayaranId: p.pembayaran?.id ?? null,
            produk: p.produk ?? [],
        };
    });
});

function formatRupiah(value: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
}

function formatDate(value: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}-${mm}-${d.getFullYear()}`;
}


function updateStatusPembayaran(item: any, event: Event) {
    if (!item.pembayaranId) return;
    const status = (event.target as HTMLSelectElement).value as (typeof statusPembayaranOptions)[number];
    router.patch(`/produksi/pembayaran/${item.pembayaranId}/status`, { status }, { preserveScroll: true });
}

function updatePrioritas(item: any, event: Event) {
    const prioritas = (event.target as HTMLSelectElement).value as (typeof prioritasOptions)[number];
    router.patch(`/pesanan/pesanan/${item.id}/prioritas`, { prioritas }, { preserveScroll: true });
}

function updateStatusPesanan(item: any, event: Event) {
    const status = (event.target as HTMLSelectElement).value as (typeof statusPesananOptions)[number];
    router.patch(`/pesanan/pesanan/${item.id}/status`, { status }, { preserveScroll: true });
}

function statusPembayaranColor(status: string): string {
    switch (status) {
        case 'Terkonfirmasi': return 'bg-green text-white';
        case 'Belum Konfirmasi': return 'bg-red text-white';
        default: return 'bg-yellow text-black';
    }
}

function prioritasColor(prioritas: string): string {
    switch (prioritas) {
        case 'Tinggi': return 'bg-red text-white';
        default: return 'bg-yellow text-black';
    }
}

function statusPesananColor(status: string): string {
    switch (status) {
        case 'Pesanan Baru': return 'bg-yellow text-black';
        case 'Dalam Produksi': return 'bg-orange text-white';
        case 'Selesai': return 'bg-green text-white';
        case 'Dibatalkan': return 'bg-red text-white';
        default: return 'bg-yellow text-black';
    }
}

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        router.get(`/pesanan/daftar-pesanan?page=${page}`, {}, { preserveScroll: true });
    }
}

function paginationPages(): (number | string)[] {
    const total = totalPages.value;
    const pages: (number | string)[] = [];
    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1, 2);
        pages.push('...');
        pages.push(total - 1, total);
    }
    return pages;
}
</script>

<template>

    <Head title="Daftar Pesanan" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h1 class="text-black text-xl font-medium mb-6">Daftar Pesanan</h1>

            <div class="border border-black/10 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-white-hover/50">
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">No</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">
                                <span class="inline-flex items-center gap-1">
                                    Pembeli
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 15l5 5 5-5" />
                                        <path d="M7 9l5-5 5 5" />
                                    </svg>
                                </span>
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Produk</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Total Harga</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Status Pembayaran
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Tenggat Waktu</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Estimasi Selesai
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Prioritas</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="9" class="px-3 py-6 text-center text-black/50">Belum ada pesanan.</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="border-t border-black/5 cursor-pointer hover:bg-black/5"
                            @click="openDetail(item)">
                            <td class="px-3 py-3 text-black">{{ item.no }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center shrink-0">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.5">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 20c0-4 4-7 8-7s8 3 8 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-black text-sm font-medium">{{ item.nama }}</div>
                                        <div class="text-black/50 text-xs">{{ item.whatsapp }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-black text-sm font-medium uppercase">{{ item.produkText }}</div>
                            </td>
                            <td class="px-3 py-3 text-black">{{ item.totalHarga }}</td>
                            <td class="px-3 py-3">
                                <select class="w-full pl-1 pr-3 py-1 rounded-full text-xs"
                                    :class="statusPembayaranColor(item.statusPembayaran)"
                                    :value="item.statusPembayaran" :disabled="!item.pembayaranId"
                                    @click.stop
                                    @change="updateStatusPembayaran(item, $event)">
                                    <option v-for="opt in statusPembayaranOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-3 text-black">{{ item.tenggatWaktu }}</td>
                            <td class="px-3 py-3 text-black">{{ item.estimasiSelesai }}</td>
                            <td class="px-3 py-3">
                                <select class="w-full pl-1 pr-3 py-1 rounded-full text-xs" :class="prioritasColor(item.prioritas)"
                                    :value="item.prioritas" @click.stop @change="updatePrioritas(item, $event)">
                                    <option v-for="opt in prioritasOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-3">
                                <select class="px-3 py-1 rounded-full text-xs" :class="statusPesananColor(item.statusPesanan)"
                                    :value="item.statusPesanan" :disabled="item.statusPesanan === 'Selesai'" @click.stop
                                    @change="updateStatusPesanan(item, $event)">
                                    <option v-for="opt in statusPesananOptions" :key="opt" :value="opt">{{ opt }}</option>
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
                <div class="relative bg-white text-black p-6 w-full max-w-lg mx-4"
                    style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15)">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-black text-sm font-medium uppercase">Detail Pesanan</div>
                            <div class="mt-1 text-black/50 text-xs">ID: {{ selectedItem?.id ?? '-' }}</div>
                        </div>
                        <button type="button" class="px-4 py-2 text-sm border border-black"
                            @click="closeDetail">Tutup</button>
                    </div>

                    <div class="mt-4 divide-y divide-black/10">
                        <div v-for="prod in (selectedItem?.produk ?? [])" :key="prod.id" class="py-3">
                            <div class="text-black text-sm font-medium uppercase">{{ prod.nama ?? '-' }}</div>
                            <div class="mt-1 text-black/50 text-xs uppercase">
                                {{ (prod.warna?.nama ?? '-').toUpperCase() }} / {{ (prod.ukuran?.nama ?? '-').toUpperCase() }}
                            </div>
                            <div class="mt-1 text-black text-xs">
                                {{ prod.pivot?.jumlah ?? 0 }} /
                                {{ formatRupiah(((prod.pivot?.subtotal ?? 0) / Math.max(1, prod.pivot?.jumlah ?? 1))) }}
                            </div>
                            <div class="mt-1 text-black text-xs">{{ formatRupiah(prod.pivot?.subtotal ?? 0) }}</div>
                        </div>
                        <div v-if="(selectedItem?.produk?.length ?? 0) === 0" class="py-3 text-black/50 text-sm">
                            Tidak ada produk.
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <div class="uppercase">Total:</div>
                        <div>{{ selectedItem?.totalHarga ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
