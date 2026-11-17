<script setup lang="ts">
import type { SiteStatus } from './types';

defineProps<{
    status: SiteStatus;
}>();

const styles: Record<SiteStatus, { pill: string; dot: string; label: string }> =
    {
        live: {
            pill: 'bg-live/12 text-live ring-live/25',
            dot: 'bg-live',
            label: 'Live',
        },
        slow: {
            pill: 'bg-slow/14 text-slow ring-slow/30',
            dot: 'bg-slow',
            label: 'Slow',
        },
        down: {
            pill: 'bg-down/12 text-down ring-down/30',
            dot: 'bg-down',
            label: 'Down',
        },
    };
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="styles[status].pill"
    >
        <span class="relative flex size-1.5">
            <span
                v-if="status !== 'live'"
                class="absolute inline-flex size-full animate-ping rounded-full opacity-60"
                :class="styles[status].dot"
            />
            <span
                class="relative inline-flex size-1.5 rounded-full"
                :class="styles[status].dot"
            />
        </span>
        {{ styles[status].label }}
    </span>
</template>
