<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import NavIcon from '@/components/NavIcon.vue';
import LoginModal from '@/components/LoginModal.vue';
import RegisterModal from '@/components/RegisterModal.vue';
import CartDrawer from '@/components/CartDrawer.vue';
import Footer from '@/components/Footer.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);
const isConfirmOpen = ref(false);

const cartPreview = {
    productName: 'Kaos Polos Dewasa Cotton Combed 30s',
    color: 'Putih',
    size: 'M',
    quantity: 2,
    subtotal: 'Rp80.000',
    imageSrc: '/images/kaos-1.png',
};

function openLogin() {
    isProfileMenuOpen.value = false;
    isRegisterOpen.value = false;
    isLoginOpen.value = true;
}

function openRegister() {
    isProfileMenuOpen.value = false;
    isLoginOpen.value = false;
    isRegisterOpen.value = true;
}

function closeLogin() {
    isLoginOpen.value = false;
}

function closeRegister() {
    isRegisterOpen.value = false;
}

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

    <Head title="Ubah Kata Sandi" />

    <div class="bg-white min-h-screen flex flex-col">
        <header class="mx-auto w-full px-30 pt-6">
            <div class="grid grid-cols-3 items-center">
                <div />

                <div class="flex items-center justify-center">
                    <Link href="/" aria-label="Beranda">
                        <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="h-12 w-auto" />
                    </Link>
                </div>

                <div class="flex items-center justify-end gap-1">
                    <NavIcon :icon="PhMagnifyingGlass" ariaLabel="Cari" />
                    <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" @click="isConfirmOpen = true" />
                    <div class="relative">
                        <NavIcon :icon="PhUserCircle" ariaLabel="Profil" :size="22"
                            @click="isProfileMenuOpen = !isProfileMenuOpen" />

                        <div v-if="isProfileMenuOpen" class="fixed inset-0 z-40" @click="isProfileMenuOpen = false" />

                        <div v-if="isProfileMenuOpen"
                            class="absolute right-0 top-full z-50 mt-2 w-28 overflow-hidden bg-white text-sm"
                            style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12)" @click.stop>
                            <template v-if="!$page.props.auth.user">
                                <button type="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5"
                                    @click="openLogin">
                                    Masuk
                                </button>
                            </template>
                            <template v-else>
                                <Link href="/profil/riwayat-pesanan"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Profil
                                </Link>
                                <Link href="/logout" method="post" as="button"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full px-30 pb-16 pt-10">
            <div class="flex gap-10">
                <!-- Sidebar -->
                <aside class="w-56 shrink-0">
                    <div class="text-black text-sm font-bold uppercase">Profil</div>
                    <nav class="mt-4 flex flex-col gap-3">
                        <Link href="/profil/riwayat-pesanan" class="text-black text-sm uppercase hover:underline">
                            Riwayat Pesanan
                        </Link>
                        <Link href="/profil/edit" class="text-black text-sm uppercase hover:underline">
                            Edit Profil
                        </Link>
                        <Link href="/profil/ubah-kata-sandi"
                            class="text-black text-sm uppercase hover:underline font-medium">
                            Ubah Kata Sandi
                        </Link>
                    </nav>
                    <div class="mt-auto pt-60">
                        <Link href="/logout" method="post" as="button" type="button"
                            class="text-red-500 text-sm uppercase hover:underline">Keluar</Link>
                    </div>
                </aside>

                <!-- Content -->
                <div class="flex-1">
                    <form @submit.prevent="handleSimpan" class="max-w-md">
                        <div class="mb-4">
                            <label for="kata-sandi-saat-ini" class="text-black text-sm">
                                Kata Sandi Saat Ini <span class="text-red-500">*</span>
                            </label>
                            <input id="kata-sandi-saat-ini" v-model="form.current_password" type="password" required
                                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
                            <div v-if="form.errors.current_password" class="text-red-500 text-xs mt-1">{{
                                form.errors.current_password }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="kata-sandi-baru" class="text-black text-sm">
                                Kata Sandi Baru <span class="text-red-500">*</span>
                            </label>
                            <input id="kata-sandi-baru" v-model="form.password" type="password" required
                                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password
                                }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="konfirmasi-kata-sandi-baru" class="text-black text-sm">
                                Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span>
                            </label>
                            <input id="konfirmasi-kata-sandi-baru" v-model="form.password_confirmation" type="password"
                                required
                                class="mt-1 w-full border border-black/20 bg-transparent px-3 py-2 text-sm text-black focus:outline-none focus:border-black" />
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing"
                                class="border border-black px-6 py-2 text-sm text-black hover:bg-black/5 disabled:opacity-50">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />

        <CartDrawer :open="isConfirmOpen" :productName="cartPreview.productName" :color="cartPreview.color"
            :size="cartPreview.size" :quantity="cartPreview.quantity" :subtotal="cartPreview.subtotal"
            :imageSrc="cartPreview.imageSrc" @close="isConfirmOpen = false" />
    </div>
</template>
