<script setup lang="ts">
interface Props {
    variant?: 'primary' | 'secondary';
}

withDefaults(defineProps<Props>(), {
    variant: 'primary',
});
</script>

<template>
    <button
        type="button"
        class="btn inline-flex items-center justify-center transition-all duration-200"
        :class="{
            'btn-primary': variant === 'primary',
            // 'btn-secondary': variant === 'secondary',
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
}

.btn-primary {
    color: black;
    outline: black solid 1px;
}

.btn-primary:hover {
    color: white;
    background-color: black;
    /* transform: translateY(-2px); */
}

.btn-primary:active {
    transform: translateY(0);
}

/* .btn-secondary {
    background-color: transparent;
    color: black;
    border: 1px solid black;
}

.btn-secondary:hover {
    background-color: black;
    color: white;
} */
</style>
