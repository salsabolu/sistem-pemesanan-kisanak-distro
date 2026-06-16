<script setup lang="ts">
import Button from '@/components/Button.vue';

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
                <Button type="button" class="px-6 py-2 text-sm"
                    @click="emit('close')">
                    Batal
                </Button>
                <Button type="button" variant="solid" class="px-6 py-2 text-sm"
                    @click="emit('confirm')">
                    Pesan Sekarang
                </Button>
            </div>
        </div>
    </div>
</template>
