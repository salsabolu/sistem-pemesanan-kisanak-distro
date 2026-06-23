<script setup lang="ts">
import { ref, watch, nextTick, onBeforeUnmount, shallowRef, computed, onErrorCaptured } from 'vue';
import { Canvas as FabricCanvas } from 'fabric';
import {
    loadSvgFromUrl,
    updateAllZoneColors,
    renderSvgToCanvas,
} from '@/lib/svgPatternUtils';
import { PhX, PhDownloadSimple, PhCopy } from '@phosphor-icons/vue';
import Alert from './Alert.vue';

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
    <div v-if="props.open" class="dpm-overlay" @click="emit('close')">
        <Alert v-model:show="alertShow" :message="alertMessage" :type="alertType" />

        <div class="dpm-modal" @click.stop>
            <!-- Header -->
            <div class="dpm-header">
                <div>
                    <h3 class="dpm-title">Hasil Kustom Desain</h3>
                    <p v-if="props.productName" class="dpm-subtitle">{{ props.productName }}</p>
                </div>
                <div class="dpm-header-actions">
                    <button class="dpm-icon-btn" @click="downloadDesign" title="Download PNG">
                        <PhDownloadSimple :size="18" />
                    </button>
                    <button class="dpm-icon-btn" @click="emit('close')" title="Tutup">
                        <PhX :size="18" />
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="dpm-body">
                <!-- Canvas Area -->
                <div ref="containerRef" class="dpm-canvas-area">
                    <!-- Loading -->
                    <div v-if="isLoading" class="dpm-loading">
                        <div class="dpm-spinner"></div>
                        <span>Memuat desain...</span>
                    </div>

                    <!-- Error -->
                    <div v-else-if="loadError" class="dpm-error">
                        <span>⚠️ {{ loadError }}</span>
                    </div>

                    <!-- Composite Preview -->
                    <canvas ref="compositeCanvasRef" class="dpm-preview-canvas"></canvas>

                    <!-- Hidden canvases for rendering -->
                    <canvas ref="svgCanvasRef" class="dpm-hidden-canvas"></canvas>
                    <canvas ref="fabricCanvasRef" class="dpm-hidden-canvas"></canvas>
                </div>

                <!-- Sidebar Lists -->
                <div v-if="displayTeksList.length > 0 || displayGambarList.length > 0" class="dpm-sidebar">
                    <div class="dpm-sidebar-content">
                        <div v-if="displayTeksList.length > 0" class="dpm-section">
                            <h4 class="dpm-section-title">Daftar Teks</h4>
                            <div class="dpm-list">
                                <div v-for="(t, i) in displayTeksList" :key="t.id" class="dpm-list-item">
                                    <span class="dpm-item-index">{{ i + 1 }}</span>
                                    <span class="dpm-item-text">{{ t.teks }}</span>
                                    <button class="dpm-copy-btn" @click="copyText(t.teks)" title="Salin Teks">
                                        <PhCopy :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="displayGambarList.length > 0" class="dpm-section">
                            <h4 class="dpm-section-title">Daftar Gambar</h4>
                            <div class="dpm-list">
                                <div v-for="(g, i) in displayGambarList" :key="g.id"
                                    class="dpm-list-item dpm-list-item--image">
                                    <span class="dpm-item-index">{{ i + 1 }}</span>
                                    <div class="dpm-item-image-wrapper">
                                        <img :src="g.file" alt="Gambar Kustom" class="dpm-item-image" />
                                    </div>
                                    <button class="dpm-icon-btn dpm-download-btn"
                                        @click.stop="forceDownload(g.file, 'gambar-kustom.png')"
                                        title="Unduh Gambar Asli">
                                        <PhDownloadSimple :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dpm-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.dpm-modal {
    background: #1a1a2e;
    border-radius: 16px;
    width: 90vw;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.dpm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.dpm-title {
    font-size: 15px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0;
}

.dpm-subtitle {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.dpm-header-actions {
    display: flex;
    gap: 6px;
}

.dpm-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.04);
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s ease;
}

.dpm-icon-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f1f5f9;
    border-color: rgba(255, 255, 255, 0.2);
}

.dpm-canvas-area {
    flex: 1;
    min-width: 0;
    /* FIX: Allows flex container to shrink below intrinsic canvas width */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 400px;
    position: relative;
    background: #1e1e2f;
    /* Changed from #0f0f1a to help diagnose black screen */
}

.dpm-preview-canvas {
    max-width: 100%;
    max-height: 60vh;
    object-fit: contain;
    border-radius: 8px;
}

.dpm-hidden-canvas {
    position: absolute;
    left: -9999px;
    top: -9999px;
    width: 0;
    height: 0;
    pointer-events: none;
    opacity: 0;
}

.dpm-loading,
.dpm-error {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #94a3b8;
    font-size: 14px;
    z-index: 5;
}

.dpm-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(148, 163, 184, 0.2);
    border-top-color: #818cf8;
    border-radius: 50%;
    animation: dpm-spin 0.8s linear infinite;
}

@keyframes dpm-spin {
    to {
        transform: rotate(360deg);
    }
}

.dpm-error {
    color: #f87171;
}

/* ─── Body & Sidebar ─── */
.dpm-body {
    display: flex;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

@media (max-width: 640px) {
    .dpm-body {
        flex-direction: column;
        overflow-y: auto;
    }

    .dpm-canvas-area {
        min-height: 300px;
    }

    .dpm-sidebar {
        border-left: none;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
}

.dpm-sidebar {
    width: 260px;
    min-width: 260px;
    position: relative;
    z-index: 20;
    background: rgba(0, 0, 0, 0.2);
    border-left: 1px solid rgba(255, 255, 255, 0.08);
}

.dpm-sidebar-content {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow-y: auto;
}

.dpm-section {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.dpm-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 12px 0;
}

.dpm-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dpm-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.03);
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.dpm-list-item--image {
    padding: 8px;
}

.dpm-item-index {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    background: rgba(255, 255, 255, 0.05);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    flex-shrink: 0;
}

.dpm-item-text {
    font-size: 13px;
    color: #e2e8f0;
    font-weight: 500;
    word-break: break-word;
}

.dpm-item-image-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.dpm-item-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.dpm-download-btn {
    margin-left: auto;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(129, 140, 248, 0.1);
    color: #818cf8;
    transition: all 0.2s ease;
    text-decoration: none;
}

.dpm-download-btn:hover,
.dpm-copy-btn:hover {
    background: #818cf8;
    color: #ffffff;
}

.dpm-copy-btn {
    margin-left: auto;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(129, 140, 248, 0.1);
    color: #818cf8;
    transition: all 0.2s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
}
</style>
