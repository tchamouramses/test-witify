export type OrderSummary = {
    id: number;
    number: string;
    customer_name: string;
    status: string;
    lines_count: number;
    total: number;
};

export type OrderLineProduct = {
    id: number;
    name: string;
    sku: string;
};

export type OrderLineWarehouse = {
    id: number;
    name: string;
    code: string;
    available_quantity: number;
};

export type OrderLine = {
    id: number;
    product: OrderLineProduct;
    quantity_ordered: number;
    quantity_reserved: number;
    quantity_remaining: number;
    warehouses: OrderLineWarehouse[];
    unit_price: number;
    line_total: number;
};

export type OrderDetail = {
    id: number;
    number: string;
    customer_name: string;
    status: string;
    lines: OrderLine[];
    total: number;
};
