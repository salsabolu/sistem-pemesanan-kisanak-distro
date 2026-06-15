<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PhTrash,
} from '@phosphor-icons/vue';
import { ref, computed, watch } from 'vue';
import Sidebar from '../../components/Sidebar.vue';

type BahanData = {
    id: number;
    nama: string;
    stok: number;
    stok_minimum: number;
    durasi_produksi: number;
    durasi_restok: number;
    is_active: boolean;
    kategori?: { id: number; nama: string };
    warna?: { id: number; nama: string; kode: string } | null;
    ukuran?: { id: number; nama: string };
};

type ProdukData = {
    id: number;
    id_bahan: number;
    nama: string;
    harga: number;
    deskripsi: string | null;
    gambar: string | null;
    is_active: boolean;
    bahan?: BahanData | null;
};

const props = defineProps<{
    produks?: { data: ProdukData[]; links: { url: string | null; label: string; active: boolean }[] };
    bahan?: { id: number; nama: string; kategori?: { nama: string }; warna?: { nama: string } | null; ukuran?: { nama: string } }[];
}>();

const showModal = ref(false);
const editingItem = ref<ProdukData | null>(null);

const gambarFile = ref<File | null>(null);
const gambarPreview = ref<string | null>(null);

const form = useForm({
    id_bahan: '',
    nama: '',
    harga: 0,
    deskripsi: '',
    is_active: true as boolean,
});

const hargaFormatted = computed({
    get() {
        if (!form.harga && form.harga !== 0) return '';
        return form.harga.toLocaleString('id-ID');
    },
    set(val: string) {
        const numericStr = val.replace(/\D/g, '');
        form.harga = numericStr ? parseInt(numericStr, 10) : 0;
    }
});

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        gambarFile.value = target.files[0];
        gambarPreview.value = URL.createObjectURL(target.files[0]);
    }
}

const isNameManuallyEdited = ref(false);

watch(() => form.id_bahan, (newVal) => {
    if (editingItem.value) return;
    if (isNameManuallyEdited.value) return;

    const selectedBahan = bahanList.value.find(b => String(b.id) === newVal);
    if (selectedBahan) {
        form.nama = selectedBahan.nama;
    } else {
        form.nama = '';
    }
});

function openTambah() {
    editingItem.value = null; form.reset(); form.clearErrors();
    form.is_active = true;
    gambarFile.value = null; gambarPreview.value = null;
    isNameManuallyEdited.value = false;
    showModal.value = true;
}

function openEdit(item: ProdukData) {
    editingItem.value = item; form.clearErrors();
    form.id_bahan = String(item.id_bahan);
    form.nama = item.nama;
    form.harga = item.harga;
    form.deskripsi = item.deskripsi ?? '';
    form.is_active = item.is_active;
    gambarFile.value = null;
    gambarPreview.value = (item.gambar && item.gambar !== '-') ? item.gambar : null;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false; editingItem.value = null; form.reset();
    gambarFile.value = null; gambarPreview.value = null;
}

function submitForm() {
    const fd = new FormData();
    fd.append('id_bahan', form.id_bahan);
    fd.append('nama', form.nama);
    fd.append('harga', String(form.harga));
    fd.append('deskripsi', form.deskripsi);
    fd.append('is_active', form.is_active ? '1' : '0');
    if (gambarFile.value) fd.append('gambar', gambarFile.value);

    if (editingItem.value) {
        fd.append('_method', 'PUT');
        router.post(`/master/produk/${editingItem.value.id}`, fd, { onSuccess: () => closeModal() });
    } else {
        router.post('/master/produk', fd, { onSuccess: () => closeModal() });
    }
}

function deleteItem(item: ProdukData) {
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${item.nama}"?`)) {
        router.delete(`/master/produk/${item.id}`, { preserveScroll: true });
    }
}

function statusColor(active: boolean) {
    return active ? 'bg-green text-white' : 'bg-black/10 text-black/40';
}

function toggleStatus(item: ProdukData) {
    router.patch(`/master/produk/${item.id}/status`, { is_active: !item.is_active }, { preserveScroll: true });
}

