<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { ref, watch } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import CtaButton from '@/components/CtaButton.vue';
import Footer from '@/components/Footer.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavButton from '@/components/NavButton.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';

const page = usePage<any>();

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

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.openLogin) openLogin();
        if (flash?.openRegister) openRegister();
    },
    { immediate: true },
);
</script>

<template>

    <Head title="Beranda" />

    <div class="bg-white relative min-h-screen flex flex-col">
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
                <NavButton href="/" variant="active">BERANDA</NavButton>
                <NavButton href="/katalog" variant="default">KATALOG</NavButton>
                <NavButton href="/galeri" variant="default">GALERI</NavButton>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full px-30 pb-16 pt-6">
            <!-- 1 Main Picture -->
            <section class="mb-4">
                <img src="/images/beranda-1.jpg" alt="Beranda" class="w-full object-cover" style="height: 460px" />
            </section>

            <!-- 2 Pictures + Tagline + CTA Button -->
            <section class="mb-4 flex gap-4">
                <div class="w-1/3 h-105 overflow-hidden">
                    <img src="/images/beranda-2.jpg" alt="Gambar 2" class="h-full w-full object-cover" />
                </div>
                <div class="w-1/3 h-105 overflow-hidden">
                    <img src="/images/beranda-3.jpg" alt="Gambar 3" class="h-full w-full object-cover" />
                </div>
                <section class="max-w-1/3">
                    <h1 class="font-medium leading-tight" style="font-size: 30px; color: black">
                        Hai, kami Kisanak Distro
                    </h1>
                    <p class="mt-1 leading-tight" style="font-size: 16px; color: black">
                        Wujudkan Desainmu, Satuan Pun Kami Layani! Layanan Cetak DTF & Sablon Premium. Full color,
                        kualitas distro.
                    </p>

                    <div class="mt-6">
                        <Link href="/katalog">
                            <CtaButton>Belanja Sekarang</CtaButton>
                        </Link>
                    </div>
                </section>
            </section>

            <!-- Address + Operational Hours + Store Picture -->
            <section class="gap-auto flex w-full justify-between">
                <section class="items-baseline justify-end max-w-1/3 gap-y-4 flex flex-col">
                    <h1 class="font-medium leading-tight" style="font-size: 30px; color: black">
                        Informasi
                    </h1>
                    <span>
                        <h1 class="font-normal leading-tight" style="font-size: 24px; color: black">
                            Lokasi
                        </h1>
                        <p class="mt-1 leading-tight" style="font-size: 16px; color: black">
                            Jl. Sersan Harun No.23,<br>
                            Kartoharjo, Kecamatan Nganjuk,<br>
                            Kabupaten Nganjuk, Jawa Timur 64416
                        </p>
                    </span>
                    <span>
                        <h1 class="font-normal leading-tight" style="font-size: 24px; color: black">
                            Jam Operasional
                        </h1>
                        <p class="mt-1 leading-tight" style="font-size: 16px; color: black">
                            Senin - Sabtu: 07.30 - 17.00<br>
                            Minggu: Libur
                        </p>
                    </span>
                </section>
                <img src="/images/beranda-1.jpg" alt="Beranda" class="w-2/3 h-105 object-cover" />
            </section>
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <CartDrawer :open="isConfirmOpen" :productName="cartPreview.productName" :color="cartPreview.color"
            :size="cartPreview.size" :quantity="cartPreview.quantity" :subtotal="cartPreview.subtotal"
            :imageSrc="cartPreview.imageSrc" @close="isConfirmOpen = false" />
    </div>
</template>