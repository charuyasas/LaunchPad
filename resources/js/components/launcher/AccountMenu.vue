<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import DockButton from './DockButton.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger as-child>
            <DockButton label="Account">
                <span
                    class="flex size-7 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground"
                >
                    {{ getInitials(user.name) }}
                </span>
            </DockButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            side="top"
            align="end"
            :side-offset="12"
            class="w-60"
        >
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
