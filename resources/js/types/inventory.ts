export type Warehouse = {
    id: number;
    name: string;
    code: string;
};

export type WarehouseSummary = Warehouse & {
    products_in_stock: number;
    units_in_stock: number;
};

export type ProductInventory = {
    id: number;
    name: string;
    sku: string;
    quantities: Record<number, number>;
};
