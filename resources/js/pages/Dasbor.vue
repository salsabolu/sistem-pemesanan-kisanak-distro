<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import {
    PhCheckSquare,
    PhClipboard,
    PhCube,
    PhList,
} from '@phosphor-icons/vue';
import Sidebar from '@/components/Sidebar.vue';
import InfoCard from '@/components/InfoCard.vue';
import { Bold } from 'lucide-vue-next';

type TopBuyer = {
    id: number;
    nama: string;
    whatsapp: string | null;
    total_pesanan: number | string;
};

const props = defineProps<{
    pesananBaruCount: number;
    konfirmasiPembayaranCount: number;
    antreanProduksiCount: number;
    stokMenipisCount: number;
    chartData: number[];
    pembeliTeratas: TopBuyer[];
    selectedMonth: string;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);

const currentMonth = ref(props.selectedMonth);

watch(currentMonth, (newVal) => {
    router.get('/dasbor', { month: newVal }, { preserveState: true });
});

function drawChart() {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Reset canvas resolution
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * window.devicePixelRatio;
    canvas.height = rect.height * window.devicePixelRatio;
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

    const w = rect.width;
    const h = rect.height;

    // Clear
    ctx.clearRect(0, 0, w, h);

    const padding = { top: 20, right: 20, bottom: 40, left: 40 };
    const chartW = w - padding.left - padding.right;
    const chartH = h - padding.top - padding.bottom;

    const data = props.chartData;
    if (!data || data.length === 0) return;

    const maxVal = Math.max(10, Math.max(...data));
    const minVal = 0;

    // Y axis labels
    ctx.fillStyle = '#999';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    const ySteps = 5;
    for (let i = 0; i <= ySteps; i++) {
        const val = minVal + (maxVal - minVal) * (i / ySteps);
        const y = padding.top + chartH - (chartH * (i / ySteps));
        ctx.fillText(Math.round(val).toString(), padding.left - 10, y);

        // grid line
        ctx.beginPath();
        ctx.strokeStyle = '#eee';
        ctx.moveTo(padding.left, y);
        ctx.lineTo(w - padding.right, y);
        ctx.stroke();
    }

    // X axis labels
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    const labels = Array.from({ length: data.length }, (_, i) => (i + 1).toString());

    // Only show certain labels if there are too many days
    const step = Math.ceil(labels.length / 10);
    for (let i = 0; i < labels.length; i++) {
        if (i % step === 0 || i === labels.length - 1) {
            const x = padding.left + (chartW / (labels.length - 1)) * i;
            ctx.fillText(labels[i], x, h - padding.bottom + 10);
        }
    }

    // X axis label
    ctx.fillStyle = '#999';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Hari', w / 2, h - 15);

    // Line
    ctx.beginPath();
    ctx.strokeStyle = '#F9DA05';
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';

    for (let i = 0; i < data.length; i++) {
        const x = padding.left + (chartW / (data.length - 1)) * i;
        const y = padding.top + chartH - ((data[i] - minVal) / (maxVal - minVal)) * chartH;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    }
    ctx.stroke();

    // Points
    for (let i = 0; i < data.length; i++) {
        const x = padding.left + (chartW / (data.length - 1)) * i;
        const y = padding.top + chartH - ((data[i] - minVal) / (maxVal - minVal)) * chartH;
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#F9DA05';
        ctx.fill();
        ctx.strokeStyle = '#F9DA05';
        ctx.lineWidth = 2;
        ctx.stroke();
    }
}

onMounted(() => {
    drawChart();
    const ro = new ResizeObserver(() => drawChart());
    if (canvasRef.value) ro.observe(canvasRef.value);
});

watch(() => props.chartData, () => {
    drawChart();
}, { deep: true });
</script>

<template>

    <Head title="Dasbor" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Info Cards -->
            <div class="grid grid-cols-4 gap-4 mb-8">
                <InfoCard title="Pesanan Baru" :count="pesananBaruCount" href="/produksi/pesanan-baru">
                    <template #icon>
                        <PhClipboard :size="24" class="text-black" />
                    </template>
                </InfoCard>

                <InfoCard title="Konfirmasi Pembayaran" :count="konfirmasiPembayaranCount"
                    href="/produksi/konfirmasi-pembayaran">
                    <template #icon>
                        <PhCheckSquare :size="24" class="text-black" />
                    </template>
                </InfoCard>

                <InfoCard title="Antrean Produksi" :count="antreanProduksiCount" href="/produksi/antrean-produksi">
                    <template #icon>
                        <PhList :size="24" class="text-black" />
                    </template>
                </InfoCard>

                <InfoCard title="Stok Menipis" :count="stokMenipisCount" href="/produksi/stok-menipis">
                    <template #icon>
                        <PhCube :size="24" class="text-black" />
                    </template>
                </InfoCard>
            </div>

            <!-- Chart -->
            <div class="border border-black/10 rounded-lg p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-black text-base font-bold">Penjualan</h2>
                    <input type="month" v-model="currentMonth"
                        class="border border-black/20 rounded px-2 py-1 text-sm text-black focus:outline-none focus:border-black" />
                </div>
                <canvas ref="canvasRef" class="w-full" style="height: 300px;"></canvas>
            </div>

            <!-- Top Buyers -->
            <div class="border border-black/10 rounded-lg p-6">
                <h2 class="text-black text-base font-bold mb-4">Pembeli Teratas</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div v-if="pembeliTeratas.length === 0" class="col-span-3 text-black/50 text-sm">Belum ada pembeli
                        di bulan ini.
                    </div>
                    <div v-for="user in pembeliTeratas" :key="user.id" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-black/10 flex items-center justify-center shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 20c0-4 4-7 8-7s8 3 8 7" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-black text-sm font-medium truncate">{{ user.nama }}</div>
                            <div class="text-black/50 text-xs">
                                {{ user.whatsapp || '-' }}
                                <span class="text-black/30"> • </span>
                                {{ Number(user.total_pesanan || 0) }} pesanan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
