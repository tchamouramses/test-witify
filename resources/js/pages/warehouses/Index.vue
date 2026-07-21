<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppPagination from '@/components/AppPagination.vue';
import { index } from '@/routes/warehouses';
import type { PaginatedData, WarehouseSummary } from '@/types';

defineProps<{
    warehouses: PaginatedData<WarehouseSummary>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Warehouses',
                href: index(),
            },
        ],
    },
});

function warehousesPage(page: number) {
    return index({ mergeQuery: { page } });
}
</script>

<template>
    <Head title="Warehouses" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 bg-muted/50 text-left text-muted-foreground"
                    >
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Products in stock
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Units in stock
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="warehouse in warehouses.data"
                        :key="warehouse.id"
                        class="border-b border-sidebar-border/70 last:border-0 hover:bg-muted/50"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ warehouse.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ warehouse.code }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ warehouse.products_in_stock }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ warehouse.units_in_stock }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination
            :pagination="warehouses"
            :href-for-page="warehousesPage"
        />
    </div>
</template>
