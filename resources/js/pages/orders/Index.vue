<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppPagination from '@/components/AppPagination.vue';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/currency';
import { index, show } from '@/routes/orders';
import type { OrderSummary, PaginatedData } from '@/types';

defineProps<{
    orders: PaginatedData<OrderSummary>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Orders',
                href: index(),
            },
        ],
    },
});

function ordersPage(page: number) {
    return index({ mergeQuery: { page } });
}
</script>

<template>
    <Head title="Orders" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 bg-muted/50 text-left text-muted-foreground"
                    >
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Lines</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="order in orders.data"
                        :key="order.id"
                        class="border-b border-sidebar-border/70 last:border-0 hover:bg-muted/50"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(order.id)"
                                class="font-medium hover:underline"
                            >
                                {{ order.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ order.customer_name }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{
                                order.status
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ order.lines_count }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatCurrency(order.total) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :pagination="orders" :href-for-page="ordersPage" />
    </div>
</template>
