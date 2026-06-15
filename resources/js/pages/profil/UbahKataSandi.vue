<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { ref } from 'vue';
import Footer from '@/components/Footer.vue';
import LoginModal from '@/components/LoginModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import RegisterModal from '@/components/RegisterModal.vue';
import ProfileSidebar from '@/components/ProfileSidebar.vue';
import ChangePasswordForm from '@/components/ChangePasswordForm.vue';

const isProfileMenuOpen = ref(false);
const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);

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
                    <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" />
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
                <ProfileSidebar />

                <!-- Content -->
                <div class="flex-1">
                    <ChangePasswordForm />
                </div>
            </div>
        </main>

        <Footer />

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />
    </div>
</template>
