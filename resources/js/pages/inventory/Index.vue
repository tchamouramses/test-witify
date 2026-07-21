<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppPagination from '@/components/AppPagination.vue';
import InventoryAdjustmentDialog from '@/components/InventoryAdjustmentDialog.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/inventory';
import type { PaginatedData, ProductInventory, Warehouse } from '@/types';

defineProps<{
    warehouses: Warehouse[];
    products: PaginatedData<ProductInventory>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inventory',
                href: index(),
            },
        ],
    },
});

const isDialogOpen = ref(false);
const selectedProduct = ref<ProductInventory | null>(null);

function openAdjustmentDialog(product: ProductInventory) {
    selectedProduct.value = product;
    isDialogOpen.value = true;
}

function totalQuantity(product: ProductInventory): number {
    return Object.values(product.quantities).reduce(
        (total, quantity) => total + quantity,
        0,
    );
}

function inventoryPage(page: number) {
    return index({ mergeQuery: { page } });
}
</script>

<template>
    <Head title="Inventory" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 bg-muted/50 text-left text-muted-foreground"
                    >
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            class="px-4 py-3 text-right font-medium"
                            :title="warehouse.name"
                        >
                            {{ warehouse.code }}
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Available total
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="product in products.data"
                        :key="product.id"
                        class="border-b border-sidebar-border/70 last:border-0 hover:bg-muted/50"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ product.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ product.sku }}
                        </td>
                        <td
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            class="px-4 py-3 text-right tabular-nums"
                        >
                            {{ product.quantities[warehouse.id] ?? 0 }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-medium tabular-nums"
                        >
                            {{ totalQuantity(product) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openAdjustmentDialog(product)"
                            >
                                Adjust
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :pagination="products" :href-for-page="inventoryPage" />

        <InventoryAdjustmentDialog
            v-model:open="isDialogOpen"
            :product="selectedProduct"
            :warehouses="warehouses"
        />
    </div>
</template>
