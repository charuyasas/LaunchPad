<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, UserRound } from '@lucide/vue';
import AppDock from '@/components/launcher/AppDock.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

const items: NavItem[] = [
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Password', href: editPassword(), icon: KeyRound },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto w-full max-w-2xl pt-4 pb-28">
        <div class="overflow-hidden rounded-3xl glass-strong">
            <div
                class="border-b border-foreground/10 px-5 pt-5 md:px-7 md:pt-6"
            >
                <h1 class="text-xl font-semibold tracking-tight">Account</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage how you sign in to LaunchPad.
                </p>

                <nav class="mt-5 flex gap-1" aria-label="Account settings">
                    <Link
                        v-for="item in items"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 pb-3 text-sm font-medium transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.title }}
                    </Link>
                </nav>
            </div>

            <section class="p-5 md:p-7">
                <slot />
            </section>
        </div>
    </div>

    <AppDock />
</template>
