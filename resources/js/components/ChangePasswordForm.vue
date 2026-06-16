<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function handleSimpan() {
    form.put('/profil/ubah-kata-sandi', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            alert('Kata sandi berhasil diubah!');
        }
    });
}
</script>

<template>
    <form @submit.prevent="handleSimpan" class="max-w-md">
        <div class="mb-4">
            <label for="kata-sandi-saat-ini" class="text-black text-sm">
                Kata Sandi Saat Ini <span class="text-red-500">*</span>
            </label>
            <input id="kata-sandi-saat-ini" v-model="form.current_password" type="password" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.current_password" class="text-red-500 text-xs mt-1">{{ form.errors.current_password
                }}</div>
        </div>

        <div class="mb-4">
            <label for="kata-sandi-baru" class="text-black text-sm">
                Kata Sandi Baru <span class="text-red-500">*</span>
            </label>
            <input id="kata-sandi-baru" v-model="form.password" type="password" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
        </div>

        <div class="mb-4">
            <label for="konfirmasi-kata-sandi-baru" class="text-black text-sm">
                Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span>
            </label>
            <input id="konfirmasi-kata-sandi-baru" v-model="form.password_confirmation" type="password" required
                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
        </div>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing" class="px-6 py-2 text-sm">
                Simpan
            </Button>
        </div>
    </form>
</template>
