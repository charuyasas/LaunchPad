<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import Wallpaper from '@/components/launcher/Wallpaper.vue';
import { Toaster } from '@/components/ui/sonner';
import { dashboard } from '@/routes';
</script>

<template>
    <div class="relative isolate flex min-h-svh flex-col">
        <Wallpaper />

        <!-- Small wordmark in the corner; navigation lives in the dock. -->
        <div class="px-4 pt-4 sm:px-6 sm:pt-5">
            <Link
                :href="dashboard()"
                class="inline-flex rounded-lg outline-none text-label focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <AppLogo />
            </Link>
        </div>

        <main
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col px-4 pt-2 sm:px-6"
        >
            <slot />
        </main>

        <Toaster position="top-right" rich-colors close-button />
    </div>
</template>
