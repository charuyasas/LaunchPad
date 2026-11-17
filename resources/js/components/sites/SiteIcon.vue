<script setup lang="ts">
import { Hourglass } from '@lucide/vue';
import {
    PopoverAnchor,
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { computed, ref, watch } from 'vue';
import SiteQuickLook from './SiteQuickLook.vue';
import type { SiteCard } from './types';

const props = defineProps<{
    site: SiteCard;
    checking: boolean;
    deployEnabled: boolean;
}>();

defineEmits<{
    refresh: [];
    edit: [];
    delete: [];
    deploy: [];
}>();

// The info card opens by clicking (or right-clicking) the icon.
const quickLookOpen = ref(false);

// Fall back to a lettered icon when there is no screenshot or it fails to load.
const imageFailed = ref(false);
watch(
    () => props.site.preview_url,
    () => (imageFailed.value = false),
);
const showPreview = computed(
    () => props.site.preview_url !== null && !imageFailed.value,
);

const initials = computed(() =>
    props.site.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join(''),
);

// A stable colour per site so lettered icons are easy to tell apart.
const hue = computed(
    () =>
        [...props.site.name].reduce(
            (sum, char) => sum + char.charCodeAt(0),
            0,
        ) % 360,
);
</script>

<template>
    <div
        class="group/app relative flex min-w-0 flex-col items-center gap-2.5"
        :data-status="site.status"
    >
        <PopoverRoot v-model:open="quickLookOpen">
            <PopoverAnchor as-child>
                <div
                    class="relative"
                    @contextmenu.prevent="quickLookOpen = true"
                >
                    <!-- The icon itself: click to open the site's info card -->
                    <PopoverTrigger as-child>
                        <button
                            type="button"
                            :aria-label="`${site.name} (${site.status}): show details`"
                            class="relative block size-20 cursor-pointer overflow-hidden rounded-[26%] bg-card shadow-[0_8px_24px_-8px_rgb(0_0_0/0.35)] ring-1 ring-black/5 transition-transform duration-200 ease-out outline-none group-hover/app:-translate-y-1 group-hover/app:scale-[1.04] focus-visible:ring-[3px] focus-visible:ring-ring active:scale-95 data-[state=open]:-translate-y-1 data-[state=open]:ring-[3px] data-[state=open]:ring-primary/70 sm:size-24 lg:size-28 dark:ring-white/10"
                        >
                            <img
                                v-if="showPreview"
                                :src="site.preview_url!"
                                alt=""
                                class="size-full object-cover object-top"
                                :class="{
                                    'opacity-70 grayscale':
                                        site.status === 'down',
                                }"
                                @error="imageFailed = true"
                            />
                            <span
                                v-else
                                class="flex size-full items-center justify-center text-2xl font-semibold text-white sm:text-3xl"
                                :style="{
                                    background: `linear-gradient(145deg, oklch(0.7 0.15 ${hue}), oklch(0.5 0.17 ${hue + 40}))`,
                                }"
                            >
                                {{ initials }}
                            </span>

                            <!-- Checking: dim the icon behind a spinning ring -->
                            <span
                                v-if="checking"
                                class="absolute inset-0 flex items-center justify-center bg-black/35 backdrop-blur-[2px]"
                            >
                                <svg
                                    viewBox="0 0 36 36"
                                    class="size-9 animate-spin text-white"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-opacity=".3"
                                        stroke-width="3.5"
                                    />
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3.5"
                                        stroke-linecap="round"
                                        stroke-dasharray="28 100"
                                    />
                                </svg>
                                <span class="sr-only"
                                    >Checking {{ site.name }}</span
                                >
                            </span>
                        </button>
                    </PopoverTrigger>

                    <!-- Status badge, like a notification badge on an app icon -->
                    <span
                        v-if="site.status === 'down'"
                        class="pointer-events-none absolute -top-1.5 -right-1.5 flex size-6 items-center justify-center rounded-full bg-down text-xs font-bold text-white shadow-md ring-2 ring-white/80 dark:ring-black/40"
                        title="Down"
                    >
                        !
                    </span>
                    <span
                        v-else-if="site.status === 'slow'"
                        class="pointer-events-none absolute -top-1.5 -right-1.5 flex size-6 items-center justify-center rounded-full bg-slow text-white shadow-md ring-2 ring-white/80 dark:ring-black/40"
                        title="Slow"
                    >
                        <Hourglass class="size-3.5" />
                    </span>
                    <span
                        v-else
                        class="pointer-events-none absolute -top-0.5 -right-0.5 size-3.5 rounded-full bg-live shadow ring-2 ring-white/90 dark:ring-black/40"
                        title="Live"
                    />
                </div>
            </PopoverAnchor>

            <PopoverPortal>
                <PopoverContent
                    side="right"
                    align="start"
                    :side-offset="14"
                    :collision-padding="{
                        top: 16,
                        right: 16,
                        bottom: 96,
                        left: 16,
                    }"
                    class="z-50 w-80 origin-(--reka-popover-content-transform-origin) rounded-2xl glass-strong bg-popover/95! text-popover-foreground outline-none data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
                >
                    <SiteQuickLook
                        :site="site"
                        :checking="checking"
                        :show-preview="showPreview"
                        :deploy-enabled="deployEnabled"
                        @refresh="$emit('refresh')"
                        @edit="$emit('edit')"
                        @delete="$emit('delete')"
                        @deploy="$emit('deploy')"
                    />
                </PopoverContent>
            </PopoverPortal>
        </PopoverRoot>

        <!-- Label under the icon -->
        <div class="w-full min-w-0 text-center">
            <p
                class="truncate text-[13px] leading-tight font-medium text-foreground text-label"
                :title="site.name"
            >
                {{ site.name }}
            </p>
            <p
                class="mt-0.5 truncate font-mono text-[11px] text-foreground/60 text-label"
            >
                {{
                    site.version ??
                    (site.version_path ? 'version unknown' : '—')
                }}
            </p>
        </div>
    </div>
</template>