const items = computed(() => props.produks?.data ?? []);
const paginationLinks = computed(() => props.produks?.links ?? []);
const bahanList = computed(() => props.bahan ?? []);
</script>

<template>

    <Head title="Produk" />
    <div class="bg-white min-h-screen flex">
        <Sidebar />

        <main class="flex-1 p-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-black text-xl font-medium">Produk</h1>
                <button type="button" class="border border-black px-4 py-1.5 text-sm text-black hover:bg-black/5"
                    @click="openTambah">Tambah</button>
            </div>

            <div class="border border-black/10 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-white-hover/50">
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">No</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">
                                <span class="inline-flex items-center gap-1">Nama
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M7 15l5 5 5-5" />
                                        <path d="M7 9l5-5 5 5" />
                                    </svg>
                                </span>
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Bahan</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Harga</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Status</th>
                            <th class="text-center px-3 py-3 text-black font-medium text-xs uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in items" :key="item.id"
                            class="border-t border-black/5 cursor-pointer hover:bg-black/[0.02]"
                            @click="openEdit(item)">
                            <td class="px-3 py-3 text-black">{{ idx + 1 }}</td>
                            <td class="px-3 py-3">
                                <div class="text-black text-sm font-medium uppercase">{{ item.nama }}</div>
                                <div class="text-black/50 text-xs uppercase">
                                    {{ item.bahan?.warna?.nama ?? '-' }} / {{ item.bahan?.ukuran?.nama ?? '-' }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-black/70 text-xs uppercase">
                                {{ item.bahan?.kategori?.nama ?? '-' }}
                            </td>
                            <td class="px-3 py-3 text-black">Rp{{ Number(item.harga).toLocaleString('id-ID') }}</td>
                            <td class="px-3 py-3" @click.stop>
                                <button type="button" class="px-3 py-1 rounded-full text-xs"
                                    :class="statusColor(item.is_active)" @click="toggleStatus(item)">
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>
                            <td class="px-3 py-3 text-center" @click.stop>
                                <button type="button"
                                    class="text-red-600 hover:text-red-800 text-xs font-medium uppercase"
                                    @click="deleteItem(item)">
                                    <PhTrash :size="18" class="text-red-600" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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

    <!-- Modal Tambah/Edit Produk -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-black text-lg font-medium mb-4">{{ editingItem ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-black text-sm mb-1">Nama Produk <span class="text-red-500">*</span></label>
                        <input v-model="form.nama" type="text" @input="isNameManuallyEdited = true"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                        <div v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-black text-sm mb-1">Bahan <span class="text-red-500">*</span></label>
                        <select v-model="form.id_bahan"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required>
                            <option value="" disabled>Pilih bahan</option>
                            <option v-for="b in bahanList" :key="b.id" :value="String(b.id)">
                                {{ b.nama }} — {{ b.kategori?.nama ?? '' }}{{ b.warna ? ' / ' + b.warna.nama : '' }}{{ b.ukuran ? ' / ' + b.ukuran.nama : '' }}
                            </option>
                        </select>
                        <div v-if="form.errors.id_bahan" class="text-red-500 text-xs mt-1">{{ form.errors.id_bahan }}</div>
                    </div>

                    <div>
                        <label class="block text-black text-sm mb-1">Harga <span class="text-red-500">*</span></label>
                        <input v-model="hargaFormatted" type="text"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                        <div v-if="form.errors.harga" class="text-red-500 text-xs mt-1">{{ form.errors.harga }}</div>
                    </div>

                    <div>
                        <label class="block text-black text-sm mb-1">Status</label>
                        <div class="flex items-center gap-3 mt-2">
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

                    <div>
                        <label class="block text-black text-sm mb-1">Gambar</label>
                        <input type="file" accept="image/jpeg,image/png,image/webp" @change="onFileChange"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
                        <img v-if="gambarPreview" :src="gambarPreview" alt="Preview"
                            class="mt-2 h-24 w-24 object-cover rounded border border-black/10" />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-black text-sm mb-1">Deskripsi</label>
                        <textarea v-model="form.deskripsi" rows="3"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black resize-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm text-black border border-black/20 hover:bg-black/5"
                        @click="closeModal">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-black hover:bg-black/80"
                        :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
</template>
