<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import EmptyCart from '@/components/EmptyCart.vue';
import OrderItemList from '@/components/OrderItemList.vue';
import OrderForm from '@/components/OrderForm.vue';
import OrderHistoryList from '@/components/OrderHistoryList.vue';
import CheckoutConfirmationModal from '@/components/CheckoutConfirmationModal.vue';
import Alert from '@/components/Alert.vue';

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

// ── Inertia flash messages ────────────────────────────────────────────────
const page = usePage<any>();
const flashSuccess = computed(() => page.props.flash?.success as string | undefined);
const flashError   = computed(() => page.props.flash?.error   as string | undefined);

const alertState = ref({
    show: false,
    message: '',
    type: 'success' as 'success' | 'error' | 'info' | 'warning',
});

function showAlert(message: string, type: 'success' | 'error' | 'info' | 'warning' = 'error') {
    alertState.value = { show: true, message, type };
}

// Tampilkan alert ketika flash message datang dari server (setelah redirect)
watch(flashSuccess, (msg) => { if (msg) showAlert(msg, 'success'); }, { immediate: true });
watch(flashError,   (msg) => { if (msg) showAlert(msg, 'error');   }, { immediate: true });

// ── Cart ─────────────────────────────────────────────────────────────────
const items = ref<CartItem[]>([]);

onMounted(() => {
    loadCart();
});

function loadCart() {
    const raw = localStorage.getItem('kisanak_cart');
    if (raw) {
        try { items.value = JSON.parse(raw); }
        catch { items.value = []; }
    }
}

function saveCart() {
    localStorage.setItem('kisanak_cart', JSON.stringify(items.value));
}

function formatRupiah(value: number): string {
    const rounded = Math.max(0, Math.round(value));
    return `Rp${new Intl.NumberFormat('id-ID').format(rounded)}`;
}

function updateQty(item: CartItem, newQty: number) {
    item.quantity = newQty;
    saveCart();
}

function removeItem(id: string) {
    items.value = items.value.filter((it) => it.id !== id);
    saveCart();
}

const totalValue = computed(() =>
    items.value.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0)
);
const totalText = computed(() => formatRupiah(totalValue.value));
const pesananAktif = computed(() => props.pesananAktif ?? []);

// ── Checkout ──────────────────────────────────────────────────────────────
const isCheckoutConfirmOpen = ref(false);
const tenggatWaktu = ref('');
const buktiPembayaran = ref<File | null>(null);

function handleCheckout(payload: { tenggatWaktu: string; buktiPembayaran: File | null }) {
    if (items.value.length === 0) {
        showAlert('Keranjang masih kosong', 'error');
        return;
    }
    if (!payload.tenggatWaktu) {
        showAlert('Tenggat waktu harus diisi', 'error');
        return;
    }
    if (!payload.buktiPembayaran) {
        showAlert('Bukti pembayaran harus diupload', 'error');
        return;
    }
    tenggatWaktu.value = payload.tenggatWaktu;
    buktiPembayaran.value = payload.buktiPembayaran;
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

    // Bersihkan keranjang SEBELUM request dikirim.
    // Ini memastikan bahwa apabila komponen di-remount saat redirect,
    // onMounted → loadCart() tidak akan menemukan data lama di localStorage.
    localStorage.removeItem('kisanak_cart');
    items.value = [];
    tenggatWaktu.value = '';
    buktiPembayaran.value = null;

    router.post('/keranjang/checkout', formData, {
        // forceFormData wajib agar Inertia tidak mengonversi payload ke JSON
        // (file akan hilang jika dikirim sebagai JSON)
        forceFormData: true,
        onError: (errors) => {
            // Jika request gagal, tampilkan pesan error
            const firstError = Object.values(errors)[0] as string;
            showAlert(firstError || 'Terjadi kesalahan saat memproses pesanan.', 'error');
        },
    });
}
</script>

<template>

    <Head title="Keranjang" />

    <Alert v-model:show="alertState.show" :message="alertState.message" :type="alertState.type" />

    <div class="bg-white min-h-screen flex flex-col">
        <PublicHeader />

        <main class="mx-auto w-full px-30 pb-16 pt-10">
            <Breadcrumbs :breadcrumbs="[{ title: 'Keranjang' }]" separator="/" uppercase />

            <!-- Empty Cart -->
            <EmptyCart v-if="items.length === 0" />

            <section v-else class="mt-5 grid grid-cols-3 gap-10">
                <OrderItemList :items="items" @update-qty="updateQty" @remove-item="removeItem" />
                <OrderForm :totalText="totalText" :distro="distro" @checkout="handleCheckout" />
            </section>

            <!-- Pesanan Section (from database: Dalam Produksi + Pembayaran Terkonfirmasi) -->
            <template v-if="pesananAktif && pesananAktif.length > 0">
                <div class="mt-10 mb-4">
                    <Breadcrumbs :breadcrumbs="[{ title: 'Riwayat Pesanan' }]" separator="/" uppercase />
                </div>
                <section class="bg-white p-8 shadow-md">
                    <OrderHistoryList :pesanan="pesananAktif" />
                </section>
            </template>
        </main>

        <Footer />

        <!-- Checkout Confirmation Modal -->
        <CheckoutConfirmationModal :open="isCheckoutConfirmOpen" @close="isCheckoutConfirmOpen = false"
            @confirm="submitOrder" />
    </div>
</template>
