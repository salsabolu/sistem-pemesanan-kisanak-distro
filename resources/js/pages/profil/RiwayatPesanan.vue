<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
    PhInfo,
} from '@phosphor-icons/vue';
import { ref, computed } from 'vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Footer from '@/components/Footer.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';

const props = defineProps<{
    pesanan?: any; // Paginated pesanan
}>();

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

function formatRupiah(value: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
}

function formatDate(value: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}-${mm}-${d.getFullYear()}`;
}

const ordersGrouped = computed(() => {
    if (!props.pesanan?.data) return [];
    const groups: Record<string, any[]> = {};
    for (const p of props.pesanan.data) {
        const date = formatDate(p.created_at);
        if (!groups[date]) groups[date] = [];

        const products = (p.produk ?? []).map((pr: any, idx: number) => {
            const qty = (pr?.pivot?.jumlah ?? 0) as number;
            const subtotal = (pr?.pivot?.subtotal ?? 0) as number;

            const imageSrc = pr?.gambar && pr.gambar !== '-' ? pr.gambar : '/images/kaos-1.png';
            const imageAlt = pr?.nama ?? '-';

            return {
                key: `${pr?.id ?? 'p'}-${idx}`,
                imageSrc,
                imageAlt,
                productName: pr?.nama ?? '-',
                color: pr?.warna?.nama ?? '-',
                size: pr?.ukuran?.nama ?? '-',
                quantity: qty,
                unitPriceText: formatRupiah(subtotal / Math.max(1, qty)),
                subtotalText: formatRupiah(subtotal),
            };
        });

        groups[date].push({
            id: p.id,
            products,
            totalText: formatRupiah(p.total ?? 0),
            status: p.status,
            deadlineText: formatDate(p.tenggat_waktu ?? null),
            estimatedFinish: formatDate(p.estimasi_selesai ?? null),
        });
    }
    return Object.keys(groups).map(date => ({
        date,
        items: groups[date],
    }));
});

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

    <Head title="Riwayat Pesanan" />

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
                                <Link href="/logout" method="post" as="button"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full px-30 pb-16 pt-10">
            <div class="flex gap-10">
                <!-- Sidebar -->
                <aside class="w-56 shrink-0">
                    <div class="text-black text-sm font-bold uppercase">Profil</div>
                    <nav class="mt-4 flex flex-col gap-3">
                        <Link href="/profil/riwayat-pesanan"
                            class="text-black text-sm uppercase hover:underline font-medium">
                            Riwayat Pesanan
                        </Link>
                        <Link href="/profil/edit" class="text-black text-sm uppercase hover:underline">
                            Edit Profil
                        </Link>
                        <Link href="/profil/ubah-kata-sandi" class="text-black text-sm uppercase hover:underline">
                            Ubah Kata Sandi
                        </Link>
                    </nav>
                    <div class="mt-auto pt-60">
                        <Link href="/logout" method="post" as="button" type="button"
                            class="text-red-500 text-sm uppercase hover:underline">Keluar</Link>
                    </div>
                </aside>

                <!-- Content -->
                <div class="flex-1">
                    <div v-if="ordersGrouped.length === 0" class="text-black/50 text-sm">Belum ada riwayat pesanan.
                    </div>
                    <div v-for="group in ordersGrouped" :key="group.date" class="mb-8">
                        <div class="text-black text-sm font-bold">{{ group.date }}</div>

                        <div v-for="item in group.items" :key="item.id" class="mt-4 border-b border-black/10 pb-6">
                            <div class="grid grid-cols-[1fr_auto] gap-6">
                                <div class="min-w-0">
                                    <div class="grid gap-4">
                                        <div v-for="prod in item.products" :key="prod.key"
                                            class="grid grid-cols-[160px_1fr] gap-6">
                                            <div class="aspect-square w-40 overflow-hidden">
                                                <img :src="prod.imageSrc" :alt="prod.imageAlt"
                                                    class="h-full w-full object-cover" />
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-black text-sm font-medium uppercase leading-snug">
                                                    {{ prod.productName }}
                                                </div>
                                                <div class="mt-1 text-black text-xs uppercase">
                                                    {{ String(prod.color).toUpperCase() }} / {{ String(prod.size).toUpperCase() }}
                                                </div>
                                                <div class="mt-1 text-black text-xs">
                                                    {{ prod.quantity }} / {{ prod.unitPriceText }}
                                                </div>
                                                <div class="mt-1 text-black text-xs">
                                                    {{ prod.subtotalText }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-black/10">
                                        <div class="text-black text-xs">{{ item.totalText }}</div>
                                        <div class="mt-2 text-black/60 text-xs uppercase">
                                            Tenggat Waktu: {{ item.deadlineText }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Info -->
                                <div class="flex items-start justify-end">
                                    <div class="w-72">
                                        <div class="grid gap-1">
                                            <NavIcon class="bg-white" :icon="PhInfo" ariaLabel="Informasi" :size="22" />
                                            <div class="grid grid-cols-2">
                                                <div
                                                    class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">
                                                    Status:
                                                </div>
                                                <div
                                                    class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">
                                                    {{ item.status }}
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2">
                                                <div
                                                    class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">
                                                    Estimasi Selesai:
                                                </div>
                                                <div
                                                    class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">
                                                    {{ item.estimatedFinish }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <CartDrawer :open="isConfirmOpen" :productName="cartPreview.productName" :color="cartPreview.color"
            :size="cartPreview.size" :quantity="cartPreview.quantity" :subtotal="cartPreview.subtotal"
            :imageSrc="cartPreview.imageSrc" @close="isConfirmOpen = false" />
    </div>
</template>
