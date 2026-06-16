<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
    class?: string;
    separator?: string;
    uppercase?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    separator: 'chevron',
    uppercase: false,
});
</script>

<template>
    <Breadcrumb :class="[props.class, uppercase ? 'uppercase' : '']">
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link :href="item.href ?? '#'">{{
                                item.title
                                }}</Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1">
                    <template v-if="separator !== 'chevron'">
                        {{ separator }}
                    </template>
                </BreadcrumbSeparator>
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>

<style scoped>
:deep(ol),
:deep(li),
:deep(a),
:deep(span) {
    color: black !important;
}
</style>
