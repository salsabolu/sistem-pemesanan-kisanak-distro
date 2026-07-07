<script setup lang="ts">
import { ref, watch, nextTick, onBeforeUnmount, shallowRef, computed, onErrorCaptured } from 'vue';
import { Canvas as FabricCanvas } from 'fabric';
import {
    loadSvgFromUrl,
    updateAllZoneColors,
    renderSvgToCanvas,
} from '@/lib/svgPatternUtils';
import { PhX, PhDownloadSimple, PhCopy, PhSun, PhMoon, PhMagnifyingGlassMinus, PhMagnifyingGlassPlus, PhArrowsOut } from '@phosphor-icons/vue';
import Alert from './Alert.vue';
import Button from './Button.vue';
import TshirtViewer from './customizer/TshirtViewer.vue';

const props = defineProps<{
    open: boolean;
    desainJson: string | null;
    productName?: string;
    teksList?: Array<{ id: number; teks: string }>;
    gambarList?: Array<{ id: number; file: string }>;
}>();

const emit = defineEmits<{ (e: 'close'): void }>();

const containerRef = ref<HTMLDivElement | null>(null);
const svgCanvasRef = ref<HTMLCanvasElement | null>(null);
const fabricCanvasRef = ref<HTMLCanvasElement | null>(null);
const compositeCanvasRef = ref<HTMLCanvasElement | null>(null);
const fabricCanvas = shallowRef<FabricCanvas | null>(null);

const isLoading = ref(false);
const loadError = ref<string | null>(null);

const internalTeksList = ref<Array<{ id: number; teks: string }>>([]);
const internalGambarList = ref<Array<{ id: number; file: string }>>([]);

const alertShow = ref(false);
const alertMessage = ref('');
const alertType = ref<'success' | 'error' | 'warning' | 'info'>('success');

const displayTeksList = computed(() => {
    return (props.teksList && props.teksList.length > 0) ? props.teksList : internalTeksList.value;
});

const isDarkMode = ref(document.documentElement.classList.contains('dark'));
function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

const viewMode = ref<'2d' | '3d'>('2d');
const zoomScale = ref(1);

function zoomIn() {
    zoomScale.value = Math.min(zoomScale.value + 0.1, 3);
}

function zoomOut() {
    zoomScale.value = Math.max(zoomScale.value - 0.1, 0.5);
}

function resetZoom() {
    zoomScale.value = 1;
}

watch(viewMode, (val) => {
    if (val === '3d') resetZoom();
});

const displayGambarList = computed(() => {
    return (props.gambarList && props.gambarList.length > 0) ? props.gambarList : internalGambarList.value;
});

const CANVAS_DISPLAY_SIZE = 500;

watch(() => props.open, async (isOpen) => {
    if (isOpen && props.desainJson) {
        extractLists();
        await nextTick();
        renderDesign();
    } else {
        cleanupCanvas();
    }
});

function extractLists() {
    internalTeksList.value = [];
    internalGambarList.value = [];
    if (!props.desainJson) return;

    try {
        const design = typeof props.desainJson === 'string'
            ? JSON.parse(props.desainJson)
            : props.desainJson;

        if (design.fabricObjects && design.fabricObjects.objects) {
            let tId = 1;
            let gId = 1;
            design.fabricObjects.objects.forEach((obj: any) => {
                const t = (obj.type || '').toLowerCase();
                if (t === 'i-text' || t === 'itext' || t === 'text') {
                    internalTeksList.value.push({ id: tId++, teks: obj.text });
                } else if (t === 'image') {
                    internalGambarList.value.push({ id: gId++, file: obj.src });
                }
            });
        }
    } catch (e) {
        console.error('Gagal ekstrak list:', e);
    }
}

