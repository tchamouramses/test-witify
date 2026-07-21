<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { PaginationMeta } from '@/types';

const props = defineProps<{
    pagination: PaginationMeta;
    hrefForPage: (page: number) => NonNullable<InertiaLinkProps['href']>;
}>();

const currentPage = computed(() =>
    Math.min(
        Math.max(props.pagination.current_page, 1),
        props.pagination.last_page,
    ),
);

const visiblePages = computed(() => {
    const firstPage = Math.max(1, currentPage.value - 2);
    const lastPage = Math.min(
        props.pagination.last_page,
        currentPage.value + 2,
    );

    return Array.from(
        { length: lastPage - firstPage + 1 },
        (_, index) => firstPage + index,
    );
});
</script>

<template>
    <nav
        v-if="pagination.last_page > 1"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Pagination"
    >
        <p class="text-sm text-muted-foreground">
            Showing
            <span class="font-medium text-foreground">{{
                pagination.from ?? 0
            }}</span>
            to
            <span class="font-medium text-foreground">{{
                pagination.to ?? 0
            }}</span>
            of
            <span class="font-medium text-foreground">{{
                pagination.total
            }}</span>
        </p>

        <div class="flex items-center gap-1.5">
            <Button
                v-if="currentPage === 1"
                variant="outline"
                size="icon"
                disabled
                aria-label="Previous page"
            >
                <ChevronLeft class="size-4" />
            </Button>
            <Button v-else variant="outline" size="icon" as-child>
                <Link
                    :href="hrefForPage(currentPage - 1)"
                    aria-label="Previous page"
                    prefetch
                >
                    <ChevronLeft class="size-4" />
                </Link>
            </Button>

            <Button
                v-for="page in visiblePages"
                :key="page"
                :variant="page === currentPage ? 'default' : 'outline'"
                size="icon"
                as-child
            >
                <Link
                    :href="hrefForPage(page)"
                    :aria-label="`Page ${page}`"
                    :aria-current="page === currentPage ? 'page' : undefined"
                    prefetch
                >
                    {{ page }}
                </Link>
            </Button>

            <Button
                v-if="currentPage === pagination.last_page"
                variant="outline"
                size="icon"
                disabled
                aria-label="Next page"
            >
                <ChevronRight class="size-4" />
            </Button>
            <Button v-else variant="outline" size="icon" as-child>
                <Link
                    :href="hrefForPage(currentPage + 1)"
                    aria-label="Next page"
                    prefetch
                >
                    <ChevronRight class="size-4" />
                </Link>
            </Button>
        </div>
    </nav>
</template>
