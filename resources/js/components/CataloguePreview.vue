<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import CtaButton from '@/components/CtaButton.vue';

type ProductStatus = 'Terbaru' | 'Stok Menipis' | 'Stok Habis';

interface CatalogueItem {
    id: number;
    status: ProductStatus;
    imageSrc: string;
    name: string;
    price: string;
}

defineProps<{ items: CatalogueItem[] }>();

function goToDetail(id: number) {
    router.visit(`/katalog/produk/${id}`);
}
</script>

<template>
    <section class="w-full h-full">
        <div class="grid grid-cols-3 gap-4">
            <article v-for="(item, idx) in items" :key="idx" class="flex h-[450px] w-full flex-col">
                <div class="relative overflow-hidden">
                    <div class="bg-black text-white absolute left-2 top-2 z-10 px-2 py-1 text-xs">
                        {{ item.status }}
                    </div>

                    <div class="aspect-square w-full overflow-hidden">
                        <img :src="item.imageSrc" :alt="item.name" class="h-full w-full object-cover border border-black" />
                    </div>
                </div>

                <h3 class="text-black whitespace-pre-line text-12px font-medium uppercase leading-tight mt-3">
                    {{ item.name }}
                </h3>

                <p class="text-black text-12px font-normal mt-1">{{ item.price }}</p>

                <div class="mt-auto">
                    <CtaButton class="w-full" @click="goToDetail(item.id)">Beli</CtaButton>
                </div>
            </article>
        </div>
    </section>
</template>
