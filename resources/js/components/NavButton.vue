<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    variant?: 'default' | 'active';
    href?: string;
}

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        class="btn inline-flex items-center justify-center transition-all duration-200"
        :class="{
            'btn-primary': variant === 'default',
            'btn-active': variant === 'active',
        }"
    >
        <slot />
    </Link>

    <button
        v-else
        type="button"
        class="btn inline-flex items-center justify-center transition-all duration-200"
        :class="{
            'btn-primary': variant === 'default',
            'btn-active': variant === 'active',
        }"
    >
        <slot />
    </button>
</template>

<style scoped>
.btn {
    padding: 4px 12px;
    font-size: 16px;
    font-weight: normal;
    color: black;
}

.btn-default {
    color: black;
}

.btn-default:hover {
    color: black;
    /* transform: translateY(-2px); */
}

.btn-default:active {
    transform: translateY(0);
}

.btn-active {
    color: black;
    border-bottom: black solid 1.5px;
}

.btn-active:hover {
    color: black;
    border-bottom: black solid 3px;
}
</style>