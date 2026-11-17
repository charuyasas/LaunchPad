<script setup lang="ts">
import { Lock } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Wallpaper from '@/components/launcher/Wallpaper.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative isolate flex min-h-svh flex-col items-center justify-center p-4 md:p-10"
    >
        <Wallpaper />

        <div class="relative w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center gap-4 text-center">
                <span
                    class="flex size-16 items-center justify-center rounded-[26%] bg-primary text-primary-foreground shadow-xl ring-1 shadow-primary/40 ring-white/30"
                >
                    <AppLogoIcon class="size-9" />
                </span>
                <div class="space-y-1 text-label">
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-foreground/70">
                        {{ description }}
                    </p>
                </div>
            </div>

            <div class="rounded-3xl glass-strong p-6">
                <slot />
            </div>

            <p
                class="mt-6 flex items-center justify-center gap-1.5 text-xs text-foreground/60 text-label"
            >
                <Lock class="size-3.5" />
                Private dashboard · authorised users only
            </p>
        </div>
    </div>
</template>
