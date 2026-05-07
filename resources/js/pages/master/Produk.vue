<script setup lang="ts">
import Sidebar from '../../components/Sidebar.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    PhTrash,
} from '@phosphor-icons/vue';

type ProdukData = {
    id: number; nama: string; satuan: string; harga: number; stok: number;
    stok_minimum: number; durasi_produksi: number; durasi_restok: number;
    deskripsi: string | null; gambar: string | null; status: string;
    id_kategori: number; id_warna: number | null; id_ukuran: number;
    kategori?: { id: number; nama: string }; warna?: { id: number; nama: string } | null;
    ukuran?: { id: number; nama: string };
};

const props = defineProps<{
    produks?: { data: ProdukData[]; links: { url: string | null; label: string; active: boolean }[] };
    kategori?: { id: number; nama: string }[];
    warna?: { id: number; nama: string }[];
    ukuran?: { id: number; nama: string }[];
}>();

const statusOptions = ['Aktif', 'Non-Aktif'] as const;
const showModal = ref(false);
const editingItem = ref<ProdukData | null>(null);

const gambarFile = ref<File | null>(null);
const gambarPreview = ref<string | null>(null);

const form = useForm({
    id_kategori: '', id_warna: '', id_ukuran: '', nama: '', satuan: '',
    harga: 0, stok: 0, stok_minimum: 0, durasi_produksi: 0, durasi_restok: 0,
    deskripsi: '', status: 'Aktif',
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

const durasiRestokHari = computed({
    get() {
        return form.durasi_restok ? form.durasi_restok / 1440 : 0;
    },
    set(val: number | string) {
        const numericVal = typeof val === 'string' ? parseFloat(val) : val;
        form.durasi_restok = (isNaN(numericVal) ? 0 : numericVal) * 1440;
    }
});

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        gambarFile.value = target.files[0];
        gambarPreview.value = URL.createObjectURL(target.files[0]);
    }
}

function openTambah() {
    editingItem.value = null; form.reset(); form.clearErrors();
    gambarFile.value = null; gambarPreview.value = null;
    showModal.value = true;
}

function openEdit(item: ProdukData) {
    editingItem.value = item; form.clearErrors();
    form.id_kategori = String(item.id_kategori); form.id_warna = item.id_warna ? String(item.id_warna) : '';
    form.id_ukuran = String(item.id_ukuran); form.nama = item.nama; form.satuan = item.satuan;
    form.harga = item.harga; form.stok = item.stok; form.stok_minimum = item.stok_minimum;
    form.durasi_produksi = item.durasi_produksi; form.durasi_restok = item.durasi_restok;
    form.deskripsi = item.deskripsi ?? '';
    form.status = item.status;
    gambarFile.value = null;
    gambarPreview.value = (item.gambar && item.gambar !== '-') ? item.gambar : null;
    showModal.value = true;
}

function closeModal() { showModal.value = false; editingItem.value = null; form.reset(); gambarFile.value = null; gambarPreview.value = null; }

function submitForm() {
    const fd = new FormData();
    fd.append('id_kategori', form.id_kategori);
    fd.append('id_warna', form.id_warna);
    fd.append('id_ukuran', form.id_ukuran);
    fd.append('nama', form.nama);
    fd.append('satuan', form.satuan);
    fd.append('harga', String(form.harga));
    fd.append('stok', String(form.stok));
    fd.append('stok_minimum', String(form.stok_minimum));
    fd.append('durasi_produksi', String(form.durasi_produksi));
    fd.append('durasi_restok', String(form.durasi_restok));
    fd.append('deskripsi', form.deskripsi);
    fd.append('status', form.status);
    if (gambarFile.value) fd.append('gambar', gambarFile.value);

    if (editingItem.value) {
        fd.append('_method', 'PUT');
        router.post(`/master/produk/${editingItem.value.id}`, fd, { onSuccess: () => closeModal() });
    } else {
        router.post('/master/produk', fd, { onSuccess: () => closeModal() });
    }
}

function cycleStatus(item: ProdukData) {
    const next = item.status === 'Aktif' ? 'Non-Aktif' : 'Aktif';
    router.put(`/master/produk/${item.id}`, { ...item, status: next }, { preserveScroll: true });
}

function deleteItem(item: ProdukData) {
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${item.nama}"?`)) {
        router.delete(`/master/produk/${item.id}`, { preserveScroll: true });
    }
}

function statusColor(s: string) { return s === 'Aktif' ? 'bg-green text-white' : 'bg-red text-white'; }

