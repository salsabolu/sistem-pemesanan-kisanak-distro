<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import ProfileSidebar from '@/components/ProfileSidebar.vue';
import ChangePasswordForm from '@/components/ChangePasswordForm.vue';


</script>

<template>

    <Head title="Ubah Kata Sandi" />

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
