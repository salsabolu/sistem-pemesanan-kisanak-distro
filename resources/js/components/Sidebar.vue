<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url ?? '');

const role = computed(() => {
    const roles = (page.props as any)?.auth?.roles as string[] | undefined;
    if (!roles || roles.length === 0) return '-';
    return roles.join(', ');
});
</script>

<template>
    <aside class="w-56 shrink-0 border-r border-black/10 flex flex-col min-h-screen px-5 py-6">
        <div class="mb-4">
            <Link href="/dasbor">
                <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="h-10 w-auto" />
            </Link>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-black/10 flex items-center justify-center">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 20c0-4 4-7 8-7s8 3 8 7" />
                </svg>
            </div>
            <div class="text-black text-sm font-medium">{{ role }}</div>
        </div>

        <nav class="flex flex-col gap-1 text-sm flex-1">
            <div class="text-black/50 text-xs uppercase mb-1">Aksi Cepat</div>
            <Link href="/dasbor" class="text-black py-1 hover:underline"
                :class="{ 'font-medium': currentUrl.startsWith('/dasbor') }">Dasbor</Link>
            <Link href="/produksi/pesanan-baru" class="text-black py-1 hover:underline"
                :class="{ 'font-medium': currentUrl.startsWith('/produksi/pesanan-baru') }">Pesanan Baru</Link>
            <Link href="/produksi/konfirmasi-pembayaran" class="text-black py-1 hover:underline"
                :class="{ 'font-medium': currentUrl.startsWith('/produksi/konfirmasi-pembayaran') }">Konfirmasi
                Pembayaran
            </Link>
            <Link href="/produksi/antrean-produksi" class="text-black py-1 hover:underline"
                :class="{ 'font-medium': currentUrl.startsWith('/produksi/antrean-produksi') }">Antrean Produksi</Link>
            <Link href="/produksi/stok-menipis" class="text-black py-1 hover:underline"
                :class="{ 'font-medium': currentUrl.startsWith('/produksi/stok-menipis') }">Stok Menipis</Link>

            <!-- <div class="text-black/50 text-xs uppercase mt-3 mb-1">Pengguna</div> -->
            <!-- <Link href="#" class="text-black py-1 hover:underline">Pengguna</Link> -->

            <div class="text-black/50 text-xs uppercase mt-3 mb-1">Pesanan</div>
            <Link href="/pesanan/daftar-pesanan" class="text-black py-1 hover:underline pl-3"
                :class="{ 'font-medium': currentUrl.startsWith('/pesanan/daftar-pesanan') }">Daftar Pesanan</Link>

            <div class="text-black/50 text-xs uppercase mt-3 mb-1">Master</div>
            <Link href="/master/kategori" class="text-black py-1 hover:underline pl-3"
                :class="{ 'font-medium': currentUrl.startsWith('/master/kategori') }">Kategori</Link>
            <Link href="/master/warna" class="text-black py-1 hover:underline pl-3"
                :class="{ 'font-medium': currentUrl.startsWith('/master/warna') }">Warna</Link>
            <Link href="/master/ukuran" class="text-black py-1 hover:underline pl-3"
                :class="{ 'font-medium': currentUrl.startsWith('/master/ukuran') }">Ukuran</Link>
            <Link href="/master/produk" class="text-black py-1 hover:underline pl-3"
                :class="{ 'font-medium': currentUrl.startsWith('/master/produk') }">Produk</Link>

            <!-- <div class="text-black/50 text-xs uppercase mt-3 mb-1">Laporan</div> -->
            <!-- <Link href="#" class="text-black py-1 hover:underline pl-3">Laporan Keuangan</Link> -->
            <!-- <Link href="#" class="text-black py-1 hover:underline pl-3">Laporan Bahan Baku</Link> -->
        </nav>

        <div class="mt-auto pt-6">
            <Link href="/logout" method="post" as="button"
                class="text-red-500 text-sm hover:underline">
                Keluar
            </Link>
            <!-- <button type="button" class="text-red-500 text-sm uppercase hover:underline">Keluar</button> -->
        </div>
    </aside>
</template>
