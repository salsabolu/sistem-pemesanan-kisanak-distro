<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import EmptyCart from '@/components/EmptyCart.vue';
import OrderItemList from '@/components/OrderItemList.vue';
import OrderForm from '@/components/OrderForm.vue';
import OrderHistoryList from '@/components/OrderHistoryList.vue';
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

const isCheckoutConfirmOpen = ref(false);

const tenggatWaktu = ref('');
const buktiPembayaran = ref<File | null>(null);

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

const totalValue = computed(() => {
    return items.value.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0);
});

const totalText = computed(() => formatRupiah(totalValue.value));

const pesananAktif = computed(() => props.pesananAktif ?? []);

function handleCheckout(payload: { tenggatWaktu: string, buktiPembayaran: File | null }) {
    if (items.value.length === 0) {
        alert('Keranjang masih kosong');
        return;
    }

    if (!payload.tenggatWaktu) {
        alert('Tenggat waktu harus diisi');
        return;
    }

    if (!payload.buktiPembayaran) {
        alert('Bukti pembayaran harus diupload');
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
</script>

<template>

    <Head title="Keranjang" />

    <div class="bg-white min-h-screen flex flex-col">
        <PublicHeader />

        <main class="mx-auto w-full px-30 pb-16 pt-10">
            <div class="text-black text-sm font-medium uppercase">Keranjang</div>

            <!-- Empty Cart -->
            <EmptyCart v-if="items.length === 0" />

            <section v-else class="mt-5 grid grid-cols-3 gap-10">
                <OrderItemList :items="items" @update-qty="updateQty" @remove-item="removeItem" />
                <OrderForm :totalText="totalText" :distro="distro" @checkout="handleCheckout" />
            </section>

            <!-- Pesanan Section (from database: Dalam Produksi + Pembayaran Terkonfirmasi) -->
            <OrderHistoryList v-if="pesananAktif && pesananAktif.length > 0" :pesanan="pesananAktif" />
        </main>

        <Footer />

        <!-- Checkout Confirmation Modal -->
        <CheckoutConfirmationModal :open="isCheckoutConfirmOpen" @close="isCheckoutConfirmOpen = false"
            @confirm="submitOrder" />
    </div>
</template>
