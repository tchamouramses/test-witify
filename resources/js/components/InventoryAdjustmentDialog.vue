<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InventoryAdjustmentController from '@/actions/App/Http/Controllers/InventoryAdjustmentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ProductInventory, Warehouse } from '@/types';

const props = defineProps<{
    product: ProductInventory | null;
    warehouses: Warehouse[];
}>();

const open = defineModel<boolean>('open', { required: true });

const direction = ref<'add' | 'remove'>('add');
const quantity = ref(1);

const form = useForm({
    product_id: 0,
    warehouse_id: null as number | null,
    quantity_change: 0,
});

watch(open, (isOpen) => {
    if (isOpen) {
        direction.value = 'add';
        quantity.value = 1;
        form.reset();
        form.clearErrors();
    }
});

const availableStock = computed(() => {
    if (!props.product || form.warehouse_id === null) {
        return null;
    }

    return props.product.quantities[form.warehouse_id] ?? 0;
});

function submit() {
    if (!props.product) {
        return;
    }

    const productId = props.product.id;

    form.transform((data) => ({
        ...data,
        product_id: productId,
        quantity_change:
            direction.value === 'remove'
                ? -Math.abs(quantity.value)
                : Math.abs(quantity.value),
    })).post(InventoryAdjustmentController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Adjust stock</DialogTitle>
                <DialogDescription v-if="product">
                    {{ product.name }} ({{ product.sku }})
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="warehouse">Warehouse</Label>
                    <Select v-model="form.warehouse_id">
                        <SelectTrigger id="warehouse" class="w-full">
                            <SelectValue placeholder="Select a warehouse" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="warehouse in warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                            >
                                {{ warehouse.name }} ({{ warehouse.code }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="availableStock !== null"
                        class="text-sm text-muted-foreground"
                    >
                        Available stock: {{ availableStock }}
                    </p>
                    <InputError :message="form.errors.warehouse_id" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="direction">Direction</Label>
                        <Select v-model="direction">
                            <SelectTrigger id="direction" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="add">Add stock</SelectItem>
                                <SelectItem value="remove">
                                    Remove stock
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="quantity">Quantity</Label>
                        <Input
                            id="quantity"
                            v-model.number="quantity"
                            type="number"
                            min="1"
                            :max="
                                direction === 'remove'
                                    ? (availableStock ?? undefined)
                                    : undefined
                            "
                            required
                        />
                    </div>
                </div>
                <InputError :message="form.errors.quantity_change" />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            form.processing || form.warehouse_id === null
                        "
                    >
                        {{ form.processing ? 'Saving...' : 'Save adjustment' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
