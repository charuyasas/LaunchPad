<script setup lang="ts">
defineProps<{
    hint: string;
    error?: string;
}>();
</script>

<template>
    <p
        class="text-xs"
        :class="error ? 'text-destructive-foreground' : 'text-muted-foreground'"
        :role="error ? 'alert' : undefined"
    >
        {{ error ?? hint }}
    </p>
</template>
