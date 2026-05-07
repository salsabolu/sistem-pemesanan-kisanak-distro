<script setup lang="ts">
import Sidebar from '../../components/Sidebar.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    PhPencilSimple,
    PhTrash,
} from '@phosphor-icons/vue';

const props = defineProps<{
    warna?: {
        data: { id: number; nama: string }[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const showModal = ref(false);
const editingItem = ref<{ id: number; nama: string } | null>(null);

const form = useForm({
    nama: '',
});

function openTambah() {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item: { id: number; nama: string }) {
    editingItem.value = item;
    form.nama = item.nama;
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingItem.value = null;
    form.reset();
}

function submitForm() {
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

const fallbackItems = [
    { id: 1, nama: 'PUTIH' }, { id: 2, nama: 'HITAM' }, { id: 3, nama: 'NAVY' },
    { id: 4, nama: 'MAROON' }, { id: 5, nama: 'ARMY' }, { id: 6, nama: 'CREAM' },
    { id: 7, nama: 'BENHUR' }, { id: 8, nama: 'MERAH' }, { id: 9, nama: 'HIJAU BOTOL' },
    { id: 10, nama: 'HIJAU DAUN' },
];

const items = computed(() => props.warna?.data ?? fallbackItems);
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

            <div class="border border-black/10 rounded-lg overflow-hidden max-w-xl">
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
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in items" :key="item.id" class="border-t border-black/5">
                            <td class="px-4 py-3 text-black">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-black font-medium uppercase">{{ item.nama }}</td>
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
                        :class="link.active ? 'border-black bg-black text-white' : 'border-black/20 bg-white text-black hover:bg-black/5'"
                        v-html="link.label" />
                    <span v-else
                        class="min-w-[32px] h-8 px-2 text-sm border border-black/10 bg-white text-black/30 flex items-center justify-center"
                        v-html="link.label" />
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
