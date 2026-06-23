<script setup lang="ts">
import { computed, ref } from 'vue';
import NavIcon from '@/components/NavIcon.vue';
import ProductCard from '@/components/ProductCard.vue';
import DesignPreviewModal from '@/components/DesignPreviewModal.vue';
import Button from '@/components/Button.vue';
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

// Format lengkap dengan jam untuk estimasi_selesai
function formatDateTime(value: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    if (isNaN(d.getTime())) return '-';
    const dd   = String(d.getDate()).padStart(2, '0');
    const mm   = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh   = String(d.getHours()).padStart(2, '0');
    const min  = String(d.getMinutes()).padStart(2, '0');
    return `${dd}-${mm}-${yyyy}, ${hh}:${min}`;
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
        const date = formatDate(p.created_at || new Date().toISOString());
        if (!groups[date]) groups[date] = [];

        const products = (p.produk ?? []).map((pr: any, idx: number) => {
            const qty = (pr?.pivot?.jumlah ?? 0) as number;
            const subtotal = (pr?.pivot?.subtotal ?? 0) as number;
            const unitPrice = subtotal / Math.max(1, qty);

            return {
                key: `${pr?.id ?? 'p'}-${idx}`,
                imageSrc: pr?.gambar && pr.gambar !== '-' ? pr.gambar : '/images/kaos-1.png',
                imageAlt: pr?.nama ?? '-',
                productName: pr?.nama ?? '-',
                produkId: pr?.id ?? 0,
                color: pr?.warna?.nama ?? '-',
                size: pr?.ukuran?.nama ?? '-',
                quantity: qty,
                price: formatRupiah(unitPrice),
                subtotal: formatRupiah(subtotal),
            };
        });

        groups[date].push({
            id: p.id,
            products,
            totalText: formatRupiah(p.total ?? 0),
            status: p.status,
            deadlineText: formatDate(p.tenggat_waktu ?? null),
            estimatedFinish: formatDateTime(p.estimasi_selesai ?? null),
            detailPesanan: p.detail_pesanan ?? [],
        });
    }
    return Object.keys(groups).map(date => ({
        date,
        items: groups[date],
    }));
});

// ── Design Preview ──
const isDesignPreviewOpen = ref(false);
const previewDesainJson = ref<string | null>(null);
const previewProductName = ref('');
const previewTeksList = ref<Array<{ id: number; teks: string }>>([]);
const previewGambarList = ref<Array<{ id: number; file: string }>>([]);

function openDesignPreview(produkId: number, productName: string, detailPesananList: any[]) {
    const detail = detailPesananList.find((dp: any) => dp.id_produk === produkId);
    if (detail?.desain?.desain_json) {
        previewDesainJson.value = detail.desain.desain_json;
        previewProductName.value = productName;
        previewTeksList.value = detail.desain.teks ?? [];
        previewGambarList.value = detail.desain.gambar ?? [];
        isDesignPreviewOpen.value = true;
    }
}

function hasDesain(produkId: number, detailPesananList: any[]): boolean {
    const detail = detailPesananList.find((dp: any) => dp.id_produk === produkId);
    return !!(detail?.desain?.desain_json);
}
</script>

<template>
    <div>
        <div v-if="ordersGrouped.length === 0" class="text-black/50 text-sm">Belum ada pesanan.</div>
        <div v-for="group in ordersGrouped" :key="group.date" class="mb-8 mt-5">
            <div class="text-black text-lg font-medium">{{ group.date }}</div>

            <div v-for="item in group.items" :key="item.id" class="mt-4 border-b border-black/10 pb-6">
                <div class="grid grid-cols-[1fr_auto] gap-6">
                    <div class="min-w-0">
                        <!-- Daftar produk dalam pesanan ini -->
                        <div class="grid gap-6">
                            <ProductCard v-for="prod in item.products" :key="prod.key" :imageSrc="prod.imageSrc"
                                :imageAlt="prod.imageAlt" :productName="prod.productName" :color="prod.color"
                                :size="prod.size" :price="prod.price" :quantity="prod.quantity"
                                :subtotal="prod.subtotal" :showQuantity="true" :showSubtotal="true" imageSize="lg">
                                <Button v-if="hasDesain(prod.produkId, item.detailPesanan)"
                                    variant="solid"
                                    class="mt-2 w-fit px-3 py-1.5 text-[10px] uppercase font-bold"
                                    @click="openDesignPreview(prod.produkId, prod.productName, item.detailPesanan)">
                                    Lihat Hasil Kustom Desain
                                </Button>
                            </ProductCard>
                        </div>

                        <!-- Total & tenggat -->
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
                                    <div class="bg-black px-3 py-3 text-xs uppercase text-white">
                                        Status:
                                    </div>
                                    <div class="bg-black px-3 py-3 text-xs uppercase text-white text-right">
                                        {{ item.status }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="bg-black px-3 py-3 text-xs uppercase text-white">
                                        Estimasi Selesai:
                                    </div>
                                    <div class="bg-black px-3 py-3 text-xs uppercase text-white text-right">
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

    <DesignPreviewModal :open="isDesignPreviewOpen" :desain-json="previewDesainJson"
        :product-name="previewProductName" :teks-list="previewTeksList" :gambar-list="previewGambarList"
        @close="isDesignPreviewOpen = false" />
</template>
