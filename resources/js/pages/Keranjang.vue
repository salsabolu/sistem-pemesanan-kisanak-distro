<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
    PhTrash,
    PhInfo,
} from '@phosphor-icons/vue';
import { computed, ref, onMounted } from 'vue';
import Footer from '@/components/Footer.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';

type CartItem = {
    id: string;
    productId: number;
    imageSrc: string;
    productName: string;
    color: string | null;
    size: string;
    unitPrice: number;
    unitPriceText: string;
    quantity: number;
};

type PesananAktifItem = {
    id: number;
    total: number;
    status: string;
    tenggat_waktu?: string | null;
    estimasi_selesai?: string | null;
    produk?: Array<{
        id: number; nama: string; harga: number; gambar: string | null;
        warna?: { nama: string } | null; ukuran?: { nama: string };
        pivot?: { jumlah: number; subtotal: number };
    }>;
};

const props = defineProps<{
    pesananAktif?: PesananAktifItem[];
}>();

const items = ref<CartItem[]>([]);

onMounted(() => {
    loadCart();
});

function loadCart() {
    const raw = localStorage.getItem('kisanak_cart');
    if (raw) {
        try {
            items.value = JSON.parse(raw);
        } catch {
            items.value = [];
        }
    }
}

function saveCart() {
    localStorage.setItem('kisanak_cart', JSON.stringify(items.value));
}

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isCheckoutConfirmOpen = ref(false);
const tenggatWaktu = ref<string>('');

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

function closeLogin() { isLoginOpen.value = false; }
function closeRegister() { isRegisterOpen.value = false; }

function formatRupiah(value: number): string {
    const rounded = Math.max(0, Math.round(value));
    return `Rp${new Intl.NumberFormat('id-ID').format(rounded)}`;
}

