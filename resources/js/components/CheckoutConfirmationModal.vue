<script setup lang="ts">
defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="emit('close')" />
        <div class="relative bg-white text-black p-6 w-full max-w-sm mx-4" style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15)">
            <h3 class="text-lg font-medium uppercase">Konfirmasi Pesanan</h3>
            <p class="mt-1 text-sm">Apakah data pesanan sudah sesuai?</p>
            <div class="mt-4 flex gap-4 justify-end">
                <button type="button" class="px-6 py-2 text-sm border border-black bg-white text-black hover:bg-black/5"
                    @click="emit('close')">
                    Batal
                </button>
                <button type="button" class="px-6 py-2 text-sm bg-black text-white hover:bg-black/90"
                    @click="emit('confirm')">
                    Pesan Sekarang
                </button>
            </div>
        </div>
    </div>
</template>
