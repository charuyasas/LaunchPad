<template>
    <!-- Soft blurred wallpaper behind every page, like a desktop background. -->
    <div
        aria-hidden="true"
        class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-[var(--wallpaper-base)]"
    >
        <div
            class="absolute -top-[20%] -left-[10%] size-[60vmax] rounded-full bg-[var(--wallpaper-1)] opacity-80 blur-[120px]"
        />
        <div
            class="absolute top-[10%] -right-[15%] size-[55vmax] rounded-full bg-[var(--wallpaper-2)] opacity-70 blur-[120px]"
        />
        <div
            class="absolute -bottom-[25%] left-[15%] size-[50vmax] rounded-full bg-[var(--wallpaper-3)] opacity-60 blur-[120px]"
        />
        <div
            class="absolute -right-[5%] -bottom-[10%] size-[35vmax] rounded-full bg-[var(--wallpaper-4)] opacity-60 blur-[110px]"
        />
    </div>
</template>
