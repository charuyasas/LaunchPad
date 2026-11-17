<script setup lang="ts">
const runTests = defineModel<boolean>({ required: true });

const options = [
    { value: true, label: 'Yes' },
    { value: false, label: 'No' },
];
</script>

<template>
    <div class="grid gap-2">
        <span class="text-sm font-medium">Run tests before deploy</span>
        <div
            class="inline-flex w-fit rounded-lg bg-muted p-0.5"
            role="radiogroup"
            aria-label="Run tests before deploy"
        >
            <button
                v-for="option in options"
                :key="option.label"
                type="button"
                role="radio"
                :aria-checked="runTests === option.value"
                class="rounded-md px-4 py-1 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :class="
                    runTests === option.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="runTests = option.value"
            >
                {{ option.label }}
            </button>
        </div>
        <slot />
    </div>
</template>
