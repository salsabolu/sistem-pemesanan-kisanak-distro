<script setup lang="ts">
import {
    PhInfo,
} from '@phosphor-icons/vue';

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

defineProps<{
    pesananAktif: PesananAktifItem[];
}>();

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

function unitPriceFromPivot(subtotal: number, qty: number): number {
    const safeQty = Math.max(1, Math.round(qty || 0));
    return Math.round((subtotal || 0) / safeQty);
}
</script>

<template>
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
                            <div class="bg-white inline-flex p-1 w-fit">
                                <PhInfo :size="22" class="text-black" />
                            </div>
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
</template>
