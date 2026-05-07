<script setup lang="ts">
import { ref } from 'vue';
import { PhEye, PhEyeSlash } from '@phosphor-icons/vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'close'): void }>();

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    nama: '',
    email: '',
    whatsapp: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            emit('close');
        },
    });
}
</script>

<template>
    <div
        v-if="props.open"
        class="fixed inset-0 z-50 flex items-center justify-center"
        @click="emit('close')"
    >
        <div class="absolute inset-0 bg-black/40" />

        <div
            class="bg-black relative w-full max-w-md px-10 py-8 text-white" @click.stop>
            <h2 class="text-2xl font-medium" style="font-size: 30px;">Registrasi</h2>

            <form @submit.prevent="submit" class="mt-4 space-y-3">
                <div>
                    <input
                        v-model="form.nama"
                        type="text"
                        autocomplete="name"
                        placeholder="Nama"
                        class="bg-white text-black w-full px-3 py-2 text-sm"
                        required
                    />
                    <div v-if="form.errors.nama" class="text-red-500 text-xs mt-1">{{ form.errors.nama }}</div>
                </div>

                <div>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="Email"
                        class="bg-white text-black w-full px-3 py-2 text-sm"
                        required
                    />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <input
                        v-model="form.whatsapp"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="Nomor WhatsApp"
                        class="bg-white text-black w-full px-3 py-2 text-sm"
                        required
                    />
                    <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp }}</div>
                </div>

                <div>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="Kata Sandi"
                            class="bg-white text-black w-full px-3 py-2 pr-10 text-sm"
                            required
                        />
                        <button
                            type="button"
                            class="text-white-disable absolute right-2 top-1/2 -translate-y-1/2"
                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                            @click="showPassword = !showPassword"
                        >
                            <PhEye v-if="!showPassword" :size="18" />
                            <PhEyeSlash v-else :size="18" />
                        </button>
                    </div>
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                </div>

                <div>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="Konfirmasi Kata Sandi"
                            class="bg-white text-black w-full px-3 py-2 pr-10 text-sm"
                            required
                        />
                        <button
                            type="button"
                            class="text-white-disable absolute right-2 top-1/2 -translate-y-1/2"
                            :aria-label="showPasswordConfirm ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                        >
                            <PhEye v-if="!showPasswordConfirm" :size="18" />
                            <PhEyeSlash v-else :size="18" />
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="bg-white text-black mt-8 w-full py-2 text-sm"
                    :disabled="form.processing"
                >
                    Registrasi
                </button>
            </form>
        </div>
    </div>
</template>
