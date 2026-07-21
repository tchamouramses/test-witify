<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-xl shadow-sm shadow-indigo-950/15"
    >
        <AppLogoIcon class="size-9" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left">
        <span
            class="truncate text-sm leading-tight font-semibold tracking-tight"
        >
            Linvara
        </span>
        <span class="truncate text-[10px] leading-tight text-muted-foreground">
            Inventory control
        </span>
    </div>
</template>