const fallback = Array.from({ length: 10 }, (_, i) => ({
    id: i + 1, nama: 'KAOS POLOS DEWASA...', satuan: '1', harga: 40000, stok: 5, stok_minimum: 5,
    durasi_produksi: 5, durasi_restok: 1440, deskripsi: null, gambar: null,
    status: i === 9 ? 'Non-Aktif' : 'Aktif', id_kategori: 1, id_warna: 1, id_ukuran: 3,
    warna: { id: 1, nama: 'PUTIH' }, ukuran: { id: 3, nama: 'M' },
} as ProdukData));

const items = computed(() => props.produks?.data ?? fallback);
const paginationLinks = computed(() => props.produks?.links ?? []);
const kategoriList = computed(() => props.kategori ?? []);
const warnaList = computed(() => props.warna ?? []);
const ukuranList = computed(() => props.ukuran ?? []);
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
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Satuan</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Harga</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Stok</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">Stok Minimum</th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">
                                <div>Durasi Produksi</div>
                                <div class="text-black/40 text-[10px] normal-case">Menit</div>
                            </th>
                            <th class="text-left px-3 py-3 text-black font-medium text-xs uppercase">
                                <div>Durasi Restock</div>
                                <div class="text-black/40 text-[10px] normal-case">Hari</div>
                            </th>
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
                                <div class="text-black/50 text-xs uppercase">{{ item.warna?.nama ?? '' }} / {{
                                    item.ukuran?.nama ?? '' }}</div>
                            </td>
                            <td class="px-3 py-3 text-black">{{ item.satuan }}</td>
                            <td class="px-3 py-3 text-black">Rp{{ Number(item.harga).toLocaleString('id-ID') }}</td>
                            <td class="px-3 py-3 text-black">{{ item.stok }}</td>
                            <td class="px-3 py-3 text-black">{{ item.stok_minimum }}</td>
                            <td class="px-3 py-3 text-black">{{ item.durasi_produksi }}</td>
                            <td class="px-3 py-3 text-black">{{ Math.round(item.durasi_restok / 1440) }}</td>
                            <td class="px-3 py-3" @click.stop>
                                <button type="button" class="px-3 py-1 rounded-full text-xs"
                                    :class="statusColor(item.status)" @click="cycleStatus(item)">{{ item.status
                                    }}</button>
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
                        :class="link.active ? 'border-black bg-black text-white' : 'border-black/20 bg-white text-black hover:bg-black/5'"
                        v-html="link.label" />
                    <span v-else
                        class="min-w-[32px] h-8 px-2 text-sm border border-black/10 bg-white text-black/30 flex items-center justify-center"
                        v-html="link.label" />
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
                        <label class="block text-black text-sm mb-1">Nama <span class="text-red-500">*</span></label>
                        <input v-model="form.nama" type="text"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Kategori <span
                                class="text-red-500">*</span></label>
                        <select v-model="form.id_kategori"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required>
                            <option value="" disabled>Pilih kategori</option>
                            <option v-for="k in kategoriList" :key="k.id" :value="String(k.id)">{{ k.nama }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Warna</label>
                        <select v-model="form.id_warna"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black">
                            <option value="">Pilih warna (Opsional)</option>
                            <option v-for="w in warnaList" :key="w.id" :value="String(w.id)">{{ w.nama }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Ukuran <span class="text-red-500">*</span></label>
                        <select v-model="form.id_ukuran"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required>
                            <option value="" disabled>Pilih ukuran</option>
                            <option v-for="u in ukuranList" :key="u.id" :value="String(u.id)">{{ u.nama }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Satuan <span class="text-red-500">*</span></label>
                        <input v-model="form.satuan" type="text"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Harga <span class="text-red-500">*</span></label>
                        <input v-model="hargaFormatted" type="text"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Stok <span class="text-red-500">*</span></label>
                        <input v-model.number="form.stok" type="number" min="0"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Stok Minimum <span
                                class="text-red-500">*</span></label>
                        <input v-model.number="form.stok_minimum" type="number" min="0"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Durasi Produksi (Menit) <span
                                class="text-red-500">*</span></label>
                        <input v-model.number="form.durasi_produksi" type="number" min="0"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Durasi Restok (Hari) <span
                                class="text-red-500">*</span></label>
                        <input v-model.number="durasiRestokHari" type="number" min="0" step="1"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required />
                    </div>
                    <div>
                        <label class="block text-black text-sm mb-1">Status <span class="text-red-500">*</span></label>
                        <select v-model="form.status"
                            class="w-full border border-black/20 rounded px-3 py-2 text-sm text-black focus:outline-none focus:border-black"
                            required>
                            <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
                        </select>
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
