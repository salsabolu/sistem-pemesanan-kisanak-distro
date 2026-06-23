<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Button from '@/components/Button.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';

type ProdukData = {
    id: number; nama: string; harga: number | string; stok: number; stok_minimum: number;
    gambar: string | null; deskripsi: string | null; status: string;
    kategori?: { id: number; nama: string };
    warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string };
    is_customizable: boolean;
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

const page = usePage();
const headerRef = ref<InstanceType<typeof PublicHeader> | null>(null);

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

const productName = computed(() => props.produk?.nama ?? '');

const breadcrumbItems = computed(() => [
    { title: 'Katalog', href: '/katalog' },
    { title: productName.value },
]);

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
    return d && d !== '-' ? d : '';
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
        const uniqueSizes = new Map<string, number>();
        variants.value.forEach((v) => {
            if (v.ukuran?.nama && v.ukuran?.id) {
                if (!uniqueSizes.has(v.ukuran.nama)) {
                    uniqueSizes.set(v.ukuran.nama, v.ukuran.id);
                }
            }
        });
        return Array.from(uniqueSizes.entries())
            .sort((a, b) => a[1] - b[1])
            .map(entry => entry[0]);
    }
    return props.ukuranOptions ?? ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];
});

const sizeOptions = computed(() => {
    if (variants.value.length === 0) return allSizeOptions.value;
    if (!selectedColor.value) return allSizeOptions.value;

    const uniqueSizes = new Map<string, number>();
    variants.value.forEach((v) => {
        if ((v.warna?.nama ?? '') === selectedColor.value && v.ukuran?.nama && v.ukuran?.id) {
            if (!uniqueSizes.has(v.ukuran.nama)) {
                uniqueSizes.set(v.ukuran.nama, v.ukuran.id);
            }
        }
    });
    return Array.from(uniqueSizes.entries())
        .sort((a, b) => a[1] - b[1])
        .map(entry => entry[0]);
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
        headerRef.value?.openLogin();
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

const subtotalText = computed(() => {
    return formatRupiah(unitPriceNumber.value * quantity.value);
});
</script>

<template>

    <Head :title="productName" />

    <div class="bg-white min-h-screen">
        <!-- Header -->
        <PublicHeader ref="headerRef" @cart-click="isConfirmOpen = true" />

        <!-- Main Content -->
        <main class="mx-auto w-full px-30 pb-16 pt-6">
            <Breadcrumbs :breadcrumbs="breadcrumbItems" separator="/" uppercase />

            <section class="mt-4 grid grid-cols-2 gap-8">
                <div>
                    <div class="aspect-square w-full overflow-hidden">
                        <img :src="productImage" :alt="productName" class="h-full w-full object-cover" />
                    </div>
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
                            <div class="text-sm font-medium uppercase mb-2">Warna</div>
                            <div class="grid grid-cols-4 gap-2">
                                <Button v-for="opt in colorOptions" :key="opt" variant="option"
                                    :active="selectedColor === opt" @click="selectedColor = opt">
                                    {{ opt }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-sm font-medium uppercase mb-2">Ukuran</div>
                        <div class="grid grid-cols-4 gap-2">
                            <Button v-for="opt in sizeOptions" :key="opt" variant="option"
                                :active="selectedSize === opt" @click="selectedSize = opt">
                                {{ opt }}
                            </Button>
                        </div>
                    </div>

                    <div class="mt-5">
                        <Button variant="quantity" label="Jumlah" v-model="quantity" />
                    </div>

                    <div class="flex flex-row gap-2 mt-5">
                        <Link v-if="props.produk?.is_customizable" :href="`/katalog/produk/${props.produk?.id}/kustomisasi?warna=${encodeURIComponent(selectedColor)}&ukuran=${encodeURIComponent(selectedSize)}`" class="block w-full">
                            <Button class="w-full" variant="primary">
                                Kustomisasi Desain
                            </Button>
                        </Link>
                        <Button class="w-full" variant="primary" @click="addToCart">
                            Tambah ke Keranjang
                        </Button>
                    </div>

                </div>
            </section>
        </main>

        <!-- Footer -->
        <Footer />

        <CartDrawer :open="isConfirmOpen" :productId="selectedVariant?.id ?? props.produk?.id ?? 0"
            :productName="productName" :color="selectedColor" :size="selectedSize" :quantity="quantity"
            :subtotal="subtotalText" :unitPrice="unitPriceNumber" :imageSrc="productImage"
            @close="isConfirmOpen = false" />
    </div>
</template>

<style scoped>
.desc-truncate {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>