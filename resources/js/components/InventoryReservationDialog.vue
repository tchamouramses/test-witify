<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InventoryReservationController from '@/actions/App/Http/Controllers/InventoryReservationController';
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
import type { OrderLine } from '@/types';

const props = defineProps<{
    orderId: number;
    line: OrderLine | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    warehouse_id: null as number | null,
    quantity: 1,
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

const selectedWarehouse = computed(() =>
    props.line?.warehouses.find(
        (warehouse) => warehouse.id === form.warehouse_id,
    ),
);

function submit(): void {
    if (!props.line) {
        return;
    }

    form.post(
        InventoryReservationController.store.url({
            order: props.orderId,
            line: props.line.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Reserve inventory</DialogTitle>
                <DialogDescription v-if="line">
                    {{ line.product.name }} ({{ line.product.sku }})
                </DialogDescription>
            </DialogHeader>

            <form v-if="line" class="grid gap-5" @submit.prevent="submit">
                <div
                    class="grid grid-cols-3 gap-3 rounded-lg border bg-muted/40 p-3 text-center"
                >
                    <div>
                        <div class="text-xs text-muted-foreground">Ordered</div>
                        <div class="font-semibold tabular-nums">
                            {{ line.quantity_ordered }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">
                            Reserved
                        </div>
                        <div class="font-semibold tabular-nums">
                            {{ line.quantity_reserved }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">
                            Remaining
                        </div>
                        <div class="font-semibold tabular-nums">
                            {{ line.quantity_remaining }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="reservation-warehouse">Warehouse</Label>
                    <Select v-model="form.warehouse_id">
                        <SelectTrigger
                            id="reservation-warehouse"
                            class="w-full"
                            :aria-invalid="Boolean(form.errors.warehouse_id)"
                        >
                            <SelectValue placeholder="Select a warehouse" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="warehouse in line.warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                            >
                                {{ warehouse.name }} ({{ warehouse.code }}) —
                                {{ warehouse.available_quantity }} available
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="selectedWarehouse"
                        class="text-sm text-muted-foreground"
                    >
                        {{ selectedWarehouse.available_quantity }} units are
                        currently available in this warehouse.
                    </p>
                    <InputError :message="form.errors.warehouse_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="reservation-quantity">Quantity</Label>
                    <Input
                        id="reservation-quantity"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        :max="
                            Math.min(
                                line.quantity_remaining,
                                selectedWarehouse?.available_quantity ??
                                    line.quantity_remaining,
                            )
                        "
                        required
                        :aria-invalid="Boolean(form.errors.quantity)"
                    />
                    <InputError :message="form.errors.quantity" />
                </div>

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
                        {{
                            form.processing
                                ? 'Reserving...'
                                : 'Reserve inventory'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
