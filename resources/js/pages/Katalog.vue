<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import CataloguePreview from '@/components/CataloguePreview.vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import SearchBar from '@/components/SearchBar.vue';

type ProdukDB = {
    id: number; nama: string; harga_min: number | string; harga_max: number | string; stok: number; stok_minimum: number;
    gambar: string | null; status: string;
    warna?: { id: number; nama: string } | null; ukuran?: { id: number; nama: string };
    kategori?: string;
};

const props = defineProps<{
    produks?: ProdukDB[];
    filters?: { search?: string };
}>();

function getStatus(p: ProdukDB): 'Terbaru' | 'Stok Menipis' | 'Stok Habis' {
    if (p.stok <= 0) return 'Stok Habis';
    if (p.stok <= p.stok_minimum) return 'Stok Menipis';
    return 'Terbaru';
}

function formatHarga(h: number) {
    return 'Rp' + Number(h).toLocaleString('id-ID');
}

function toNumber(value: unknown, fallback = 0): number {
    const n = typeof value === 'number' ? value : Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function formatHargaRange(minRaw: unknown, maxRaw: unknown): string {
    const min = toNumber(minRaw);
    const max = toNumber(maxRaw);
    if (min === max) return formatHarga(min);
    return `${formatHarga(min)} - ${formatHarga(max)}`;
}

const catalogueItems = computed(() => {
    if (!props.produks) return [];
    return props.produks.map(p => ({
        id: p.id,
        status: getStatus(p),
        imageSrc: (p.gambar && p.gambar !== '-') ? p.gambar : '/images/kaos-1.png',
        name: p.nama,
        price: formatHargaRange(p.harga_min, p.harga_max),
        kategori: p.kategori ?? '',
    }));
});

const isConfirmOpen = ref(false);
const isSearchOpen = ref(false);

const searchBarRef = ref<InstanceType<typeof SearchBar> | null>(null);

async function openSearch() {
    isSearchOpen.value = true;
    await nextTick();
    searchBarRef.value?.focus();
}

const searchQuery = ref(props.filters?.search || '');

function handleSearch(val: string) {
    isSearchOpen.value = false;
    router.get('/katalog', { search: val }, { preserveState: true, replace: true });
}

</script>

<template>

    <Head title="Katalog" />

    <div class="bg-white min-h-screen flex flex-col">
        <!-- Header -->
        <PublicHeader showNav activeNav="katalog" @cart-click="isConfirmOpen = true" @search-click="openSearch" />

        <main class="flex-1 mx-auto w-full px-30 pb-16 pt-6 flex flex-col items-center">
            <div v-if="catalogueItems.length === 0" class="text-black/50 text-sm py-10">
                Belum ada produk yang aktif.
            </div>
            <CataloguePreview v-else :items="catalogueItems" />
        </main>

        <!-- Footer -->
        <Footer />

        <SearchBar v-if="isSearchOpen" ref="searchBarRef" v-model="searchQuery" @search="handleSearch"
            @close="isSearchOpen = false" placeholder="Cari produk..." />

        <CartDrawer :open="isConfirmOpen" @close="isConfirmOpen = false" />
    </div>
</template>