<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PhPencilSimple,
    PhTrash,
} from '@phosphor-icons/vue';
import { ref, computed } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

const props = defineProps<{
    ukuran?: {
        data: { id: number; nama: string; panjang: number | null; lebar: number | null; is_active: boolean }[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const showModal = ref(false);
const editingItem = ref<{ id: number; nama: string; panjang: number | null; lebar: number | null; is_active: boolean } | null>(null);

const form = useForm({
    nama: '',
    panjang: null as number | null,
    lebar: null as number | null,
    is_active: true as boolean,
});

function openTambah() {
    editingItem.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item: { id: number; nama: string; panjang: number | null; lebar: number | null; is_active: boolean }) {
    editingItem.value = item;
    form.nama = item.nama;
    form.panjang = item.panjang;
    form.lebar = item.lebar;
    form.is_active = item.is_active;
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
        form.put(`/master/ukuran/${editingItem.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/master/ukuran', {
            onSuccess: () => closeModal(),
        });
    }
}

function hapusItem(id: number) {
    if (confirm('Yakin ingin menghapus ukuran ini?')) {
        router.delete(`/master/ukuran/${id}`);
    }
}

const items = computed(() => props.ukuran?.data ?? []);
const paginationLinks = computed(() => props.ukuran?.links ?? []);
</script>

<template>

    <Head title="Ukuran" />

    <div class="bg-white min-h-screen flex">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-black text-xl font-medium">Ukuran</h1>
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
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">
                                <div>Panjang</div>
                                <div class="text-black/40 text-[10px] normal-case">cm</div>
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase">
                                <div>Lebar</div>
                                <div class="text-black/40 text-[10px] normal-case">cm</div>
                            </th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-28">Status</th>
                            <th class="text-left px-4 py-3 text-black font-medium text-xs uppercase w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-black/50">Belum ada ukuran.</td>
                        </tr>
                        <tr v-for="(item, idx) in items" :key="item.id" class="border-t border-black/5">
                            <td class="px-4 py-3 text-black">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-black font-medium uppercase">{{ item.nama }}</td>
                            <td class="px-4 py-3 text-black">{{ item.panjang ?? '-' }}</td>
                            <td class="px-4 py-3 text-black">{{ item.lebar ?? '-' }}</td>
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
                {{ editingItem ? 'Edit Ukuran' : 'Tambah Ukuran' }}
            </h2>

            <form @submit.prevent="submitForm">
                <div class="mb-4">
                    <label class="block text-black text-sm mb-1">Nama <span class="text-red-500">*</span></label>
                    <input v-model="form.nama" type="text"
                        class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                        placeholder="Masukkan nama ukuran" required />
                    <div v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-black text-sm mb-1">Panjang (cm)</label>
                        <input v-model.number="form.panjang" type="number" min="0"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            placeholder="Opsional" />
                        <div v-if="form.errors.panjang" class="text-red-500 text-xs mt-1">{{ form.errors.panjang }}</div>
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Lebar (cm)</label>
                        <input v-model.number="form.lebar" type="number" min="0"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            placeholder="Opsional" />
                        <div v-if="form.errors.lebar" class="text-red-500 text-xs mt-1">{{ form.errors.lebar }}</div>
                    </div>
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
