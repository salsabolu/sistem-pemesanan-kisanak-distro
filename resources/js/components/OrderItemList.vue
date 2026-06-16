<script setup lang="ts">
import { PhTrash } from '@phosphor-icons/vue';
import Button from '@/components/Button.vue';
import ProductCard from '@/components/ProductCard.vue';

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

defineProps<{
    items: CartItem[];
}>();

const emit = defineEmits<{
    (e: 'update-qty', item: CartItem, newQty: number): void;
    (e: 'remove-item', id: string): void;
}>();
</script>

<template>
    <div class="col-span-2 bg-white shadow-md">
        <div class="divide-y divide-black/10">
            <div v-for="item in items" :key="item.id" class="p-6">
                <div class="flex items-start gap-4">
                    <!-- Product info via shared card -->
                    <div class="flex-1 min-w-0">
                        <ProductCard :imageSrc="item.imageSrc" :productName="item.productName" :color="item.color"
                            :size="item.size" :price="item.unitPriceText" imageSize="lg">
                            <!-- Qty control -->
                            <div class="mt-3">
                                <div class="text-black text-xs uppercase">Jumlah</div>
                                <div class="mt-2">
                                    <Button variant="quantity" :modelValue="item.quantity" :showLabel="false" :min="1"
                                        @update:modelValue="(val) => emit('update-qty', item, val)" />
                                </div>
                            </div>
                        </ProductCard>
                    </div>

                    <!-- Hapus -->
                    <button type="button" class="h-10 w-10 inline-flex items-center justify-center shrink-0"
                        @click="emit('remove-item', item.id)" aria-label="Hapus">
                        <PhTrash :size="18" class="text-red-600" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
