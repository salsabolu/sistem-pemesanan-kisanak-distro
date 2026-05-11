<script setup lang="ts">
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue';
import { ref, nextTick } from 'vue';

const props = defineProps<{
    modelValue?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'search', value: string): void;
    (e: 'close'): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const localValue = ref(props.modelValue ?? '');

function onInput(event: Event) {
    const target = event.target as HTMLInputElement;
    localValue.value = target.value;
    emit('update:modelValue', target.value);
}

function onSearch() {
    emit('search', localValue.value);
}

function close() {
    emit('close');
}

function focus() {
    nextTick(() => inputRef.value?.focus());
}

defineExpose({ focus });
</script>

<template>
    <!-- Full-width search bar overlay -->
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-[72px] px-4" @click.self="close">
        <div class="w-full max-w-2xl bg-white shadow-lg border border-black/10">
            <div class="flex items-center px-4">
                <PhMagnifyingGlass :size="18" class="text-black/40 shrink-0" />
                <input
                    ref="inputRef"
                    :value="localValue"
                    @input="onInput"
                    @keyup.enter="onSearch"
                    @keyup.esc="close"
                    type="text"
                    :placeholder="placeholder || 'Cari produk...'"
                    class="flex-1 py-3 px-3 text-sm text-black bg-transparent focus:outline-none"
                />
                <button type="button" @click="onSearch" class="text-black/50 hover:text-black px-2">
                    Cari
                </button>
                <button type="button" @click="close" class="text-black/40 hover:text-black pl-2 pr-1">
                    <PhX :size="18" />
                </button>
            </div>
        </div>
    </div>
</template>
