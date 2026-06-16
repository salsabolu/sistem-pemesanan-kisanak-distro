<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Button from '@/components/Button.vue';

type CartItem = {
    id: string;
    productId: number;
    imageSrc: string;
    productName: string;
    color: string;
    size: string;
    unitPrice: number;
    unitPriceText: string;
    quantity: number;
};

interface Props {
    open: boolean;
    productId?: number;
    productName?: string;
    color?: string;
    size?: string;
    quantity?: number;
    subtotal?: string;
    unitPrice?: number;
    imageSrc?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'close'): void }>();

const cartItems = ref<CartItem[]>([]);

watch(
    () => props.open,
    (open) => {
        if (open) {
            loadCart();
        }
    }
);

function loadCart() {
    const raw = localStorage.getItem('kisanak_cart');
    if (raw) {
        try {
            cartItems.value = JSON.parse(raw);
        } catch {
            cartItems.value = [];
        }
    } else {
        cartItems.value = [];
    }
}

function saveCart() {
    localStorage.setItem('kisanak_cart', JSON.stringify(cartItems.value));
}

function formatRupiah(value: number): string {
    const rounded = Math.max(0, Math.round(value));
    return `Rp${new Intl.NumberFormat('id-ID').format(rounded)}`;
}

function handleQtyChange(item: CartItem, newQty: number) {
    item.quantity = newQty;
    if (item.quantity <= 0) {
        cartItems.value = cartItems.value.filter(i => i.id !== item.id);
    }
    saveCart();
}

const totalText = computed(() => {
    const total = cartItems.value.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0);
    return formatRupiah(total);
});

function goToCart() {
    emit('close');
    router.get('/keranjang');
}
</script>

<template>
    <div v-if="props.open" class="fixed inset-0 z-50 flex justify-end" @click="emit('close')">
        <div class="absolute inset-0 bg-black/40" />

        <div class="bg-white text-black relative h-full w-full border-l border-black px-8 py-7 flex flex-col md:w-2/5 md:max-w-none"
            @click.stop>
            <h2 class="text-lg font-medium uppercase flex-none">Keranjang</h2>

            <div class="mt-6 flex-1 overflow-y-auto">
                <!-- Empty cart -->
                <div v-if="cartItems.length === 0" class="text-sm text-black/50">
                    Keranjang masih kosong.
                </div>

                <!-- Cart items -->
                <div v-for="item in cartItems" :key="item.id" class="grid grid-cols-2 w-full gap-2 mb-6">
                    <div class="aspect-square w-40 h-40 overflow-hidden border border-black">
                        <img :src="item.imageSrc" :alt="item.productName" class="h-full w-full object-cover" />
                    </div>

                    <div class="grid grid-rows-1 w-full h-full min-w-0">
                        <div class="text-sm font-medium uppercase leading-snug">
                            {{ item.productName }}
                        </div>
                        <div class="mt-0 text-xs uppercase">
                            {{ item.color.toUpperCase() }} / {{ item.size.toUpperCase() }}
                        </div>
                        <div class="mt-1 text-sm">{{ formatRupiah(item.unitPrice * item.quantity) }}</div>

                        <div class="mt-4">
                            <div class="text-xs uppercase">Jumlah</div>
                            <div class="mt-2">
                                <Button variant="quantity" :modelValue="item.quantity" :showLabel="false" :min="0"
                                    @update:modelValue="(val) => handleQtyChange(item, val)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-none pt-6">
                <div class="text-[10px] italic">*Pajak sudah termasuk</div>

                <div class="mt-4 flex items-center justify-between text-sm">
                    <div class="uppercase">Total Harga:</div>
                    <div>{{ totalText }}</div>
                </div>

                <Button class="mt-6 w-full text-sm py-2" :disabled="cartItems.length === 0" @click="goToCart">
                    Checkout
                </Button>
            </div>
        </div>
    </div>
</template>
