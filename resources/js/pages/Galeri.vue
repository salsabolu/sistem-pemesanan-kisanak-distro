<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { ref } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Footer from '@/components/Footer.vue';
import GalleryPicture from '@/components/GalleryPicture.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavButton from '@/components/NavButton.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';

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

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isConfirmOpen = ref(false);

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

    <Head title="Galeri" />

    <div class="bg-white min-h-screen flex flex-col">
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
                                <Link href="/logout" method="post" as="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="mt-5 flex items-center justify-center gap-20 text-sm ">
                <NavButton href="/" variant="default">BERANDA</NavButton>
                <NavButton href="/katalog" variant="default">KATALOG</NavButton>
                <NavButton href="/galeri" variant="active">GALERI</NavButton>
            </nav>
        </header>

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

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <CartDrawer :open="isConfirmOpen" :productName="cartPreview.productName" :color="cartPreview.color"
            :size="cartPreview.size" :quantity="cartPreview.quantity" :subtotal="cartPreview.subtotal"
            :imageSrc="cartPreview.imageSrc" @close="isConfirmOpen = false" />
    </div>
</template>