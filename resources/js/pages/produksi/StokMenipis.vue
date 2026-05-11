<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

type ProdukDB = {
    id: number;
    nama: string;
    stok: number;
    stok_minimum: number;
    kategori?: { nama: string };
    warna?: { nama: string } | null;
    ukuran?: { nama: string };
};

type PaginatedResponse = {
    data: ProdukDB[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    produks?: PaginatedResponse;
}>();

const currentPage = computed(() => props.produks?.current_page ?? 1);
const totalPages = computed(() => props.produks?.last_page ?? 1);

const items = computed(() => {
    if (!props.produks?.data) return [];
    return props.produks.data.map((p, idx) => ({
        no: (currentPage.value - 1) * (props.produks?.per_page ?? 10) + idx + 1,
        produkNama: p.nama,
        produkKategori: p.kategori?.nama ?? '-',
        warna: p.warna?.nama ?? 'Tidak Ada',
        ukuran: p.ukuran?.nama ?? '-',
        stok: p.stok,
        stokMinimum: p.stok_minimum,
    }));
});

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        router.get(`/produksi/stok-menipis?page=${page}`, {}, { preserveScroll: true });
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

    <Head title="Stok Menipis" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h1 class="text-black text-xl font-medium mb-6">Stok Menipis</h1>

            <div class="border border-black/10 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-white-hover/50">
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">No</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">
                                <span class="inline-flex items-center gap-1">
                                    Nama
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 15l5 5 5-5" />
                                        <path d="M7 9l5-5 5 5" />
                                    </svg>
                                </span>
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Warna</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Ukuran</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Stok</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Stok Minimum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.no" class="border-t border-black/5">
                            <td class="px-4 py-3 text-black">{{ item.no }}</td>
                            <td class="px-4 py-3">
                                <div class="text-black text-sm font-medium uppercase">{{ item.produkNama }}</div>
                                <div class="text-black/50 text-xs uppercase">{{ item.produkKategori }}</div>
                            </td>
                            <td class="px-4 py-3 text-black uppercase">{{ item.warna }}</td>
                            <td class="px-4 py-3 text-black uppercase">{{ item.ukuran }}</td>
                            <td class="px-4 py-3 text-black text-center">{{ item.stok }}</td>
                            <td class="px-4 py-3 text-black text-center">{{ item.stokMinimum }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center gap-1">
                <button v-for="(page, idx) in paginationPages()" :key="idx" type="button"
                    class="min-w-[32px] h-8 px-2 text-sm border"
                    :class="page === currentPage ? 'border-black bg-black text-white' : 'border-black/20 bg-white text-black hover:bg-black/5'"
                    :disabled="typeof page === 'string'" @click="typeof page === 'number' && goToPage(page)">
                    {{ page }}
                </button>
            </div>
        </main>
    </div>
</template>
