<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import AccountMenu from './AccountMenu.vue';
import DockButton from './DockButton.vue';
import DockThemeMenu from './DockThemeMenu.vue';

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <!-- Floating dock: navigation and page actions live at the bottom of the screen. -->
    <nav
        class="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center gap-1 rounded-2xl glass p-1.5 sm:bottom-6"
        aria-label="Dock"
    >
        <DockButton
            :as="Link"
            :href="dashboard()"
            label="Sites"
            :active="isCurrentUrl(dashboard())"
        >
            <LayoutGrid />
        </DockButton>

        <template v-if="$slots.default">
            <span class="mx-1 h-7 w-px bg-foreground/15" aria-hidden="true" />
            <slot />
        </template>

        <span class="mx-1 h-7 w-px bg-foreground/15" aria-hidden="true" />
        <DockThemeMenu />
        <AccountMenu />
    </nav>
</template>
