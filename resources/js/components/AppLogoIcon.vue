<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        fill="none"
        aria-hidden="true"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient
                id="linvara-gradient"
                x1="7"
                y1="5"
                x2="41"
                y2="43"
                gradientUnits="userSpaceOnUse"
            >
                <stop stop-color="#4f46e5" />
                <stop offset="1" stop-color="#0d9488" />
            </linearGradient>
        </defs>
        <path
            d="M12 3h24a9 9 0 0 1 9 9v24a9 9 0 0 1-9 9H12a9 9 0 0 1-9-9V12a9 9 0 0 1 9-9Z"
            fill="url(#linvara-gradient)"
        />
        <path
            d="m13.5 16.75 10.5-6 10.5 6v14.5l-10.5 6-10.5-6v-14.5Z"
            stroke="white"
            stroke-width="2.2"
            stroke-linejoin="round"
        />
        <path
            d="m13.5 16.75 10.5 6 10.5-6M24 22.75v14.5"
            stroke="white"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity=".72"
        />
        <path
            d="m17.75 27.25 3.5 3.5 8.75-10"
            stroke="white"
            stroke-width="2.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
