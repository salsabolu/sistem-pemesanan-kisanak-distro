<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Button from '@/components/Button.vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';

const isConfirmOpen = ref(false);
</script>

<template>

    <Head title="Beranda" />

    <div class="bg-white relative min-h-screen flex flex-col">
        <!-- Header -->
        <PublicHeader showNav activeNav="beranda" @cart-click="isConfirmOpen = true" />

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full px-30 pb-16 pt-6">
            <!-- 1 Main Picture -->
            <section class="mb-4">
                <img src="/images/beranda-1.jpg" alt="Beranda" class="w-full object-cover border border-black" style="height: 460px" />
            </section>

            <!-- 2 Pictures + Tagline + CTA Button -->
            <section class="mb-4 flex gap-4">
                <div class="w-1/3 h-105 overflow-hidden">
                    <img src="/images/beranda-2.jpg" alt="Gambar 2" class="h-full w-full object-cover border border-black" />
                </div>
                <div class="w-1/3 h-105 overflow-hidden">
                    <img src="/images/beranda-3.jpg" alt="Gambar 3" class="h-full w-full object-cover border border-black" />
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
                            <Button>Belanja Sekarang</Button>
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
                <img src="/images/beranda-4.png" alt="Beranda" class="w-2/3 h-105 object-cover border border-black" />
            </section>
        </main>

        <Footer />

        <CartDrawer :open="isConfirmOpen" @close="isConfirmOpen = false" />
    </div>
</template>