<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue';
import { PhCheckCircle, PhXCircle, PhInfo, PhX } from '@phosphor-icons/vue';

const props = defineProps<{
    show: boolean;
    message: string;
    type?: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}>();

const emit = defineEmits<{
    (e: 'update:show', value: boolean): void;
}>();

let timer: ReturnType<typeof setTimeout> | null = null;

function closeAlert() {
    emit('update:show', false);
}

function startTimer() {
    if (timer) clearTimeout(timer);
    if (props.duration !== 0) {
        timer = setTimeout(() => {
            closeAlert();
        }, props.duration || 3000);
    }
}

watch(() => props.show, (newVal) => {
    if (newVal) {
        startTimer();
    } else {
        if (timer) clearTimeout(timer);
    }
});

onMounted(() => {
    if (props.show) startTimer();
});

onUnmounted(() => {
    if (timer) clearTimeout(timer);
});
</script>

<template>
    <transition enter-active-class="transition ease-out duration-300"
        enter-from-class="transform translate-y-[-20px] opacity-0" enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200" leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform translate-y-[-20px] opacity-0">
        <div v-if="show"
            class="fixed top-8 left-5/6 -translate-x-1/2 z-[100] flex items-start gap-3 px-4 py-3 shadow-[0_8px_24px_rgba(0,0,0,0.12)] border min-w-[320px] max-w-md bg-white"
            :class="{
                'border-green-500': type === 'success',
                'border-red-500': type === 'error',
                'border-yellow': type === 'warning',
                'border-black/20': type === 'info' || !type
            }">

            <div class="shrink-0 mt-0.5">
                <PhCheckCircle v-if="type === 'success'" :size="20" class="text-green-600" weight="fill" />
                <PhXCircle v-else-if="type === 'error'" :size="20" class="text-red-600" weight="fill" />
                <PhInfo v-else :size="20" class="text-black/50" weight="fill" />
            </div>

            <div class="flex-1 text-sm text-black">
                {{ message }}
            </div>

            <button type="button" @click="closeAlert" class="shrink-0 text-black/40 hover:text-black transition-colors">
                <PhX :size="18" />
            </button>
        </div>
    </transition>
</template>
