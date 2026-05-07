<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    count: number;
    iconUrl?: string; // We can pass icon path, but for simplicity we can just use slots or a specific icon based on title
    href?: string;
}>();
</script>

<template>
    <div class="bg-white border border-black/10 rounded-lg p-5 flex items-center gap-4 transition-colors hover:border-black/30">
        <div class="w-10 h-10 bg-yellow rounded flex items-center justify-center shrink-0">
            <slot name="icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1F1B1E" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" />
                    <path d="M8 21h8" />
                    <path d="M12 17v4" />
                </svg>
            </slot>
        </div>
        <div class="flex-1">
            <div class="text-black text-2xl font-bold">{{ count }}</div>
            <component :is="href ? Link : 'div'" :href="href" class="text-black text-xs" :class="{'hover:underline': href}">
                {{ title }}
            </component>
        </div>
    </div>
</template>
