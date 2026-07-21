<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Lock,
    Package,
    ShoppingCart,
    Warehouse,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCurrency } from '@/lib/currency';
import { dashboard } from '@/routes';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as ordersIndex, show as orderShow } from '@/routes/orders';
import { index as warehousesIndex } from '@/routes/warehouses';
import type {
    DashboardFulfillment,
    DashboardInventoryAlert,
    DashboardOrder,
    DashboardSummary,
    DashboardWarehouse,
} from '@/types';

const props = defineProps<{
    summary: DashboardSummary;
    fulfillment: DashboardFulfillment;
    warehouses: DashboardWarehouse[];
    recent_orders: DashboardOrder[];
    inventory_alerts: DashboardInventoryAlert[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const numberFormatter = new Intl.NumberFormat('en-US');
const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
});

const metricCards = computed(() => [
    {
        label: 'Physical stock',
        value: formatNumber(props.summary.physical_stock),
        detail: `Across ${formatNumber(props.summary.products_count)} products`,
        icon: Package,
        iconClass: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    },
    {
        label: 'Reserved stock',
        value: formatNumber(props.summary.reserved_stock),
        detail: `${props.fulfillment.rate}% of ordered units secured`,
        icon: Lock,
        iconClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    {
        label: 'Available stock',
        value: formatNumber(props.summary.available_stock),
        detail: 'Ready for new allocations',
        icon: CheckCircle2,
        iconClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        label: 'Orders',
        value: formatNumber(props.summary.orders_count),
        detail: `${formatNumber(props.fulfillment.pending_units)} units still to reserve`,
        icon: ShoppingCart,
        iconClass: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
]);

function formatNumber(value: number): string {
    return numberFormatter.format(value);
}

function formatDate(value: string): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

function statusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <section
            class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-indigo-500/12 via-background to-emerald-500/10 p-5 shadow-sm md:p-7"
        >
            <div
                class="pointer-events-none absolute -top-20 -right-16 size-56 rounded-full bg-indigo-500/10 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-24 left-1/3 size-56 rounded-full bg-emerald-500/10 blur-3xl"
            />

            <div
                class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="max-w-2xl space-y-2">
                    <h1
                        class="text-2xl font-semibold tracking-tight md:text-3xl"
                    >
                        Everything that needs your attention, at a glance.
                    </h1>
                    <p class="text-sm leading-6 text-muted-foreground">
                        Track stock availability, reservation coverage and the
                        latest orders across your entire warehouse network.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" class="bg-background/80" as-child>
                        <Link :href="inventoryIndex()"> Review inventory </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="ordersIndex()">
                            View orders
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="metric in metricCards" :key="metric.label">
                <CardContent class="flex items-start justify-between p-5">
                    <div class="space-y-1.5">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ metric.label }}
                        </p>
                        <p
                            class="text-2xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ metric.value }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ metric.detail }}
                        </p>
                    </div>
                    <div :class="['rounded-xl p-2.5', metric.iconClass]">
                        <component :is="metric.icon" class="size-5" />
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <CardHeader
                    class="flex flex-row items-start justify-between gap-4"
                >
                    <div class="space-y-1.5">
                        <CardTitle>Warehouse availability</CardTitle>
                        <CardDescription>
                            Physical, reserved and immediately available units.
                        </CardDescription>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="warehousesIndex()">
                            All warehouses
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div
                        v-for="warehouse in warehouses"
                        :key="warehouse.id"
                        class="space-y-2"
                    >
                        <div
                            class="flex items-start justify-between gap-4 text-sm"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted"
                                >
                                    <Warehouse class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ warehouse.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ warehouse.code }}
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-medium tabular-nums">
                                    {{
                                        formatNumber(warehouse.available_stock)
                                    }}
                                    available
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatNumber(warehouse.reserved_stock) }}
                                    reserved of
                                    {{ formatNumber(warehouse.physical_stock) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="h-2 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            :aria-label="`${warehouse.name} reserved stock`"
                            :aria-valuenow="warehouse.reserved_percentage"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-teal-500 transition-all"
                                :style="{
                                    width: `${warehouse.reserved_percentage}%`,
                                }"
                            />
                        </div>
                    </div>

                    <p
                        v-if="warehouses.length === 0"
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        No warehouses have been configured yet.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Reservation coverage</CardTitle>
                    <CardDescription>
                        Progress against all units currently ordered.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p
                                class="text-4xl font-semibold tracking-tight tabular-nums"
                            >
                                {{ fulfillment.rate }}%
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                overall coverage
                            </p>
                        </div>
                        <span
                            class="flex size-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                        >
                            <Lock class="size-5" />
                        </span>
                    </div>

                    <div
                        class="h-3 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        aria-label="Overall reservation coverage"
                        :aria-valuenow="fulfillment.rate"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-teal-500 transition-all"
                            :style="{ width: `${fulfillment.rate}%` }"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border bg-muted/30 p-3">
                            <p class="text-xs text-muted-foreground">
                                Reserved
                            </p>
                            <p class="mt-1 font-semibold tabular-nums">
                                {{ formatNumber(fulfillment.reserved_units) }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/30 p-3">
                            <p class="text-xs text-muted-foreground">
                                Still needed
                            </p>
                            <p class="mt-1 font-semibold tabular-nums">
                                {{ formatNumber(fulfillment.pending_units) }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-muted-foreground">
                        Based on
                        {{ formatNumber(fulfillment.ordered_units) }} ordered
                        units across all confirmed orders.
                    </p>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <Card class="overflow-hidden xl:col-span-2">
                <CardHeader
                    class="flex flex-row items-start justify-between gap-4"
                >
                    <div class="space-y-1.5">
                        <CardTitle>Recent orders</CardTitle>
                        <CardDescription>
                            The latest commitments and their reservation
                            progress.
                        </CardDescription>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="ordersIndex()">
                            View all
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="px-0 pb-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-y bg-muted/40 text-left text-xs text-muted-foreground"
                                >
                                    <th class="px-6 py-3 font-medium">Order</th>
                                    <th class="px-4 py-3 font-medium">
                                        Coverage
                                    </th>
                                    <th class="px-4 py-3 font-medium">Date</th>
                                    <th
                                        class="px-6 py-3 text-right font-medium"
                                    >
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in recent_orders"
                                    :key="order.id"
                                    class="border-b last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-6 py-4">
                                        <Link
                                            :href="orderShow(order.id)"
                                            class="font-medium hover:underline"
                                        >
                                            {{ order.number }}
                                        </Link>
                                        <p
                                            class="mt-0.5 max-w-52 truncate text-xs text-muted-foreground"
                                        >
                                            {{ order.customer_name }} ·
                                            {{ order.lines_count }} lines
                                        </p>
                                    </td>
                                    <td class="min-w-40 px-4 py-4">
                                        <div
                                            class="flex items-center justify-between gap-3 text-xs"
                                        >
                                            <span
                                                class="font-medium tabular-nums"
                                            >
                                                {{ order.fulfillment_rate }}%
                                            </span>
                                            <Badge variant="secondary">
                                                {{ statusLabel(order.status) }}
                                            </Badge>
                                        </div>
                                        <div
                                            class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full bg-primary"
                                                :style="{
                                                    width: `${order.fulfillment_rate}%`,
                                                }"
                                            />
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-4 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right font-medium whitespace-nowrap tabular-nums"
                                    >
                                        {{ formatCurrency(order.total) }}
                                    </td>
                                </tr>
                                <tr v-if="recent_orders.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-6 py-12 text-center text-muted-foreground"
                                    >
                                        No orders have been created yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1.5">
                            <CardTitle>Inventory alerts</CardTitle>
                            <CardDescription>
                                Products with 10 or fewer available units.
                            </CardDescription>
                        </div>
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                        >
                            <AlertTriangle class="size-4" />
                        </span>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="alert in inventory_alerts"
                        :key="alert.id"
                        class="flex items-center justify-between gap-3 rounded-xl border p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ alert.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ alert.sku }} ·
                                {{ formatNumber(alert.reserved_stock) }}
                                reserved
                            </p>
                        </div>
                        <Badge
                            :variant="
                                alert.severity === 'critical'
                                    ? 'destructive'
                                    : 'secondary'
                            "
                        >
                            {{ formatNumber(alert.available_stock) }} left
                        </Badge>
                    </div>

                    <div
                        v-if="inventory_alerts.length === 0"
                        class="flex flex-col items-center gap-2 py-8 text-center"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="size-5" />
                        </span>
                        <p class="text-sm font-medium">
                            Inventory looks healthy
                        </p>
                        <p class="text-xs text-muted-foreground">
                            No product is below the alert threshold.
                        </p>
                    </div>

                    <Button
                        v-if="inventory_alerts.length > 0"
                        variant="outline"
                        class="mt-2 w-full"
                        as-child
                    >
                        <Link :href="inventoryIndex()">
                            Open inventory
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
