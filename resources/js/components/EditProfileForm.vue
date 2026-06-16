<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';

const page = usePage();
const user = page.props.auth.user as any;

const form = useForm({
    nama: user?.nama || '',
    email: user?.email || '',
    whatsapp: user?.whatsapp || '',
    alamat: user?.alamat || '',
});

function handleEdit() {
    form.put('/profil/edit', {
        preserveScroll: true
    });
}
</script>

<template>
    <form @submit.prevent="handleEdit" class="max-w-md">
        <div class="mb-4">
            <label for="nama" class="text-black text-sm">
                Nama <span class="text-red-500">*</span>
            </label>
            <input id="nama" v-model="form.nama" type="text" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</div>
        </div>

        <div class="mb-4">
            <label for="email" class="text-black text-sm">
                Email <span class="text-red-500">*</span>
            </label>
            <input id="email" v-model="form.email" type="email" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}
            </div>
        </div>

        <div class="mb-4">
            <label for="nomor-whatsapp" class="text-black text-sm">
                Nomor WhatsApp <span class="text-red-500">*</span>
            </label>
            <input id="nomor-whatsapp" v-model="form.whatsapp" type="text" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp }}</div>
        </div>

        <div class="mb-4">
            <label for="alamat" class="text-black text-sm">
                Alamat <span class="text-red-500">*</span>
            </label>
            <textarea id="alamat" v-model="form.alamat" rows="4"
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.alamat" class="text-red-500 text-xs mt-1">{{ form.errors.alamat }}
            </div>
        </div>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing" class="px-6 py-2 text-sm">
                Simpan Perubahan
            </Button>
        </div>
    </form>
</template>
