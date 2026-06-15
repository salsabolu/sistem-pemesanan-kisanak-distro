<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
    PhTrash,
    PhInfo,
    PhCopy,
} from '@phosphor-icons/vue';
import { computed, ref, onMounted } from 'vue';
import Footer from '@/components/Footer.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';
import EmptyCart from '@/components/EmptyCart.vue';
import PesananSection from '@/components/PesananSection.vue';
import CheckoutConfirmationModal from '@/components/CheckoutConfirmationModal.vue';

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

type DistroType = {
    rekening_bca: string;
    rekening_bri: string;
};

const props = defineProps<{
    pesananAktif?: PesananAktifItem[];
    distro?: DistroType;
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
const buktiPembayaran = ref<File | null>(null);

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        buktiPembayaran.value = target.files[0];
    } else {
        buktiPembayaran.value = null;
    }
}

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

    if (!buktiPembayaran.value) {
        alert('Bukti pembayaran harus diupload');
        return;
    }

    isCheckoutConfirmOpen.value = true;
}

function submitOrder() {
    isCheckoutConfirmOpen.value = false;

    const formData = new FormData();
    formData.append('tenggat_waktu', tenggatWaktu.value);
    if (buktiPembayaran.value) {
        formData.append('bukti_pembayaran', buktiPembayaran.value);
    }
    
    items.value.forEach((it, index) => {
        formData.append(`items[${index}][productId]`, String(it.productId));
        if (it.color) formData.append(`items[${index}][color]`, it.color);
        formData.append(`items[${index}][size]`, it.size);
        formData.append(`items[${index}][quantity]`, String(it.quantity));
        formData.append(`items[${index}][unitPrice]`, String(it.unitPrice));
    });

    router.post('/keranjang/checkout', formData, {
        onSuccess: () => {
            localStorage.removeItem('kisanak_cart');
            items.value = [];
            tenggatWaktu.value = '';
            buktiPembayaran.value = null;
            alert('Pesanan berhasil dibuat!');
        },
    });
}

function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Nomor rekening berhasil disalin!');
    }).catch(err => {
        console.error('Gagal menyalin text: ', err);
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
            <EmptyCart v-if="items.length === 0" />

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
                    
                    <div class="mt-4">
                        <div class="text-black text-xs uppercase">Bukti Pembayaran</div>
                        <input type="file" accept="image/png, image/jpeg, image/jpg, application/pdf" @change="handleFileChange"
                            class="mt-2 w-full border border-black px-3 py-2 text-sm text-black file:mr-4 file:py-1 file:px-2 file:border-0 file:text-xs file:bg-black file:text-white" />
                    </div>

                    <div v-if="distro" class="mt-4 p-3 bg-black/5 text-xs text-black border border-black/10">
                        <div class="font-medium uppercase mb-1">Transfer ke Rekening:</div>
                        <div class="flex items-center justify-between py-1 border-b border-black/5 last:border-0">
                            <div>BCA: {{ distro.rekening_bca }}</div>
                            <button type="button" @click="copyToClipboard(distro.rekening_bca)" class="text-black hover:opacity-75 focus:outline-none" title="Salin nomor rekening BCA">
                                <PhCopy :size="16" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <div>BRI: {{ distro.rekening_bri }}</div>
                            <button type="button" @click="copyToClipboard(distro.rekening_bri)" class="text-black hover:opacity-75 focus:outline-none" title="Salin nomor rekening BRI">
                                <PhCopy :size="16" />
                            </button>
                        </div>
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
            <PesananSection v-if="pesananAktif && pesananAktif.length > 0" :pesananAktif="pesananAktif" />
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <!-- Checkout Confirmation Modal -->
        <CheckoutConfirmationModal :open="isCheckoutConfirmOpen" @close="isCheckoutConfirmOpen = false" @confirm="submitOrder" />
    </div>
</template>
