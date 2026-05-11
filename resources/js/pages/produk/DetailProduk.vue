<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import CtaButton from '@/components/CtaButton.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';

type ProdukData = {
    id: number; nama: string; harga: number | string; stok: number; stok_minimum: number;
    gambar: string | null; deskripsi: string | null; status: string; satuan: string;
    kategori?: { id: number; nama: string };
    warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string };
};

type ProdukVariant = {
    id: number;
    harga: number | string;
    stok: number;
    stok_minimum: number;
    gambar: string | null;
    deskripsi: string | null;
    warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string } | null;
};

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);

const page = usePage();

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

const props = defineProps<{
    produk?: ProdukData;
    variants?: ProdukVariant[];
    warnaOptions?: string[];
    ukuranOptions?: string[];
}>();

const variants = computed<ProdukVariant[]>(() => props.variants ?? []);

const selectedColor = ref<string>('');
const selectedSize = ref<string>('');

const selectedVariant = computed<ProdukVariant | undefined>(() => {
    if (variants.value.length === 0) return undefined;

    const exact = variants.value.find(
        (v) => (v.warna?.nama ?? '') === selectedColor.value && (v.ukuran?.nama ?? '') === selectedSize.value
    );
    if (exact) return exact;

    const byColor = variants.value.find((v) => (v.warna?.nama ?? '') === selectedColor.value);
    if (byColor) return byColor;

    const bySize = variants.value.find((v) => (v.ukuran?.nama ?? '') === selectedSize.value);
    if (bySize) return bySize;

    return variants.value[0];
});

const productName = computed(() => props.produk?.nama ?? 'Kaos Polos Dewasa Cotton Combed 30s');

function toUnitPrice(value: unknown, fallback = 40000): number {
    const n = typeof value === 'number' ? value : Number(value);
    return Number.isFinite(n) ? n : fallback;
}

const unitPriceNumber = computed(() => {
    const raw = selectedVariant.value?.harga ?? props.produk?.harga;
    return toUnitPrice(raw, 40000);
});

const productPrice = computed(() => {
    return 'Rp' + unitPriceNumber.value.toLocaleString('id-ID');
});
const productDescription = computed(() => {
    const d = selectedVariant.value?.deskripsi ?? props.produk?.deskripsi;
    return (d && d !== '-') ? d : 'Kaos polos ukuran dewasa ini menggunakan bahan cotton combed dengan gramasi 30s yang membuat kaos terasa ringan. Anda tidak akan merasa kepanasan meskipun dipakai di bawah terik matahari. Kaos ini cocok untuk penggunaan sehari-hari.';
});
const productImage = computed(() => {
    const g = selectedVariant.value?.gambar ?? props.produk?.gambar;
    return (g && g !== '-') ? g : '/images/kaos-1.png';
});
const statusLabel = computed(() => {
    const stok = selectedVariant.value?.stok ?? props.produk?.stok;
    const stokMin = selectedVariant.value?.stok_minimum ?? props.produk?.stok_minimum;
    if (stok == null || stokMin == null) return 'Terbaru';
    if (stok <= 0) return 'Stok Habis';
    if (stok <= stokMin) return 'Stok Menipis';
    return 'Terbaru';
});

const descriptionExpanded = ref(false);

const colorOptions = computed(() => {
    if (variants.value.length > 0) {
        return Array.from(new Set(variants.value.map((v) => v.warna?.nama).filter(Boolean) as string[]));
    }
    return props.warnaOptions ?? [];
});
const hasWarna = computed(() => colorOptions.value.length > 0);
const allSizeOptions = computed(() => {
    if (variants.value.length > 0) {
        return Array.from(new Set(variants.value.map((v) => v.ukuran?.nama).filter(Boolean) as string[]));
    }
    return props.ukuranOptions ?? ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];
});

const sizeOptions = computed(() => {
    if (variants.value.length === 0) return allSizeOptions.value;
    if (!selectedColor.value) return allSizeOptions.value;

    const sizes = variants.value
        .filter((v) => (v.warna?.nama ?? '') === selectedColor.value)
        .map((v) => v.ukuran?.nama)
        .filter(Boolean) as string[];

    return Array.from(new Set(sizes));
});

watch(
    variants,
    (list) => {
        if (list.length === 0) {
            selectedColor.value = colorOptions.value[0] ?? selectedColor.value;
            selectedSize.value = allSizeOptions.value[0] ?? selectedSize.value;
            return;
        }

        if (!selectedColor.value) selectedColor.value = list[0].warna?.nama ?? '';
        if (!selectedSize.value) selectedSize.value = list[0].ukuran?.nama ?? '';

        if (selectedColor.value && !colorOptions.value.includes(selectedColor.value)) {
            selectedColor.value = colorOptions.value[0] ?? selectedColor.value;
        }
        if (selectedSize.value && !allSizeOptions.value.includes(selectedSize.value)) {
            selectedSize.value = allSizeOptions.value[0] ?? selectedSize.value;
        }
    },
    { immediate: true }
);

watch(
    selectedColor,
    () => {
        const sizes = sizeOptions.value;
        if (sizes.length > 0 && !sizes.includes(selectedSize.value)) {
            selectedSize.value = sizes[0];
        }
    },
    { immediate: true }
);
const quantity = ref<number>(1);

const isConfirmOpen = ref(false);

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

