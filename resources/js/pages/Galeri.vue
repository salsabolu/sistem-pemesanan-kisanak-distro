<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Footer from '@/components/Footer.vue';
import GalleryPicture from '@/components/GalleryPicture.vue';
import PublicHeader from '@/components/PublicHeader.vue';

interface GalleryItem {
    src: string;
    caption: string;
}

const galleryItems: GalleryItem[] = [
    { src: '/images/galeri-1.jpg', caption: 'Cetak DTF Meteran' },
    { src: '/images/galeri-2.jpg', caption: 'Cetak DTF Raster' },
    { src: '/images/galeri-3.jpg', caption: 'Cetak DTF Warna Bebas' },
    { src: '/images/galeri-4.jpg', caption: 'Sablon Kaos Kelas' },
    { src: '/images/galeri-5.jpg', caption: 'Sablon pada Hoodie' },
    { src: '/images/galeri-6.jpg', caption: 'Sablon pada Kaos Polo' },
    { src: '/images/galeri-7.jpg', caption: 'Sablon pada Jersey' },
    { src: '/images/galeri-8.jpg', caption: 'Detail Hasil Cetak DTF' },
    { src: '/images/galeri-9.jpg', caption: 'Detail Warna Cetak DTF' },
];

const isConfirmOpen = ref(false);
</script>

<template>

    <Head title="Galeri" />

    <div class="bg-white min-h-screen flex flex-col">
        <PublicHeader showNav activeNav="galeri" @cart-click="isConfirmOpen = true" />

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full px-30 pb-16 pt-6">
            <section class="w-full">
                <div class="grid grid-cols-3 gap-4">
                    <GalleryPicture v-for="(item, idx) in galleryItems" :key="idx" :src="item.src"
                        :caption="item.caption" />
                </div>
            </section>
        </main>

        <!-- Footer -->
        <Footer />

        <CartDrawer :open="isConfirmOpen" @close="isConfirmOpen = false" />
    </div>
</template>