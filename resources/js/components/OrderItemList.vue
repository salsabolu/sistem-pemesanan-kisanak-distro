<script setup lang="ts">
import { PhTrash } from '@phosphor-icons/vue';
import Button from '@/components/Button.vue';

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
                        <div class="mt-2">
                            <Button
                                variant="quantity"
                                :modelValue="item.quantity"
                                :showLabel="false"
                                :min="1"
                                @update:modelValue="(val) => emit('update-qty', item, val)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-start justify-end">
                    <button type="button" class="h-10 w-10 inline-flex items-center justify-center"
                        @click="emit('remove-item', item.id)" aria-label="Hapus">
                        <PhTrash :size="18" class="text-red-600" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
