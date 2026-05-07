<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import NavButton from '@/components/NavButton.vue';
import NavIcon from '@/components/NavIcon.vue';
import SearchBar from '@/components/SearchBar.vue';
import CataloguePreview from '@/components/CataloguePreview.vue';
import LoginModal from '@/components/LoginModal.vue';
import RegisterModal from '@/components/RegisterModal.vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Footer from '@/components/Footer.vue';

type ProdukDB = {
    id: number; nama: string; harga_min: number | string; harga_max: number | string; stok: number; stok_minimum: number;
    gambar: string | null; status: string;
    warna?: { id: number; nama: string } | null; ukuran?: { id: number; nama: string };
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
    }));
});

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isConfirmOpen = ref(false);
const isSearchOpen = ref(false);

const page = usePage<any>();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.openLogin) openLogin();
    },
    { immediate: true },
);

const searchBarRef = ref<InstanceType<typeof SearchBar> | null>(null);

function openSearch() {
    isSearchOpen.value = true;
    searchBarRef.value?.focus();
}

const searchQuery = ref(props.filters?.search || '');

function handleSearch(val: string) {
    isSearchOpen.value = false;
    router.get('/katalog', { search: val }, { preserveState: true, replace: true });
}

const cartPreview = {
    productName: 'Kaos Polos Dewasa Cotton Combed 30s',
    color: 'Putih',
    size: 'M',
    quantity: 2,
    subtotal: 'Rp80.000',
    imageSrc: '/images/kaos-1.png',
};

function openLogin() {
    isProfileMenuOpen.value = false;
    isRegisterOpen.value = false;
    isLoginOpen.value = true;
}

function openRegister() {
    isProfileMenuOpen.value = false;
    isLoginOpen.value = false;
    isRegisterOpen.value = true;
}

function closeLogin() {
    isLoginOpen.value = false;
}

function closeRegister() {
    isRegisterOpen.value = false;
}
</script>

<template>

    <Head title="Katalog" />

    <div class="bg-white min-h-screen flex flex-col">
        <!-- Header -->
        <header class="mx-auto w-full px-30 pt-6">
            <div class="grid grid-cols-3 items-center">
                <div />

                <div class="flex items-center justify-center">
                    <Link href="/" aria-label="Beranda">
                        <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="h-12 w-auto" />
                    </Link>
                </div>

                <div class="flex items-center justify-end gap-1">
                    <NavIcon :icon="PhMagnifyingGlass" ariaLabel="Cari" @click="openSearch" />
                    <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" @click="isConfirmOpen = true" />
                    <div class="relative">
                        <NavIcon :icon="PhUserCircle" ariaLabel="Profil" :size="22"
                            @click="isProfileMenuOpen = !isProfileMenuOpen" />

                        <div v-if="isProfileMenuOpen" class="fixed inset-0 z-40" @click="isProfileMenuOpen = false" />

                        <div v-if="isProfileMenuOpen"
                            class="absolute right-0 top-full z-50 mt-2 w-28 overflow-hidden bg-white text-sm"
                            style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12)" @click.stop>
                            <template v-if="!$page.props.auth.user">
                                <button type="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5"
                                    @click="openLogin">
                                    Masuk
                                </button>
                            </template>
                            <template v-else>
                                <Link href="/profil/riwayat-pesanan"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Profil
                                </Link>
                                <Link href="/logout" method="post" as="button"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="mt-5 flex items-center justify-center gap-20 text-sm ">
                <NavButton href="/" variant="default">BERANDA</NavButton>
                <NavButton href="/katalog" variant="active">KATALOG</NavButton>
                <NavButton href="/galeri" variant="default">GALERI</NavButton>
            </nav>
        </header>

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
        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <CartDrawer :open="isConfirmOpen" :productName="cartPreview.productName" :color="cartPreview.color"
            :size="cartPreview.size" :quantity="cartPreview.quantity" :subtotal="cartPreview.subtotal"
            :imageSrc="cartPreview.imageSrc" @close="isConfirmOpen = false" />
    </div>
</template>