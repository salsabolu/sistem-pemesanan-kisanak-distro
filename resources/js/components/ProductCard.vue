<script setup lang="ts">
/**
 * ProductCard — Komponen fleksibel untuk menampilkan satu item produk pesanan.
 *
 * Props:
 *  - imageSrc        : URL gambar produk
 *  - imageAlt        : Alt text gambar (opsional, default nama produk)
 *  - productName     : Nama produk (uppercase otomatis)
 *  - color           : Nama warna (opsional)
 *  - size            : Nama ukuran (opsional)
 *  - price           : Harga satuan (teks, misal "Rp43.000")
 *  - quantity        : Jumlah item (opsional)
 *  - subtotal        : Subtotal (opsional, tampil jika showSubtotal true)
 *  - showQuantity    : Tampilkan baris qty/harga (default false)
 *  - showSubtotal    : Tampilkan baris subtotal (default false)
 *  - imageSize       : Ukuran gambar — 'sm' (80px) | 'md' (128px) | 'lg' (160px, default)
 */
interface Props {
    imageSrc: string;
    imageAlt?: string;
    productName: string;
    color?: string | null;
    size?: string | null;
    price?: string;
    quantity?: number;
    subtotal?: string;
    showQuantity?: boolean;
    showSubtotal?: boolean;
    imageSize?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    imageAlt: undefined,
    color: null,
    size: null,
    price: '',
    quantity: 0,
    subtotal: '',
    showQuantity: false,
    showSubtotal: false,
    imageSize: 'lg',
});
</script>

<template>
    <div class="flex gap-4">
        <!-- Gambar produk -->
        <div class="shrink-0 overflow-hidden border border-black"
            :style="{
                width: imageSize === 'sm' ? '80px' : imageSize === 'md' ? '128px' : '160px',
                height: imageSize === 'sm' ? '80px' : imageSize === 'md' ? '128px' : '160px',
            }">
            <img :src="imageSrc" :alt="imageAlt ?? productName"
                class="h-full w-full object-cover" />
        </div>

        <!-- Info produk -->
        <div class="min-w-0 flex-1">
            <!-- Nama produk -->
            <div class="text-sm font-medium uppercase leading-snug text-black">
                {{ productName }}
            </div>

            <!-- Warna / Ukuran -->
            <div v-if="color || size" class="mt-1 text-xs uppercase text-black">
                <template v-if="color && size">
                    {{ color.toUpperCase() }} / {{ size.toUpperCase() }}
                </template>
                <template v-else-if="color">{{ color.toUpperCase() }}</template>
                <template v-else-if="size">{{ size.toUpperCase() }}</template>
            </div>

            <!-- Harga satuan (tanpa qty) -->
            <div v-if="price && !showQuantity" class="mt-1 text-sm text-black">{{ price }}</div>

            <!-- Jumlah / harga per-qty (untuk riwayat pesanan) -->
            <div v-if="showQuantity && quantity > 0" class="mt-1 text-xs text-black">
                {{ quantity }} / {{ price }}
            </div>

            <!-- Subtotal -->
            <div v-if="showSubtotal && subtotal" class="mt-1 text-xs text-black">
                {{ subtotal }}
            </div>

            <!-- Slot untuk konten tambahan (misal tombol qty di CartDrawer & OrderItemList) -->
            <slot />
        </div>
    </div>
</template>
