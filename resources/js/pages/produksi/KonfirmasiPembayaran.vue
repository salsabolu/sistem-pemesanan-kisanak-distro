<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

type PembayaranDB = {
    id: number;
    id_pembeli: number;
    id_pesanan: number;
    bukti_pembayaran: string | null;
    status: string;
    pembeli?: { id: number; nama: string; whatsapp: string };
    pesanan?: { id: number; total_harga: number; produk?: { nama: string } };
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

const statusOptions = ['Menunggu', 'Belum Konfirmasi', 'Terkonfirmasi'] as const;

const page = usePage<any>();
const roles = computed<string[]>(() => page.props?.auth?.roles ?? []);
const isKasir = computed(() => roles.value.includes('kasir'));
const isPemilik = computed(() => roles.value.includes('pemilik'));

const currentPage = computed(() => props.pembayaran?.current_page ?? 1);
const totalPages = computed(() => props.pembayaran?.last_page ?? 1);

const items = computed(() => {
    if (!props.pembayaran?.data) return [];
    return props.pembayaran.data.map((p, idx) => ({
        id: p.id,
        no: (currentPage.value - 1) * (props.pembayaran?.per_page ?? 10) + idx + 1,
        avatarInitial: (p.pembeli?.nama ?? '?')[0].toUpperCase(),
        nama: p.pembeli?.nama ?? '-',
        whatsapp: p.pembeli?.whatsapp ?? '-',
        idPesanan: String(p.id_pesanan),
        totalHarga: formatRupiah(p.pesanan?.total_harga ?? 0),
        buktiPembayaran: p.bukti_pembayaran,
        statusPembayaran: p.status as 'Menunggu' | 'Belum Konfirmasi' | 'Terkonfirmasi',
    }));
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
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">ID Pesanan</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Total Harga</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Bukti Pembayaran
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Status Pembayaran
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-black/50">Belum ada pembayaran yang perlu
                                dikonfirmasi.</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="border-t border-black/5">
                            <td class="px-4 py-3 text-black">{{ item.no }}</td>
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
                            <td class="px-4 py-3 text-black">{{ item.idPesanan }}</td>
                            <td class="px-4 py-3 text-black">{{ item.totalHarga }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a v-if="item.buktiPembayaran" :href="buktiUrl(item.buktiPembayaran)"
                                        target="_blank" class="px-3 py-1 rounded-full text-xs text-black bg-yellow">
                                        Lihat
                                    </a>

                                    <label v-if="isKasir"
                                        class="px-3 py-1 rounded-full text-xs text-black bg-yellow cursor-pointer">
                                        Upload
                                        <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                                            @change="uploadBukti(item, $event)" />
                                    </label>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <select class="px-3 py-1 rounded-full text-xs"
                                    :class="statusColor(item.statusPembayaran)" :value="item.statusPembayaran"
                                    :disabled="!isPemilik" @change="updateStatus(item, $event)">
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
        </main>
    </div>
</template>
