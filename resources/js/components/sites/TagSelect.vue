<script setup lang="ts">
import { Loader2 } from '@lucide/vue';
import { Label } from '@/components/ui/label';

defineProps<{
    tags: string[];
    loading: boolean;
    error: string | null;
    /** Marks this tag as "running now" in the list. */
    currentVersion?: string | null;
}>();

const selected = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-2">
        <Label for="deploy-tag">Tag</Label>
        <div
            v-if="loading"
            class="flex h-9 items-center gap-2 rounded-md border px-3 text-sm text-muted-foreground"
        >
            <Loader2 class="size-4 animate-spin" />
            Loading tags from GitHub…
        </div>
        <p
            v-else-if="error"
            class="rounded-md border border-down/40 bg-down/10 px-3 py-2 text-sm text-down"
            role="alert"
        >
            {{ error }}
        </p>
        <select
            v-else
            id="deploy-tag"
            v-model="selected"
            class="h-9 w-full rounded-md border border-input bg-transparent px-3 font-mono text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
        >
            <option v-for="(tag, index) in tags" :key="tag" :value="tag">
                {{ tag }}{{ index === 0 ? ' (latest)' : ''
                }}{{ tag === currentVersion ? ' · running now' : '' }}
            </option>
        </select>
        <slot />
    </div>
</template>