async function renderDesign() {
    if (!props.desainJson) return;

    isLoading.value = true;
    loadError.value = null;

    try {
        const design = typeof props.desainJson === 'string'
            ? JSON.parse(props.desainJson)
            : props.desainJson;

        const patternPath = design.patternPath || '/patterns/pattern-tshirt1.svg';
        const baseColor = design.baseColor || null;
        const zoneColors: Record<string, string> = design.zoneColors || {};
        const fabricObjects = design.fabricObjects || null;

        // 1. Load and render SVG pattern
        const doc = await loadSvgFromUrl(patternPath);

        const svgEl = doc.querySelector('svg');
        const canvasWidth = parseInt(svgEl?.getAttribute('width') || '2048', 10);
        const canvasHeight = parseInt(svgEl?.getAttribute('height') || '2048', 10);

        // Apply zone colors
        if (baseColor) {
            updateAllZoneColors(doc, baseColor);
        }
        // Apply individual zone colors (overrides base)
        for (const [zoneId, color] of Object.entries(zoneColors)) {
            const el = doc.getElementById(zoneId);
            if (el) {
                el.setAttribute('fill', color);
                el.setAttribute('style', `fill: ${color}`);
            }
        }

        // 2. Render SVG to canvas
        if (svgCanvasRef.value) {
            svgCanvasRef.value.width = canvasWidth;
            svgCanvasRef.value.height = canvasHeight;
            await renderSvgToCanvas(doc, svgCanvasRef.value);
        }

        // 3. Render Fabric objects
        cleanupCanvas();
        if (fabricCanvasRef.value && fabricObjects) {
            const fc = new FabricCanvas(fabricCanvasRef.value, {
                width: canvasWidth,
                height: canvasHeight,
                backgroundColor: 'transparent',
                selection: false,
                interactive: false,
            });
            fabricCanvas.value = fc;

            // Replace or filter out dead blob URLs
            if (fabricObjects && fabricObjects.objects) {
                const filteredObjects: any[] = [];
                let gIndex = 0;

                fabricObjects.objects.forEach((obj: any) => {
                    const t = (obj.type || '').toLowerCase();
                    if (t === 'image') {
                        if (props.gambarList && gIndex < props.gambarList.length) {
                            // Replace with real URL
                            obj.src = props.gambarList[gIndex].file;
                            filteredObjects.push(obj);
                            gIndex++;
                        } else if (obj.src && obj.src.startsWith('blob:')) {
                            // Skip dead blob URL to prevent hanging/crashing
                            console.warn('[DesignPreviewModal] Skipping dead blob URL:', obj.src);
                        } else {
                            // Keep normal or data: URLs
                            filteredObjects.push(obj);
                        }
                    } else {
                        filteredObjects.push(obj);
                    }
                });
                fabricObjects.objects = filteredObjects;
            }

            try {
                await fc.loadFromJSON(fabricObjects);
            } catch (err) {
                console.warn('[DesignPreviewModal] fabric loadFromJSON warning:', err);
                // Continue even if some objects failed to load
            }
            fc.renderAll();

            // Disable interaction on all objects
            fc.getObjects().forEach(obj => {
                obj.set({
                    selectable: false,
                    evented: false,
                    hasControls: false,
                    hasBorders: false,
                });
            });
            fc.renderAll();
        }

        // 4. Compose into composite canvas
        if (compositeCanvasRef.value && svgCanvasRef.value) {
            const composite = compositeCanvasRef.value;
            composite.width = canvasWidth;
            composite.height = canvasHeight;
            const ctx = composite.getContext('2d');
            if (ctx) {
                ctx.clearRect(0, 0, canvasWidth, canvasHeight);
                ctx.drawImage(svgCanvasRef.value, 0, 0);

                if (fabricCanvas.value) {
                    const fabricEl = (fabricCanvas.value as any).getElement();
                    if (fabricEl) {
                        ctx.drawImage(fabricEl, 0, 0, canvasWidth, canvasHeight);
                    }
                }
            }
        }

        isLoading.value = false;
    } catch (err) {
        console.error('[DesignPreviewModal] Error rendering design:', err);
        loadError.value = (err as Error).message || 'Gagal memuat desain';
        isLoading.value = false;
    }
}

function cleanupCanvas() {
    if (fabricCanvas.value) {
        fabricCanvas.value.dispose();
        fabricCanvas.value = null;
    }
}

function downloadDesign() {
    if (!compositeCanvasRef.value) return;
    compositeCanvasRef.value.toBlob((blob) => {
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `desain-${props.productName || 'kustom'}.png`;
        a.click();
        URL.revokeObjectURL(url);
    }, 'image/png');
}

