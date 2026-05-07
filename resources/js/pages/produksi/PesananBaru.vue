<script setup lang="ts">
import Sidebar from '../../components/Sidebar.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type PesananDB = {
    id: number;
    id_pembeli: number;
    id_produk: number;
    jumlah: number;
    total_harga: number;
    prioritas: string;
    tenggat_waktu: string | null;
    catatan: string | null;
    status: string;
    estimasi_selesai: string | null;
    pembeli?: { id: number; nama: string; whatsapp: string };
    produk?: {
        id: number; nama: string;
        warna?: { nama: string }; ukuran?: { nama: string };
    };
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

const pembayaranOptions = ['Menunggu', 'Belum Konfirmasi', 'Terkonfirmasi'] as const;

const page = usePage<any>();
const roles = computed<string[]>(() => page.props?.auth?.roles ?? []);
const isKasir = computed(() => roles.value.includes('kasir'));
const isPemilik = computed(() => roles.value.includes('pemilik'));
const pembayaranOptionsAllowed = computed(() => {
    if (isPemilik.value) return [...pembayaranOptions];
    if (isKasir.value) return pembayaranOptions.filter((s) => s !== 'Terkonfirmasi');
    return [...pembayaranOptions];
});

const currentPage = computed(() => props.pesanan?.current_page ?? 1);
const totalPages = computed(() => props.pesanan?.last_page ?? 1);

const items = computed(() => {
    if (!props.pesanan?.data) return [];
    return props.pesanan.data.map((p, idx) => ({
        id: p.id,
        pembayaranId: p.pembayaran?.id ?? null,
        no: (currentPage.value - 1) * (props.pesanan?.per_page ?? 10) + idx + 1,
        avatarInitial: (p.pembeli?.nama ?? '?')[0].toUpperCase(),
        nama: p.pembeli?.nama ?? '-',
        whatsapp: p.pembeli?.whatsapp ?? '-',
        produkNama: p.produk?.nama?.toUpperCase() ?? '-',
        produkVariasi: `${(p.produk?.warna?.nama ?? '-').toUpperCase()} / ${(p.produk?.ukuran?.nama ?? '-').toUpperCase()}`,
        jumlah: p.jumlah,
        totalHarga: formatRupiah(p.total_harga),
        tenggatWaktu: formatDate(p.tenggat_waktu),
        statusPembayaran: (p.pembayaran?.status ?? 'Menunggu') as 'Menunggu' | 'Belum Konfirmasi' | 'Terkonfirmasi',
        estimasiSelesai: formatDate(p.estimasi_selesai),
    }));
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

function updatePembayaranStatus(item: any, event: Event) {
    if (!item.pembayaranId) return;
    const status = (event.target as HTMLSelectElement).value as (typeof pembayaranOptions)[number];
    router.patch(`/produksi/pembayaran/${item.pembayaranId}/status`, { status }, { preserveScroll: true });
}

function pembayaranColor(status: string): string {
    switch (status) {
        case 'Terkonfirmasi': return 'bg-green text-white';
        case 'Belum Konfirmasi': return 'bg-orange text-white';
        default: return 'bg-yellow text-black';
    }
}

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        router.get(`/produksi/pesanan-baru?page=${page}`, {}, { preserveScroll: true });
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

    <Head title="Pesanan Baru" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h1 class="text-black text-xl font-medium mb-6">Pesanan Baru</h1>

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
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Jumlah</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Total Harga</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Tenggat Waktu</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Status Pembayaran
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Estimasi Selesai
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="8" class="px-3 py-6 text-center text-black/50">Belum ada pesanan baru.</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="border-t border-black/5">
                            <td class="px-3 py-3 text-black">{{ item.no }}</td>
                            <td class="px-3 py-3">
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
                            <td class="px-3 py-3">
                                <div class="text-black text-sm font-medium uppercase">{{ item.produkNama }}</div>
                                <div class="text-black/50 text-xs uppercase">{{ item.produkVariasi }}</div>
                            </td>
                            <td class="px-3 py-3 text-black">{{ item.jumlah }}</td>
                            <td class="px-3 py-3 text-black">{{ item.totalHarga }}</td>
                            <td class="px-3 py-3 text-black">{{ item.tenggatWaktu }}</td>
                            <td class="px-3 py-3">
                                <select class="px-3 py-1 rounded-full text-xs" :class="pembayaranColor(item.statusPembayaran)"
                                    :value="item.statusPembayaran"
                                    :disabled="!item.pembayaranId || (isKasir && item.statusPembayaran === 'Terkonfirmasi')"
                                    @change="updatePembayaranStatus(item, $event)">
                                    <option v-for="opt in pembayaranOptionsAllowed" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-3 text-black">{{ item.estimasiSelesai }}</td>
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
        </main>
    </div>
</template>
