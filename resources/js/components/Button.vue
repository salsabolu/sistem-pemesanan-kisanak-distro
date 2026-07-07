<script setup lang="ts">
defineOptions({ inheritAttrs: false });

interface Props {
    variant?: 'primary' | 'option' | 'quantity' | 'solid' | 'light' | 'select' | 'icon';
    active?: boolean;
    label?: string;       // button text or quantity label
    showLabel?: boolean;  // toggle label for quantity variant
    modelValue?: number | string;  // for quantity variant or select
    min?: number;
    max?: number;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    active: false,
    showLabel: true,
    modelValue: 1,
    min: 1,
    max: 9999,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
}>();

function decrement() {
    if (typeof props.modelValue === 'number' && props.modelValue > props.min) {
        emit('update:modelValue', props.modelValue - 1);
    }
}

function increment() {
    if (typeof props.modelValue === 'number' && props.modelValue < props.max) {
        emit('update:modelValue', props.modelValue + 1);
    }
}
</script>

<template>
    <!-- 1. Quantity Selector -->
    <div v-if="variant === 'quantity'" class="flex flex-col gap-2" v-bind="$attrs">
        <div v-if="label && showLabel" class="text-sm font-medium uppercase text-black dark:text-white select-none">
            {{ label }}
        </div>
        <div class="inline-flex items-center w-fit border border-black dark:border-white text-black dark:text-white transition-colors duration-200">
            <button type="button"
                class="h-8 w-8 flex items-center justify-center font-bold hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors cursor-pointer select-none"
                :disabled="Number(modelValue) <= min" :class="{ 'opacity-50 cursor-not-allowed': Number(modelValue) <= min }"
                @click.stop="decrement">
                -
            </button>
            <div class="h-8 w-10 text-center text-sm leading-8 select-none font-medium border-l border-r border-black dark:border-white transition-colors duration-200">
                {{ modelValue }}
            </div>
            <button type="button"
                class="h-8 w-8 flex items-center justify-center font-bold hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors cursor-pointer select-none"
                :disabled="Number(modelValue) >= max" :class="{ 'opacity-50 cursor-not-allowed': Number(modelValue) >= max }"
                @click.stop="increment">
                +
            </button>
        </div>
    </div>

    <!-- 2. Option Selection Button -->
    <button v-else-if="variant === 'option'" type="button" v-bind="$attrs"
        class="px-3 py-1 text-xs uppercase transition-all duration-200 cursor-pointer select-none font-medium border"
        :class="active 
            ? 'bg-black text-white border-black dark:bg-white dark:text-black dark:border-white' 
            : 'bg-transparent text-black border-black dark:text-white dark:border-white hover:bg-gray-100 dark:hover:bg-gray-800'">
        <slot>{{ label }}</slot>
    </button>

    <!-- 3. Select Dropdown -->
    <select v-else-if="variant === 'select'"
        :value="modelValue"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        v-bind="$attrs"
        class="w-full text-center bg-transparent cursor-pointer px-2 py-1.5 text-xs uppercase transition-all duration-200 font-medium border border-black text-black dark:border-white dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 appearance-none focus:outline-none"
    >
        <slot></slot>
    </select>

    <!-- 4. Primary / Solid / Light / Icon CTA Button -->
    <button v-else type="button" v-bind="$attrs"
        class="inline-flex items-center justify-center transition-all duration-200 cursor-pointer select-none"
        :class="{
            'px-3 py-1.5 text-sm text-black border border-black bg-transparent hover:bg-black hover:text-white dark:text-white dark:border-white dark:hover:bg-white dark:hover:text-black': variant === 'primary',
            'px-3 py-1.5 text-sm bg-black text-white border border-black hover:bg-gray-800 dark:bg-white dark:text-black dark:border-white dark:hover:bg-gray-200': variant === 'solid',
            'px-3 py-1.5 text-sm bg-white text-black border border-black hover:bg-gray-100 dark:bg-[#16162a] dark:text-white dark:border-white dark:hover:bg-gray-800': variant === 'light',
            'p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400': variant === 'icon',
        }">
        <slot>{{ label }}</slot>
    </button>
</template>
