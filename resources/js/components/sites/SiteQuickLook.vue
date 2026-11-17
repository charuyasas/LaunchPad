<script setup lang="ts">
import {
    ArrowUpRight,
    ImageOff,
    Loader2,
    Pencil,
    RefreshCw,
    Rocket,
    Trash2,
    UploadCloud,
} from '@lucide/vue';
import { useTimeAgo } from '@vueuse/core';
import type { UseTimeAgoMessages } from '@vueuse/core';
import { PopoverClose } from 'reka-ui';
import { computed } from 'vue';
import StatusPill from './StatusPill.vue';
import { displayUrl } from './types';
import type { SiteCard } from './types';

const props = defineProps<{
    site: SiteCard;
    checking: boolean;
    showPreview: boolean;
    /** Deploying is configured in LaunchPad (repository and token set). */
    deployEnabled: boolean;
}>();

defineEmits<{
    refresh: [];
    edit: [];
    delete: [];
    deploy: [];
}>();

// Short relative times so they fit a small tile: "now", "4m ago", "2h ago".
const short = (unit: string) => (n: number) => `${n}${unit}`;
const messages: UseTimeAgoMessages = {
    justNow: 'now',
    past: (n) => (/\d/.test(n) ? `${n} ago` : n),
    future: (n) => (/\d/.test(n) ? `in ${n}` : n),
    month: short('mo'),
    year: short('y'),
    day: short('d'),
    week: short('w'),
    hour: short('h'),
    minute: short('m'),
    second: short('s'),
    invalid: '',
};
const checkedAgo = useTimeAgo(
    computed(() => props.site.checked_at ?? new Date(0)),
    { messages, updateInterval: 30_000 },
);

