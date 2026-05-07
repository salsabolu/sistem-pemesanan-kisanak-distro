<script setup lang="ts">
interface Props {
    src: string;
    alt?: string;
    caption: string;
}

const props = defineProps<Props>();
</script>

<template>
    <figure class="w-full">
        <div class="aspect-square w-full overflow-hidden">
            <img
                :src="props.src"
                :alt="props.alt ?? props.caption"
                class="h-full w-full object-cover"
            />
        </div>
        <figcaption class="text-black text-12px font-normal uppercase mt-1">
            {{ props.caption }}
        </figcaption>
    </figure>
</template>
