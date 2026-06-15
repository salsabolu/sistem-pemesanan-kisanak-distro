<script setup lang="ts">
import { computed } from 'vue';
import NavIcon from '@/components/NavIcon.vue';
import { PhInfo } from '@phosphor-icons/vue';

const props = defineProps<{
    pesanan?: any; // Can be Paginated pesanan or array of pesanan
}>();

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
    let data = [];
    if (Array.isArray(props.pesanan)) {
        data = props.pesanan;
    } else if (props.pesanan?.data) {
        data = props.pesanan.data;
    }
    
    if (data.length === 0) return [];
    
    const groups: Record<string, any[]> = {};
    for (const p of data) {
        // Handle case where created_at is missing (like active orders)
        const date = formatDate(p.created_at || new Date().toISOString());
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
</script>

<template>
    <div>
        <div v-if="ordersGrouped.length === 0" class="text-black/50 text-sm">Belum ada pesanan.</div>
        <div v-for="group in ordersGrouped" :key="group.date" class="mb-8 mt-5">
            <div class="text-black text-sm font-bold">{{ group.date }}</div>

            <div v-for="item in group.items" :key="item.id" class="mt-4 border-b border-black/10 pb-6">
                <div class="grid grid-cols-[1fr_auto] gap-6">
                    <div class="min-w-0">
                        <div class="grid gap-4">
                            <div v-for="prod in item.products" :key="prod.key"
                                class="grid grid-cols-[160px_1fr] gap-6">
                                <div class="aspect-square w-40 overflow-hidden border border-black/10">
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
                                    <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">
                                        Status:
                                    </div>
                                    <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">
                                        {{ item.status }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black">
                                        Estimasi Selesai:
                                    </div>
                                    <div class="bg-yellow px-3 py-3 text-xs font-medium uppercase text-black text-right">
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
</template>
