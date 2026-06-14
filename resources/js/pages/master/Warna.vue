<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PhPencilSimple,
    PhTrash,
} from '@phosphor-icons/vue';
import { ref, computed } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

const props = defineProps<{
    warna?: {
        data: { id: number; nama: string; kode: string; is_active: boolean }[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const showModal = ref(false);
const editingItem = ref<{ id: number; nama: string; kode: string; is_active: boolean } | null>(null);

const form = useForm({
    nama: '',
    kode: '',
    is_active: true as boolean,
});

// Parse CMYK string "C,M,Y,K" into 4 separate values
const cmyk = ref({ c: 0, m: 0, y: 0, k: 0 });

function cmykToKode(): string {
    return `${cmyk.value.c},${cmyk.value.m},${cmyk.value.y},${cmyk.value.k}`;
}

function kodeToCmyk(kode: string) {
    const parts = (kode || '0,0,0,0').split(',');
    cmyk.value = {
        c: parseInt(parts[0] ?? '0') || 0,
        m: parseInt(parts[1] ?? '0') || 0,
        y: parseInt(parts[2] ?? '0') || 0,
        k: parseInt(parts[3] ?? '0') || 0,
    };
}

function openTambah() {
    editingItem.value = null;
    form.reset();
    form.is_active = true;
    cmyk.value = { c: 0, m: 0, y: 0, k: 0 };
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item: { id: number; nama: string; kode: string; is_active: boolean }) {
    editingItem.value = item;
    form.nama = item.nama;
    form.kode = item.kode;
    form.is_active = item.is_active;
    kodeToCmyk(item.kode);
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingItem.value = null;
    form.reset();
}

function submitForm() {
    form.kode = cmykToKode();
    if (editingItem.value) {
        form.put(`/master/warna/${editingItem.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/master/warna', {
            onSuccess: () => closeModal(),
        });
    }
}

function hapusItem(id: number) {
    if (confirm('Yakin ingin menghapus warna ini?')) {
        router.delete(`/master/warna/${id}`);
    }
}

// Render a small color swatch from CMYK
function cmykToRgbStyle(kode: string): string {
    const parts = (kode || '0,0,0,0').split(',').map(v => parseInt(v) || 0);
    const [c, m, y, k] = parts;
    const r = Math.round(255 * (1 - c / 100) * (1 - k / 100));
    const g = Math.round(255 * (1 - m / 100) * (1 - k / 100));
    const b = Math.round(255 * (1 - y / 100) * (1 - k / 100));
    return `rgb(${r},${g},${b})`;
}

const items = computed(() => props.warna?.data ?? []);
const paginationLinks = computed(() => props.warna?.links ?? []);
</script>

<template>

    <Head title="Warna" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-black text-xl font-medium">Warna</h1>
                <button type="button" class="border border-black px-4 py-1.5 text-sm text-black hover:bg-black/5"
                    @click="openTambah">Tambah</button>
            </div>

            <div class="border border-black/10 rounded-lg overflow-hidden max-w-2xl">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-white-hover/50">
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-16">No</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">
                                <span class="inline-flex items-center gap-1">
                                    Nama
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 15l5 5 5-5" />
                                        <path d="M7 9l5-5 5 5" />
                                    </svg>
                                </span>
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">Kode CMYK</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-8">Preview</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-28">Status</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in items" :key="item.id" class="border-t border-black/5">
                            <td class="px-4 py-3 text-black">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-black font-medium uppercase">{{ item.nama }}</td>
                            <td class="px-4 py-3 text-black/60 font-mono text-xs">{{ item.kode }}</td>
                            <td class="px-4 py-3">
                                <div class="w-6 h-6 rounded-sm border border-black/10"
                                    :style="{ backgroundColor: cmykToRgbStyle(item.kode) }"></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs"
                                    :class="item.is_active ? 'bg-green text-white' : 'bg-black/10 text-black/40'">
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="text-black/50 hover:text-black"
                                        @click="openEdit(item)">
                                        <PhPencilSimple :size="18" class="text-white-disable" />
                                    </button>
                                    <button type="button" class="text-red-500 hover:text-red-700"
                                        @click="hapusItem(item.id)">
                                        <PhTrash :size="18" class="text-red-600" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="mt-6 flex justify-center gap-1">
                <template v-for="(link, idx) in paginationLinks" :key="idx">
                    <Link v-if="link.url" :href="link.url"
                        class="min-w-[32px] h-8 px-2 text-sm border flex items-center justify-center"
                        :class="link.active ? 'border-black bg-black text-white' : 'border-black/20 bg-white text-black hover:bg-black/5'">
                        <span v-html="link.label" />
                    </Link>
                    <span v-else
                        class="min-w-[32px] h-8 px-2 text-sm border border-black/10 bg-white text-black/30 flex items-center justify-center">
                        <span v-html="link.label" />
                    </span>
                </template>
            </div>
        </main>
    </div>

    <!-- Modal Tambah/Edit -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-black text-lg font-medium mb-4">
                {{ editingItem ? 'Edit Warna' : 'Tambah Warna' }}
            </h2>

            <form @submit.prevent="submitForm">
                <div class="mb-4">
                    <label class="block text-black text-sm mb-1">Nama <span class="text-red-500">*</span></label>
                    <input v-model="form.nama" type="text"
                        class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                        placeholder="Masukkan nama warna" required />
                    <div v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</div>
                </div>

                <!-- CMYK Fields -->
                <div class="mb-4">
                    <label class="block text-black text-sm mb-2">Kode Warna CMYK <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-black/50 text-xs mb-1 text-center">C</label>
                            <input v-model.number="cmyk.c" type="number" min="0" max="100"
                                class="w-full border border-black/20 rounded px-2 py-1.5 text-sm text-center text-black focus:outline-none focus:border-black"
                                required />
                        </div>
                        <div>
                            <label class="block text-black/50 text-xs mb-1 text-center">M</label>
                            <input v-model.number="cmyk.m" type="number" min="0" max="100"
                                class="w-full border border-black/20 rounded px-2 py-1.5 text-sm text-center text-black focus:outline-none focus:border-black"
                                required />
                        </div>
                        <div>
                            <label class="block text-black/50 text-xs mb-1 text-center">Y</label>
                            <input v-model.number="cmyk.y" type="number" min="0" max="100"
                                class="w-full border border-black/20 rounded px-2 py-1.5 text-sm text-center text-black focus:outline-none focus:border-black"
                                required />
                        </div>
                        <div>
                            <label class="block text-black/50 text-xs mb-1 text-center">K</label>
                            <input v-model.number="cmyk.k" type="number" min="0" max="100"
                                class="w-full border border-black/20 rounded px-2 py-1.5 text-sm text-center text-black focus:outline-none focus:border-black"
                                required />
                        </div>
                    </div>
                    <!-- Preview -->
                    <div class="mt-2 flex items-center gap-2">
                        <div class="w-8 h-8 rounded border border-black/10"
                            :style="{ backgroundColor: cmykToRgbStyle(cmykToKode()) }"></div>
                        <span class="text-black/50 text-xs font-mono">{{ cmykToKode() }}</span>
                    </div>
                    <div v-if="form.errors.kode" class="text-red-500 text-xs mt-1">{{ form.errors.kode }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-black text-sm mb-1">Status</label>
                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="form.is_active ? 'bg-black' : 'bg-black/20'"
                            @click="form.is_active = !form.is_active">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="form.is_active ? 'translate-x-6' : 'translate-x-1'" />
                        </button>
                        <span class="text-sm text-black">{{ form.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm text-black border border-black/20 hover:bg-black/5"
                        @click="closeModal">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-black hover:bg-black/80"
                        :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
