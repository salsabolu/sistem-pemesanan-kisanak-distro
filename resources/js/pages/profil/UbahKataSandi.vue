<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import ProfileSidebar from '@/components/ProfileSidebar.vue';
import ChangePasswordForm from '@/components/ChangePasswordForm.vue';
import Alert from '@/components/Alert.vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success as string | undefined);
const alertState = ref({ show: false, message: '', type: 'success' as const });

watch(flashSuccess, (msg) => {
    if (msg) {
        alertState.value = { show: true, message: msg, type: 'success' };
    }
}, { immediate: true });


</script>

<template>

    <Head title="Ubah Kata Sandi" />

    <Alert v-model:show="alertState.show" :message="alertState.message" :type="alertState.type" />

    <div class="bg-white min-h-screen flex flex-col">
        <!-- Header -->
        <PublicHeader />

        <!-- Main Content -->
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

        <!-- Footer -->
        <Footer />

    </div>
</template>
