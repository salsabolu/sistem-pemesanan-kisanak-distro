<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    PhMagnifyingGlass,
    PhShoppingCartSimple,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { ref, watch } from 'vue';
import LoginModal from '@/components/LoginModal.vue';
import RegisterModal from '@/components/RegisterModal.vue';
import NavIcon from '@/components/NavIcon.vue';
import NavButton from '@/components/NavButton.vue';

const props = defineProps<{
    showNav?: boolean;
    activeNav?: 'beranda' | 'katalog' | 'galeri';
}>();

const emit = defineEmits<{
    (e: 'cart-click'): void;
    (e: 'search-click'): void;
}>();

const page = usePage<any>();

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

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.openLogin) openLogin();
        if (flash?.openRegister) openRegister();
    },
    { immediate: true },
);

defineExpose({
    openLogin,
    openRegister
});
</script>

<template>
    <header class="mx-auto w-full px-30 pt-6">
        <div class="grid grid-cols-3 items-center">
            <div />

            <div class="flex items-center justify-center">
                <Link href="/" aria-label="Beranda">
                    <img src="/images/logo/logo-dark.png" alt="Kisanak Distro" class="h-12 w-auto" />
                </Link>
            </div>

            <div class="flex items-center justify-end gap-1">
                <NavIcon :icon="PhMagnifyingGlass" ariaLabel="Cari" @click="$emit('search-click')" />
                <NavIcon :icon="PhShoppingCartSimple" ariaLabel="Keranjang" @click="$emit('cart-click')" />
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

        <nav v-if="showNav" class="mt-5 flex items-center justify-center gap-20 text-sm ">
            <NavButton href="/" :variant="activeNav === 'beranda' ? 'active' : 'default'">BERANDA</NavButton>
            <NavButton href="/katalog" :variant="activeNav === 'katalog' ? 'active' : 'default'">KATALOG</NavButton>
            <NavButton href="/galeri" :variant="activeNav === 'galeri' ? 'active' : 'default'">GALERI</NavButton>
        </nav>

        <LoginModal :open="isLoginOpen" @close="closeLogin" @open-register="openRegister" />
        <RegisterModal :open="isRegisterOpen" @close="closeRegister" />
    </header>
</template>