// "420 ms" under a second, "1.8 s" above, so the tile stays short.
const responseTime = computed(() => {
    const ms = props.site.response_ms;

    if (ms === null || props.site.status === 'down') {
        return null;
    }

    return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`;
});

const responseColour = {
    live: 'text-live',
    slow: 'text-slow',
    down: 'text-muted-foreground',
} as const;

const lastDeployAgo = useTimeAgo(
    computed(() => props.site.last_deploy?.at ?? new Date(0)),
    { messages, updateInterval: 30_000 },
);

const iconButton =
    'flex size-9 shrink-0 items-center justify-center rounded-xl bg-foreground/5 text-foreground/80 transition-colors outline-none hover:bg-foreground/10 hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-40';
</script>

<template>
    <div class="flex flex-col gap-3 p-2.5">
        <!-- Screenshot, with the status pinned on top -->
        <div
            class="relative aspect-[16/10] overflow-hidden rounded-xl bg-muted ring-1 ring-black/5 dark:ring-white/10"
        >
            <img
                v-if="showPreview"
                :src="site.preview_url!"
                :alt="`${site.name} homepage`"
                class="size-full object-cover object-top"
            />
            <div
                v-else
                class="flex size-full flex-col items-center justify-center gap-1.5 text-xs text-muted-foreground"
            >
                <ImageOff class="size-5 opacity-60" />
                Preview unavailable
            </div>

            <div class="absolute top-2 right-2">
                <span
                    v-if="checking"
                    class="inline-flex items-center gap-1.5 rounded-full bg-background/90 px-2 py-0.5 text-xs font-medium shadow-sm"
                >
                    <Loader2 class="size-3 animate-spin" />
                    Checking
                </span>
                <StatusPill
                    v-else
                    :status="site.status"
                    class="bg-background/90 shadow-sm backdrop-blur"
                />
            </div>
        </div>

        <!-- Name and address -->
        <div class="min-w-0 px-1">
            <p class="truncate text-[15px] leading-tight font-semibold">
                {{ site.name }}
            </p>
            <p
                class="mt-1 truncate font-mono text-xs text-muted-foreground"
                :title="site.base_url"
            >
                {{ displayUrl(site.base_url) }}
            </p>
        </div>

        <!-- At-a-glance tiles -->
        <dl class="grid grid-cols-3 gap-1.5">
            <div class="min-w-0 rounded-xl bg-foreground/5 px-2.5 py-2">
                <dt
                    class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                >
                    Version
                </dt>
                <dd
                    class="mt-0.5 truncate font-mono text-sm font-medium"
                    :class="{ 'text-muted-foreground': !site.version }"
                    :title="
                        site.version ??
                        (site.version_path
                            ? `Nothing returned from ${site.version_path}`
                            : 'No version endpoint configured')
                    "
                >
                    {{ site.version ?? '—' }}
                </dd>
            </div>
            <div class="min-w-0 rounded-xl bg-foreground/5 px-2.5 py-2">
                <dt
                    class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                >
                    Response
                </dt>
                <dd
                    class="mt-0.5 truncate text-sm font-semibold tabular-nums"
                    :class="responseColour[site.status]"
                    :title="
                        site.status === 'down'
                            ? 'No answer on the last check'
                            : site.response_ms !== null
                              ? `Homepage answered in ${site.response_ms} ms`
                              : 'Not measured yet'
                    "
                >
                    {{
                        responseTime ??
                        (site.status === 'down' ? 'no answer' : '—')
                    }}
                </dd>
            </div>
            <div class="min-w-0 rounded-xl bg-foreground/5 px-2.5 py-2">
                <dt
                    class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                >
                    Checked
                </dt>
                <dd class="mt-0.5 truncate text-sm font-medium">
                    {{ site.checked_at ? checkedAgo : 'never' }}
                </dd>
            </div>
        </dl>

        <!-- Deploy: only for Paylance sites that have a client folder -->
        <div
            v-if="deployEnabled && site.client"
            class="flex items-center justify-between gap-2 rounded-xl bg-primary/8 px-2.5 py-2 ring-1 ring-primary/15"
        >
            <div class="min-w-0 text-xs">
                <p class="truncate">
                    <span class="text-muted-foreground">Client</span>
                    <span class="ml-1 font-mono font-medium">{{
                        site.client
                    }}</span>
                </p>
                <p
                    class="mt-0.5 truncate text-muted-foreground"
                    :title="
                        site.last_deploy
                            ? `${site.last_deploy.ref} deployed by ${site.last_deploy.by} (${site.last_deploy.run_tests ? 'with' : 'without'} tests) at ${new Date(site.last_deploy.at).toLocaleString()}`
                            : undefined
                    "
                >
                    <template v-if="site.last_deploy">
                        Last:
                        <span class="font-mono text-foreground">{{
                            site.last_deploy.ref
                        }}</span>
                        · {{ lastDeployAgo }}
                    </template>
                    <template v-else>Not deployed from LaunchPad yet</template>
                </p>
            </div>
            <PopoverClose as-child>
                <button
                    type="button"
                    class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg bg-background px-2.5 text-xs font-semibold text-primary shadow-sm ring-1 ring-primary/20 transition-colors outline-none hover:bg-primary hover:text-primary-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    @click="$emit('deploy')"
                >
                    <UploadCloud class="size-3.5" />
                    Deploy…
                </button>
            </PopoverClose>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1.5">
            <a
                :href="site.base_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-3 text-sm font-medium text-primary-foreground shadow-md shadow-primary/30 transition-colors outline-none hover:bg-primary/90 focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <Rocket class="size-4" />
                Launch
                <ArrowUpRight class="size-3.5 opacity-70" />
            </a>
            <PopoverClose as-child>
                <button
                    type="button"
                    :class="iconButton"
                    :disabled="checking"
                    title="Refresh"
                    aria-label="Refresh"
                    @click="$emit('refresh')"
                >
                    <RefreshCw class="size-4" />
                </button>
            </PopoverClose>
            <PopoverClose as-child>
                <button
                    type="button"
                    :class="iconButton"
                    title="Edit"
                    aria-label="Edit"
                    @click="$emit('edit')"
                >
                    <Pencil class="size-4" />
                </button>
            </PopoverClose>
            <PopoverClose as-child>
                <button
                    type="button"
                    :class="[iconButton, 'hover:bg-down/15 hover:text-down']"
                    title="Delete"
                    aria-label="Delete"
                    @click="$emit('delete')"
                >
                    <Trash2 class="size-4" />
                </button>
            </PopoverClose>
        </div>
    </div>
</template>
