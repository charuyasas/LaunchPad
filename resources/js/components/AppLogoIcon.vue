<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <!-- LaunchPad mark: a rocket lifting off its pad -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="currentColor"
        aria-hidden="true"
        v-bind="$attrs"
    >
        <path
            fill-rule="evenodd"
            d="M16 2.5c4.2 3.1 6.4 7.7 6.4 13V21H9.6v-5.5c0-5.3 2.2-9.9 6.4-13Zm0 8.5a2.3 2.3 0 1 0 0 4.6 2.3 2.3 0 0 0 0-4.6Z"
        />
        <path
            d="M9.6 16.8 6 20.4V24h3.6v-7.2ZM22.4 16.8l3.6 3.6V24h-3.6v-7.2Z"
        />
        <path d="M13.4 22.6h5.2L16 27.4l-2.6-4.8Z" opacity=".7" />
        <rect x="5" y="28.4" width="22" height="1.6" rx=".8" opacity=".5" />
    </svg>
</template>
