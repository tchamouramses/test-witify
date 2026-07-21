<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col overflow-hidden bg-muted p-10 text-white lg:flex"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-950 to-teal-950"
            />
            <div
                class="absolute -right-28 -bottom-28 size-96 rounded-full bg-teal-400/15 blur-3xl"
            />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppLogoIcon class="mr-3 size-10" />
                {{ name }}
            </Link>
            <div class="relative z-20 mt-auto max-w-md">
                <p class="text-3xl leading-tight font-semibold tracking-tight">
                    Inventory clarity,<br />by design.
                </p>
                <p class="mt-4 text-sm leading-relaxed text-white/65">
                    Orders, warehouses and reservations aligned in one precise
                    workspace.
                </p>
            </div>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
