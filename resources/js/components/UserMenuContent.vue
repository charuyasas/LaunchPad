<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { KeyRound, LogOut, UserRound } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import type { User } from '@/types';

defineProps<{
    user: User;
}>();

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="px-2 py-1.5 font-normal">
        <p class="truncate text-sm font-medium">{{ user.name }}</p>
        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem as-child>
            <Link class="w-full cursor-pointer" :href="editProfile()" prefetch>
                <UserRound />
                Profile
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem as-child>
            <Link class="w-full cursor-pointer" :href="editPassword()" prefetch>
                <KeyRound />
                Password
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem as-child>
        <Link
            class="w-full cursor-pointer"
            :href="logout()"
            as="button"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
