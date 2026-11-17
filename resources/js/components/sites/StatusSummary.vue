<script setup lang="ts">
import { computed } from 'vue';
import type { SiteCard, StatusFilter } from './types';

const props = defineProps<{
    sites: SiteCard[];
}>();

const filter = defineModel<StatusFilter>({ required: true });

const segments = computed(() => {
    const count = (status: string) =>
        props.sites.filter((site) => site.status === status).length;

    return [
        {
            key: 'all' as const,
            label: 'All',
            value: props.sites.length,
            dot: '',
        },
        {
            key: 'live' as const,
            label: 'Live',
            value: count('live'),
            dot: 'bg-live',
        },
        {
            key: 'slow' as const,
            label: 'Slow',
            value: count('slow'),
            dot: 'bg-slow',
        },
        {
            key: 'down' as const,
            label: 'Down',
            value: count('down'),
            dot: 'bg-down',
        },
    ];
});

function select(key: StatusFilter) {
    filter.value = filter.value === key && key !== 'all' ? 'all' : key;
}
</script>

<template>
    <div
        class="inline-flex items-center gap-0.5 rounded-full glass p-1"
        role="group"
        aria-label="Filter by status"
    >
        <button
            v-for="segment in segments"
            :key="segment.key"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-sm"
            :class="
                filter === segment.key
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-foreground/70 hover:text-foreground'
            "
            :aria-pressed="filter === segment.key"
            @click="select(segment.key)"
        >
            <span
                v-if="segment.dot"
                class="size-2 rounded-full"
                :class="segment.dot"
            />
            {{ segment.label }}
            <span
                class="tabular-nums"
                :class="{
                    'text-down': segment.key === 'down' && segment.value > 0,
                    'text-slow': segment.key === 'slow' && segment.value > 0,
                    'opacity-60': segment.value === 0 && segment.key !== 'all',
                }"
            >
                {{ segment.value }}
            </span>
        </button>
    </div>
</template>
