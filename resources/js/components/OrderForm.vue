<script setup lang="ts">
import { PhCopy } from '@phosphor-icons/vue';
import { ref } from 'vue';
import Button from '@/components/Button.vue';

type DistroType = {
    rekening_bca: string;
    rekening_bri: string;
};

const props = defineProps<{
    totalText: string;
    distro?: DistroType;
}>();

const emit = defineEmits<{
    (e: 'checkout', payload: { tenggatWaktu: string, buktiPembayaran: File | null, rekening: 'BCA' | 'BRI' }): void;
}>();

const tenggatWaktu = ref('');
const buktiPembayaran = ref<File | null>(null);
const rekening = ref<'BCA' | 'BRI'>('BCA');

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        buktiPembayaran.value = target.files[0];
    } else {
        buktiPembayaran.value = null;
    }
}

function handleCheckoutClick() {
    emit('checkout', { tenggatWaktu: tenggatWaktu.value, buktiPembayaran: buktiPembayaran.value, rekening: rekening.value });
}

function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Nomor rekening berhasil disalin!');
    }).catch(err => {
        console.error('Gagal menyalin text: ', err);
    });
}
</script>

<template>
    <div class="bg-white shadow-md p-6 flex flex-col">
        <div class="mt-4">
            <div class="text-black text-xs uppercase">Tenggat Waktu</div>
            <input v-model="tenggatWaktu" type="date"
                class="mt-2 w-full border border-black px-3 py-2 text-sm text-black" />
        </div>

        <div class="mt-4">
            <div class="text-black text-xs uppercase">Bukti Pembayaran</div>
            <input type="file" accept="image/png, image/jpeg, image/jpg, application/pdf" @change="handleFileChange"
                class="mt-2 w-full border border-black px-3 py-2 text-sm text-black file:mr-4 file:py-1 file:px-2 file:border-0 file:text-xs file:bg-black file:text-white" />
        </div>

        <div v-if="distro" class="mt-4 p-3 bg-black/5 text-xs text-black border border-black/10">
            <div class="font-medium uppercase mb-2">Pilih Rekening Transfer:</div>
            
            <div class="flex items-center justify-between py-1 border-b border-black/5">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="rekening" value="BCA" name="rekening" class="accent-black" />
                    BCA: {{ distro.rekening_bca }}
                </label>
                <button type="button" @click="copyToClipboard(distro.rekening_bca)"
                    class="text-black hover:opacity-75 focus:outline-none" title="Salin nomor rekening BCA">
                    <PhCopy :size="16" />
                </button>
            </div>
            
            <div class="flex items-center justify-between py-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="rekening" value="BRI" name="rekening" class="accent-black" />
                    BRI: {{ distro.rekening_bri }}
                </label>
                <button type="button" @click="copyToClipboard(distro.rekening_bri)"
                    class="text-black hover:opacity-75 focus:outline-none" title="Salin nomor rekening BRI">
                    <PhCopy :size="16" />
                </button>
            </div>
        </div>

        <div class="mt-4 text-black text-[10px] italic">*Pajak sudah termasuk</div>

        <div class="text-black mt-2 flex items-center justify-between text-sm">
            <div class="uppercase">Total Harga:</div>
            <div>{{ totalText }}</div>
        </div>

        <Button type="button" class="mt-auto w-full py-2 text-sm" @click="handleCheckoutClick">
            Checkout
        </Button>
    </div>
</template>
