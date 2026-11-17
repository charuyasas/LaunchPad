<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';
import DockButton from './DockButton.vue';

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const options: { value: Appearance; label: string; icon: typeof Sun }[] = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <DockButton label="Theme">
                <Moon v-if="resolvedAppearance === 'dark'" />
                <Sun v-else />
            </DockButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent side="top" :side-offset="12" class="w-36">
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                @select="updateAppearance(option.value)"
            >
                <component :is="option.icon" />
                {{ option.label }}
                <Check
                    v-if="appearance === option.value"
                    class="ml-auto text-primary"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