async function copyText(text: string) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback for non-secure contexts (e.g., HTTP localhost)
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
        alertMessage.value = 'Teks berhasil disalin!';
        alertType.value = 'success';
        alertShow.value = true;
    } catch (err) {
        console.error('Gagal menyalin teks:', err);
        alertMessage.value = 'Gagal menyalin teks. Silakan salin secara manual.';
        alertType.value = 'error';
        alertShow.value = true;
    }
}

async function forceDownload(url: string, filename: string) {
    try {
        const response = await fetch(url);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(blobUrl);
    } catch (err) {
        console.error('Gagal mengunduh gambar:', err);
        // Fallback open in new tab
        window.open(url, '_blank');
    }
}

onErrorCaptured((err) => {
    console.error('[DesignPreviewModal] Vue Error Captured:', err);
    loadError.value = 'Terjadi kesalahan sistem: ' + (err.message || String(err));
    isLoading.value = false;
    return false; // Stop propagation
});

onBeforeUnmount(() => {
    cleanupCanvas();
});
</script>

<template>
    <div v-if="props.open" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click="emit('close')">
        <Alert v-model:show="alertShow" :message="alertMessage" :type="alertType" />

        <div class="bg-white dark:bg-[#1a1a2e] w-[90vw] max-w-[800px] max-h-[90vh] overflow-hidden flex flex-col shadow-[0_24px_48px_rgba(0,0,0,0.4)] border border-gray-200 dark:border-white/10"
            @click.stop>
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h3 class="text-[15px] font-medium text-gray-900 dark:text-slate-100 m-0">Hasil Kustom Desain</h3>
                    <p v-if="props.productName"
                        class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 uppercase tracking-wide">{{
                            props.productName }}</p>
                </div>
                <div class="flex gap-1.5">
                    <Button variant="icon" @click="toggleTheme" title="Ganti Tema">
                        <PhSun v-if="isDarkMode" :size="18" />
                        <PhMoon v-else :size="18" />
                    </Button>
                    <Button variant="icon" @click="downloadDesign" title="Download PNG">
                        <PhDownloadSimple :size="18" />
                    </Button>
                    <Button variant="icon" @click="emit('close')" title="Tutup" class="text-red">
                        <PhX :size="18" />
                    </Button>
                </div>
            </div>

            <!-- Body -->
            <div class="flex flex-col sm:flex-row flex-1 min-h-0 overflow-y-auto sm:overflow-hidden">
                <!-- Canvas Area -->
                <div ref="containerRef"
                    class="flex-1 min-w-0 relative bg-white dark:bg-[#1e1e2f] overflow-hidden min-h-[300px] sm:min-h-[400px]">

                    <!-- View Mode Toggle -->
                    <div
                        class="absolute top-4 left-4 z-20 flex bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button @click="viewMode = '2d'" class="px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="viewMode === '2d' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">2D
                            Flat</button>
                        <button @click="viewMode = '3d'"
                            class="px-3 py-1.5 text-xs font-medium transition-colors border-l border-gray-200 dark:border-gray-700"
                            :class="viewMode === '3d' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">3D
                            Model</button>
                    </div>

                    <!-- Loading -->
                    <div v-if="isLoading"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-400 text-sm z-10 bg-white/50 dark:bg-[#1e1e2f]/50">
                        <div
                            class="w-8 h-8 border-[3px] border-slate-400/20 border-t-indigo-400 rounded-full animate-spin">
                        </div>
                        <span>Memuat desain...</span>
                    </div>

                    <!-- Error -->
                    <div v-else-if="loadError"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-red-400 text-sm z-10">
                        <span>⚠️ {{ loadError }}</span>
                    </div>

                    <div class="absolute inset-0 p-5 sm:p-8 pt-16 sm:pt-20 z-10">
                        <!-- Zoom Controls -->
                        <div v-if="viewMode === '2d'"
                            class="absolute bottom-4 right-4 flex bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 overflow-hidden z-20">
                            <button @click="zoomOut"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                                title="Zoom Out">
                                <PhMagnifyingGlassMinus :size="16" />
                            </button>
                            <button @click="resetZoom"
                                class="px-2 text-[10px] font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border-x border-gray-200 dark:border-gray-700 transition-colors"
                                title="Reset Zoom">
                                <div class="flex items-center gap-1">
                                    <PhArrowsOut :size="14" />
                                    {{ Math.round(zoomScale * 100) }}%
                                </div>
                            </button>
                            <button @click="zoomIn"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                                title="Zoom In">
                                <PhMagnifyingGlassPlus :size="16" />
                            </button>
                        </div>

                        <!-- Composite Preview -->
                        <div
                            :class="viewMode === '2d' ? 'w-full h-full overflow-auto custom-scrollbar rounded-lg' : 'absolute -left-[9999px] -top-[9999px] w-0 h-0 opacity-0 pointer-events-none'">
                            <div class="flex items-center justify-center transition-all duration-200 min-w-full min-h-full"
                                :style="{ width: `${100 * zoomScale}%`, height: `${100 * zoomScale}%` }">
                                <canvas ref="compositeCanvasRef"
                                    class="w-full h-full object-contain drop-shadow-xl"></canvas>
                            </div>
                        </div>

                        <!-- 3D View -->
                        <div v-if="viewMode === '3d'" class="w-full h-full">
                            <TshirtViewer :modelPath="'/models/tshirt1.obj'" :designCanvas="compositeCanvasRef" />
                        </div>
                    </div>

                    <!-- Hidden canvases for rendering -->
                    <canvas ref="svgCanvasRef"
                        class="absolute -left-[9999px] -top-[9999px] w-0 h-0 pointer-events-none opacity-0"></canvas>
                    <canvas ref="fabricCanvasRef"
                        class="absolute -left-[9999px] -top-[9999px] w-0 h-0 pointer-events-none opacity-0"></canvas>
                </div>

                <!-- Sidebar Lists -->
                <div v-if="displayTeksList.length > 0 || displayGambarList.length > 0"
                    class="w-full sm:w-[260px] sm:min-w-[260px] relative z-20 bg-white dark:bg-black/20 border-t sm:border-t-0 sm:border-l border-gray-200 dark:border-white/10">
                    <div class="relative sm:absolute inset-0 sm:overflow-y-auto">
                        <div v-if="displayTeksList.length > 0" class="p-4 border-b border-gray-200 dark:border-white/5">
                            <h4
                                class="text-[11px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                                Daftar Teks</h4>
                            <div class="flex flex-col gap-2">
                                <div v-for="(t, i) in displayTeksList" :key="t.id"
                                    class="flex items-center gap-3 bg-white dark:bg-white/5 p-2.5 rounded-lg border border-gray-200 dark:border-white/5">
                                    <span
                                        class="text-[10px] font-medium text-gray-500 dark:text-slate-500 bg-white-hover/50 dark:bg-white/5 w-5 h-5 flex items-center justify-center rounded shrink-0">{{
                                            i + 1 }}</span>
                                    <span
                                        class="text-[13px] text-gray-900 dark:text-slate-200 font-medium break-words">{{
                                            t.teks }}</span>
                                    <Button variant="icon" class="ml-auto" @click="copyText(t.teks)" title="Salin Teks">
                                        <PhCopy :size="16" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-if="displayGambarList.length > 0"
                            class="p-4 border-b border-gray-200 dark:border-white/5">
                            <h4
                                class="text-[11px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                                Daftar Gambar</h4>
                            <div class="flex flex-col gap-2">
                                <div v-for="(g, i) in displayGambarList" :key="g.id"
                                    class="flex items-center gap-3 bg-white dark:bg-white/5 p-2 rounded-lg border border-gray-200 dark:border-white/5">
                                    <span
                                        class="text-[10px] font-medium text-gray-500 dark:text-slate-500 bg-white-hover/50 dark:bg-white/5 w-5 h-5 flex items-center justify-center rounded shrink-0">{{
                                            i + 1 }}</span>
                                    <div
                                        class="w-12 h-12 rounded-md overflow-hidden bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 shrink-0">
                                        <img :src="g.file" alt="Gambar Kustom" class="w-full h-full object-cover" />
                                    </div>
                                    <Button variant="icon" class="ml-auto"
                                        @click.stop="forceDownload(g.file, 'gambar-kustom.png')"
                                        title="Unduh Gambar Asli">
                                        <PhDownloadSimple :size="16" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
