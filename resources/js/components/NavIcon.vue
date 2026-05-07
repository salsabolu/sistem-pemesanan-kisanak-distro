<script setup lang="ts">
import type { Component } from 'vue';
import { ref } from 'vue';

interface Props {
    icon: Component;
    ariaLabel: string;
    size?: number;
    variant?: 'default';
}

const props = withDefaults(defineProps<Props>(), {
    size: 20,
    variant: 'default',
});

const isHovering = ref(false);
</script>

<template>
    <button
        type="button"
        class="btn relative inline-flex h-10 w-10 items-center justify-center rounded-full transition-colors"
        :class="{
            'btn-default': props.variant === 'default',
        }"
        :aria-label="props.ariaLabel"
        :title="props.ariaLabel"
        @mouseenter="isHovering = true"
        @mouseleave="isHovering = false"
    >
        <component
            :is="props.icon"
            :size="props.size"
            :weight="isHovering ? 'bold' : undefined"
        />

        <span class="tooltip" role="tooltip">{{ props.ariaLabel }}</span>
    </button>
</template>

<style scoped>
.btn {
    color: black;
}

.btn:hover {
    color: black;
}

.tooltip {
    position: absolute;
    left: 50%;
    bottom: calc(90%);
    transform: translateX(-50%);
    padding: 6px 10px;
    border-radius: 9999px;
    font-size: 12px;
    line-height: 1;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: opacity 120ms ease, transform 120ms ease;
    background: var(--kisanak-black, #000);
    color: var(--kisanak-cream, #fff);
}

.btn:hover .tooltip,
.btn:focus-visible .tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0px);
}
</style>