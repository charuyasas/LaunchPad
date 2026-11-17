<script setup lang="ts">
import { Primitive } from 'reka-ui';
import type { PrimitiveProps } from 'reka-ui';

withDefaults(
    defineProps<
        Pick<PrimitiveProps, 'as'> & {
            /** Shown as a tooltip above the icon and used as the accessible name. */
            label: string;
            /** Highlight as the primary dock action. */
            primary?: boolean;
            active?: boolean;
        }
    >(),
    { as: 'button' },
);
</script>

<template>
    <Primitive
        :as="as"
        :type="as === 'button' ? 'button' : undefined"
        :aria-label="label"
        class="group/dock relative flex size-11 items-center justify-center rounded-xl transition-all duration-200 ease-out outline-none hover:-translate-y-1 hover:scale-110 focus-visible:ring-[3px] focus-visible:ring-ring/60 disabled:pointer-events-none disabled:opacity-50 [&_svg:not([class*='size-'])]:size-5"
        :class="
            primary
                ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/40'
                : 'text-foreground/80 hover:bg-white/50 hover:text-foreground dark:hover:bg-white/10'
        "
    >
        <slot />
        <span
            class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 rounded-md bg-foreground/85 px-2 py-1 text-xs font-medium whitespace-nowrap text-background opacity-0 shadow transition-opacity group-hover/dock:opacity-100 group-focus-visible/dock:opacity-100"
        >
            {{ label }}
        </span>
        <span
            v-if="active"
            class="absolute -bottom-1.5 left-1/2 size-1 -translate-x-1/2 rounded-full bg-foreground/70"
        />
    </Primitive>
</template>
