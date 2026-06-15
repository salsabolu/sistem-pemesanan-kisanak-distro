<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const isRiwayatPesanan = computed(() => page.url.startsWith('/profil/riwayat-pesanan'));
const isEditProfil = computed(() => page.url === '/profil/edit');
const isUbahKataSandi = computed(() => page.url === '/profil/ubah-kata-sandi');
</script>

<template>
    <aside class="w-56 shrink-0">
        <div class="text-black text-sm font-bold uppercase">Profil</div>
        <nav class="mt-4 flex flex-col gap-3">
            <Link href="/profil/riwayat-pesanan" class="text-black text-sm uppercase hover:underline" :class="{ 'font-medium': isRiwayatPesanan }">
                Riwayat Pesanan
            </Link>
            <Link href="/profil/edit" class="text-black text-sm uppercase hover:underline" :class="{ 'font-medium': isEditProfil }">
                Edit Profil
            </Link>
            <Link href="/profil/ubah-kata-sandi" class="text-black text-sm uppercase hover:underline" :class="{ 'font-medium': isUbahKataSandi }">
                Ubah Kata Sandi
            </Link>
        </nav>
        <div class="mt-auto pt-60">
            <Link href="/logout" method="post" as="button" type="button" class="text-red-500 text-sm uppercase hover:underline">Keluar</Link>
        </div>
    </aside>
</template>
