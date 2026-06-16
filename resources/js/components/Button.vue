<script setup lang="ts">
interface Props {
    variant?: 'primary' | 'option' | 'quantity' | 'solid' | 'light';
    active?: boolean;
    label?: string; // used for button text if slot is empty, or quantity label
    showLabel?: boolean; // toggle label for quantity
    modelValue?: number; // for quantity variant
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
    (e: 'click', event: MouseEvent): void;
    (e: 'update:modelValue', value: number): void;
}>();

function decrement() {
    if (props.modelValue > props.min) {
        emit('update:modelValue', props.modelValue - 1);
    }
}

function increment() {
    if (props.modelValue < props.max) {
        emit('update:modelValue', props.modelValue + 1);
    }
}
</script>

<template>
    <!-- 1. Quantity Selector -->
    <div v-if="variant === 'quantity'" class="flex flex-col gap-2">
        <div v-if="label && showLabel" class="text-sm font-medium uppercase text-black select-none">
            {{ label }}
        </div>
        <div class="inline-flex items-center w-fit" style="border: 1px solid black">
            <button type="button"
                class="h-8 w-8 flex items-center justify-center font-bold text-black hover:bg-black hover:text-white transition-colors cursor-pointer select-none"
                :disabled="modelValue <= min" :class="{ 'opacity-50 cursor-not-allowed': modelValue <= min }"
                @click="decrement">
                -
            </button>
            <div class="h-8 w-10 text-center text-sm leading-8 text-black select-none font-medium"
                style="border-left: 1px solid black; border-right: 1px solid black">
                {{ modelValue }}
            </div>
            <button type="button"
                class="h-8 w-8 flex items-center justify-center font-bold text-black hover:bg-black hover:text-white transition-colors cursor-pointer select-none"
                :disabled="modelValue >= max" :class="{ 'opacity-50 cursor-not-allowed': modelValue >= max }"
                @click="increment">
                +
            </button>
        </div>
    </div>

    <!-- 2. Option Selection Button -->
    <button v-else-if="variant === 'option'" type="button" v-bind="$attrs"
        class="px-3 py-1 text-xs uppercase transition-all duration-200 cursor-pointer select-none font-medium" :style="{
            border: '1px solid black',
            backgroundColor: active ? 'black' : 'transparent',
            color: active ? 'white' : 'black',
        }">
        <slot>{{ label }}</slot>
    </button>

    <!-- 3. Primary / Solid / Light CTA Button -->
    <button v-else type="button" v-bind="$attrs"
        class="btn inline-flex items-center justify-center transition-all duration-200 cursor-pointer select-none"
        :class="{
            'btn-primary': variant === 'primary',
            'btn-solid': variant === 'solid',
            'btn-light': variant === 'light',
        }">
        <slot>{{ label }}</slot>
    </button>
</template>

<style scoped>
.btn {
    padding: 4px 12px;
    font-size: smaller;
    font-weight: normal;
}

.btn-primary {
    color: black;
    outline: black solid 1px;
    background-color: transparent;
}

.btn-primary:hover {
    color: white;
    background-color: black;
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-solid {
    background-color: black;
    color: white;
}

.btn-solid:hover {
    background-color: #1a1a1a;
}

.btn-light {
    background-color: white;
    color: black;
}

.btn-light:hover {
    background-color: rgba(255, 255, 255, 0.9);
}
</style>