function formatDate(value: string | null | undefined): string {
    if (!value) return '-';
    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value);
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}-${mm}-${d.getFullYear()}`;
}

function decQty(item: CartItem) {
    item.quantity = Math.max(1, item.quantity - 1);
    saveCart();
}

function incQty(item: CartItem) {
    item.quantity += 1;
    saveCart();
}

function removeItem(id: string) {
    items.value = items.value.filter((it) => it.id !== id);
    saveCart();
}

const totalValue = computed(() => {
    return items.value.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0);
});

const totalText = computed(() => formatRupiah(totalValue.value));

function unitPriceFromPivot(subtotal: number, qty: number): number {
    const safeQty = Math.max(1, Math.round(qty || 0));
    return Math.round((subtotal || 0) / safeQty);
}

const pesananAktif = computed(() => props.pesananAktif ?? []);

function handleCheckoutClick() {
    if (items.value.length === 0) {
        alert('Keranjang masih kosong');
        return;
    }

    if (!tenggatWaktu.value) {
        alert('Tenggat waktu harus diisi');
        return;
    }

    isCheckoutConfirmOpen.value = true;
}

function submitOrder() {
    isCheckoutConfirmOpen.value = false;

    const payload = {
        tenggat_waktu: tenggatWaktu.value,
        items: items.value.map(it => ({
            productId: it.productId,
            color: it.color,
            size: it.size,
            quantity: it.quantity,
            unitPrice: it.unitPrice,
        })),
    };

    router.post('/keranjang/checkout', payload, {
        onSuccess: () => {
            localStorage.removeItem('kisanak_cart');
            items.value = [];
            tenggatWaktu.value = '';
            alert('Pesanan berhasil dibuat!');
        },
    });
}
</script>

<template>

    <Head title="Keranjang" />

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
                    <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" />
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

        <main class="mx-auto w-full px-30 pb-16 pt-10">
            <div class="text-black text-sm font-medium uppercase">Keranjang</div>

            <!-- Empty Cart -->
            <div v-if="items.length === 0" class="mt-10 text-center text-black/50 text-sm">
                Keranjang masih kosong. <Link href="/katalog" class="underline text-black">Belanja sekarang</Link>
            </div>

            <section v-else class="mt-5 grid grid-cols-3 gap-10">
                <div class="col-span-2 bg-white" style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08)">
                    <div class="divide-y divide-white-hover">
                        <div v-for="item in items" :key="item.id" class="grid grid-cols-3 gap-1 p-6">
                            <div class="aspect-square w-40 overflow-hidden border border-black">
                                <img :src="item.imageSrc" :alt="item.productName" class="h-full w-full object-cover" />
                            </div>

                            <div class="min-w-0">
                                <div class="text-black text-sm font-medium uppercase leading-snug">
                                    {{ item.productName }}
                                </div>
                                <div class="mt-2 text-black text-xs uppercase">
                                    {{ item.color ? item.color.toUpperCase() : 'TIDAK ADA' }} / {{ item.size ? item.size.toUpperCase() : 'TIDAK ADA' }}
                                </div>
                                <div class="mt-2 text-black text-sm">{{ item.unitPriceText }}</div>

                                <div class="mt-3">
                                    <div class="text-black text-xs uppercase">Jumlah</div>
                                    <div class="mt-2 inline-flex items-center" :style="{ border: '1px solid black' }">
                                        <button type="button" class="text-black h-8 w-8"
                                            @click="decQty(item)">-</button>
                                        <div class="h-8 w-10 text-black text-center text-sm leading-8"
                                            :style="{ borderLeft: '1px solid black', borderRight: '1px solid black' }">
                                            {{ item.quantity }}
                                        </div>
                                        <button type="button" class="text-black h-8 w-8"
                                            @click="incQty(item)">+</button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start justify-end">
                                <button type="button" class="h-10 w-10 inline-flex items-center justify-center"
                                    @click="removeItem(item.id)" aria-label="Hapus">
                                    <PhTrash :size="18" class="text-red-600" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 flex flex-col" style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08)">
                    <div class="mt-4">
                        <div class="text-black text-xs uppercase">Tenggat Waktu</div>
                        <input v-model="tenggatWaktu" type="date"
                            class="mt-2 w-full border border-black px-3 py-2 text-sm text-black" />
                    </div>

                    <div class="mt-4 text-black text-[10px] italic">*Pajak sudah termasuk</div>

                    <div class="text-black mt-2 flex items-center justify-between text-sm">
                        <div class="uppercase">Total Harga:</div>
                        <div>{{ totalText }}</div>
                    </div>

                    <button type="button" class="mt-auto text-black w-full border border-black px-4 py-2 text-sm"
                        @click="handleCheckoutClick">
                        Checkout
                    </button>
                </div>
            </section>

            <!-- Pesanan Section (from database: Dalam Produksi + Pembayaran Terkonfirmasi) -->
            <section v-if="pesananAktif && pesananAktif.length > 0" class="mt-10">
                <div class="mt-5 bg-black text-white p-6" style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12)">
                    <div class="text-white text-sm font-normal uppercase">Pesanan</div>
                    <div v-for="order in pesananAktif" :key="order.id" class="mt-4 grid grid-cols-[1fr_auto] gap-10">
                        <div class="min-w-0">
                            <div class="grid gap-4">
                                <div v-for="prod in (order.produk ?? [])" :key="prod.id"
                                    class="grid grid-cols-[160px_1fr] gap-6">
                                    <div class="aspect-square w-40 overflow-hidden">
                                        <img :src="(prod.gambar && prod.gambar !== '-') ? prod.gambar : '/images/kaos-1.png'"
                                            :alt="prod.nama ?? 'Produk'" class="h-full w-full object-cover" />
                                    </div>

                                    <div class="min-w-0">
                                        <div class="text-sm font-medium uppercase leading-snug">{{ prod.nama ?? '-' }}</div>
                                        <div class="mt-1 text-xs uppercase">
                                            {{ (prod.warna?.nama ?? '-').toUpperCase() }} / {{ (prod.ukuran?.nama ?? '-').toUpperCase() }}
                                        </div>
                                        <div class="mt-1 text-xs">
                                            {{ prod.pivot?.jumlah ?? 0 }} /
                                            {{ formatRupiah(unitPriceFromPivot(prod.pivot?.subtotal ?? 0, prod.pivot?.jumlah ?? 0)) }}
                                        </div>
                                        <div class="mt-1 text-xs">{{ formatRupiah(prod.pivot?.subtotal ?? 0) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-white/20">
                                <div class="text-xs">{{ formatRupiah(order.total) }}</div>
                                <div class="mt-2 text-white/70 text-xs uppercase">
                                    Tenggat Waktu: {{ formatDate(order.tenggat_waktu) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start justify-end">
                            <div class="w-72">
                                <div class="grid gap-1">
                                    <NavIcon class="bg-white" :icon="PhInfo" ariaLabel="Informasi" :size="22" />
                                    <div class="grid grid-cols-2">
                                        <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">Status:</div>
                                        <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">{{ order.status.toUpperCase() }}</div>
                                    </div>
                                    <div class="grid grid-cols-2">
                                        <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">Estimasi Selesai:</div>
                                        <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">{{ formatDate(order.estimasi_selesai) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <!-- Checkout Confirmation Modal -->
        <div v-if="isCheckoutConfirmOpen" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="isCheckoutConfirmOpen = false" />
            <div class="relative bg-white text-black p-6 w-full max-w-sm mx-4" style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15)">
                <h3 class="text-lg font-medium uppercase">Konfirmasi Pesanan</h3>
                <p class="mt-1 text-sm">Apakah data pesanan sudah sesuai?</p>
                <div class="mt-4 flex gap-4 justify-end">
                    <button type="button" class="px-6 py-2 text-sm border border-black bg-white text-black hover:bg-black/5"
                        @click="isCheckoutConfirmOpen = false">
                        Batal
                    </button>
                    <button type="button" class="px-6 py-2 text-sm bg-black text-white hover:bg-black/90"
                        @click="submitOrder">
                        Pesan Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