function addToCart() {
    if (!page.props.auth.user) {
        isLoginOpen.value = true;
        return;
    }

    const variantId = selectedVariant.value?.id ?? props.produk?.id ?? 0;
    const unitPrice = unitPriceNumber.value;
    const cartItem = {
        id: `${variantId}_${selectedColor.value}_${selectedSize.value}_${Date.now()}`,
        productId: variantId,
        imageSrc: productImage.value,
        productName: productName.value,
        color: selectedColor.value,
        size: selectedSize.value,
        unitPrice: unitPrice,
        unitPriceText: formatRupiah(unitPrice),
        quantity: quantity.value,
    };

    const raw = localStorage.getItem('kisanak_cart');
    const cart = raw ? JSON.parse(raw) : [];

    const existingIdx = cart.findIndex(
        (c: any) => c.productId === cartItem.productId && c.color === cartItem.color && c.size === cartItem.size
    );

    if (existingIdx >= 0) {
        cart[existingIdx].quantity += cartItem.quantity;
    } else {
        cart.push(cartItem);
    }

    localStorage.setItem('kisanak_cart', JSON.stringify(cart));
    isConfirmOpen.value = true;
}

function decQty() {
    quantity.value = Math.max(1, quantity.value - 1);
}

function incQty() {
    quantity.value += 1;
}

const subtotalText = computed(() => {
    return formatRupiah(unitPriceNumber.value * quantity.value);
});
</script>

<template>

    <Head :title="productName" />

    <div class="bg-white min-h-screen">
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
                    <NavIcon :icon="PhMagnifyingGlass" ariaLabel="Cari" />
                    <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" @click="page.props.auth.user ? (isConfirmOpen = true) : (isLoginOpen = true)" />
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
                                <Link href="/logout" method="post" as="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto w-full px-30 pb-16 pt-6">
            <div class="text-black font-16px uppercase">
                <Link href="/katalog" class="hover:underline">KATALOG</Link> / {{ productName.toUpperCase() }}
            </div>

            <section class="mt-4 grid grid-cols-2 gap-8">
                <div>
                    <div class="aspect-square w-full overflow-hidden">
                        <img :src="productImage" :alt="productName" class="h-full w-full object-cover" />
                    </div>

                    <!-- <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="aspect-square w-full overflow-hidden">
                            <img :src="productImage" alt="Foto 1" class="h-full w-full object-cover" />
                        </div>
                        <div class="aspect-square w-full overflow-hidden">
                            <img :src="productImage" alt="Foto 2" class="h-full w-full object-cover" />
                        </div>
                        <div class="aspect-square w-full overflow-hidden">
                            <img :src="productImage" alt="Foto 3" class="h-full w-full object-cover" />
                        </div>
                    </div> -->
                </div>

                <div class="text-black max-w-3/4">
                    <div class="inline-flex bg-black px-2 py-1 text-xs text-white">
                        {{ statusLabel }}
                    </div>

                    <h1 class="mt-3 text-xl font-medium leading-tight uppercase">
                        {{ productName }}
                    </h1>
                    <div class="mt-1 text-sm">{{ productPrice }}</div>

                    <div class="mt-4">
                        <p class="text-xs leading-relaxed" :class="{ 'desc-truncate': !descriptionExpanded }">
                            {{ productDescription }}
                        </p>
                        <button type="button" class="mt-2 text-xs underline"
                            @click="descriptionExpanded = !descriptionExpanded">
                            {{ descriptionExpanded ? 'Tutup' : 'Lihat selengkapnya' }}
                        </button>
                    </div>

                    <div class="mt-5">
                                        <div v-if="hasWarna">
                                            <div class="text-sm font-medium uppercase">Warna</div>
                                            <div class="mt-2 grid grid-cols-4 gap-2">
                                                <button v-for="opt in colorOptions" :key="opt" type="button"
                                                    class="px-3 py-1 text-xs uppercase" :style="{
                                                        border: '1px solid black',
                                                        backgroundColor: selectedColor === opt ? 'black' : 'transparent',
                                                        color: selectedColor === opt ? 'white' : 'black',
                                                    }" @click="selectedColor = opt">
                                                    {{ opt }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                    <div class="mt-5">
                        <div class="text-sm font-medium uppercase">Ukuran</div>
                        <div class="mt-2 grid grid-cols-4 gap-2">
                            <button v-for="opt in sizeOptions" :key="opt" type="button" class="px-3 py-1 text-xs"
                                :style="{
                                    border: '1px solid black',
                                    backgroundColor: selectedSize === opt ? 'black' : 'transparent',
                                    color: selectedSize === opt ? 'white' : 'black',
                                }" @click="selectedSize = opt">
                                {{ opt }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-sm font-medium uppercase">Jumlah</div>
                        <div class="mt-2 inline-flex items-center" :style="{ border: '1px solid black' }">
                            <button type="button" class="h-8 w-8" @click="decQty">-</button>
                            <div class="h-8 w-10 text-center text-sm leading-8"
                                :style="{ borderLeft: '1px solid black', borderRight: '1px solid black' }">
                                {{ quantity }}
                            </div>
                            <button type="button" class="h-8 w-8" @click="incQty">+</button>
                        </div>
                    </div>

                    <div class="mt-5">
                        <CtaButton class="w-full" @click="addToCart">
                            Tambah ke Keranjang
                        </CtaButton>
                    </div>
                </div>
            </section>
        </main>

        <CartDrawer :open="isConfirmOpen" :productId="selectedVariant?.id ?? props.produk?.id ?? 0" :productName="productName"
            :color="selectedColor" :size="selectedSize" :quantity="quantity" :subtotal="subtotalText"
            :unitPrice="unitPriceNumber" :imageSrc="productImage" @close="isConfirmOpen = false" />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />
    </div>
</template>

<style scoped>
.desc-truncate {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>