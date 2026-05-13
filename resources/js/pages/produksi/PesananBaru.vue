<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

type PesananDB = {
    id: number;
    id_pembeli: number;
    total: number;
    prioritas: string;
    status: string;
    created_at?: string | null;
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
        const totalQty = (p.produk ?? []).reduce((sum, pr) => sum + (pr.pivot?.jumlah ?? 0), 0);

        return {
            id: p.id,
            pembayaranId: p.pembayaran?.id ?? null,
            no: (currentPage.value - 1) * (props.pesanan?.per_page ?? 10) + idx + 1,
            jamMasuk: formatTime(p.created_at ?? null),
            avatarInitial: (p.pembeli?.nama ?? '?')[0].toUpperCase(),
            nama: p.pembeli?.nama ?? '-',
            whatsapp: p.pembeli?.whatsapp ?? '-',
            produkText: produkCount > 0 ? `${produkCount} Produk` : '-',
            jumlah: totalQty,
            totalHarga: formatRupiah(p.total),
            statusPembayaran: (p.pembayaran?.status ?? 'Menunggu') as 'Menunggu' | 'Belum Konfirmasi' | 'Terkonfirmasi',
            produk: p.produk ?? [],
        };
    });
});

function formatRupiah(value: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
}

function formatTime(value: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    if (isNaN(d.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(d);
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
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Jam Masuk</th>
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
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Status Pembayaran
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="7" class="px-3 py-6 text-center text-black/50">Belum ada pesanan baru.</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="border-t border-black/5 cursor-pointer hover:bg-black/5"
                            @click="openDetail(item)">
                            <td class="px-3 py-3 text-black text-sm">{{ item.no }}</td>
                            <td class="px-3 py-3 text-black text-sm">{{ item.jamMasuk }}</td>
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
                                <div class="text-black text-sm font-medium uppercase">{{ item.produkText }}</div>
                            </td>
                            <td class="px-3 py-3 text-black text-sm">{{ item.jumlah }}</td>
                            <td class="px-3 py-3 text-black text-sm">{{ item.totalHarga }}</td>
                            <td class="px-3 py-3">
                                <select class="px-3 py-1 rounded-full text-xs" :class="pembayaranColor(item.statusPembayaran)"
                                    :value="item.statusPembayaran"
                                    :disabled="!item.pembayaranId || (isKasir && item.statusPembayaran === 'Terkonfirmasi')"
                                    @click.stop
                                    @change="updatePembayaranStatus(item, $event)">
                                    <option v-for="opt in pembayaranOptionsAllowed" :key="opt" :value="opt">{{ opt }}</option>
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
                        <button type="button" class="px-4 py-2 text-sm border border-black" @click="closeDetail">Tutup</button>
                    </div>

                    <div class="mt-4 divide-y divide-black/10">
                        <div v-for="prod in (selectedItem?.produk ?? [])" :key="prod.id" class="py-3">
                            <div class="text-black text-sm font-medium uppercase">{{ prod.nama ?? '-' }}</div>
                            <div class="mt-1 text-black/50 text-xs uppercase">WARNA: 
                                {{ (prod.warna?.nama ?? '-').toUpperCase() }} / UKURAN: {{ (prod.ukuran?.nama ?? '-').toUpperCase() }}
                            </div>
                            <div class="mt-1 text-black text-xs">JUMLAH: 
                                {{ prod.pivot?.jumlah ?? 0 }} / HARGA: 
                                {{ formatRupiah(((prod.pivot?.subtotal ?? 0) / Math.max(1, prod.pivot?.jumlah ?? 1))) }}
                            </div>
                            <div class="mt-1 text-black text-xs">SUBTOTAL: {{ formatRupiah(prod.pivot?.subtotal ?? 0) }}</div>
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
