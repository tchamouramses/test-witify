export type DashboardSummary = {
    physical_stock: number;
    reserved_stock: number;
    available_stock: number;
    orders_count: number;
    products_count: number;
    warehouses_count: number;
};

export type DashboardFulfillment = {
    ordered_units: number;
    reserved_units: number;
    pending_units: number;
    rate: number;
};

export type DashboardWarehouse = {
    id: number;
    name: string;
    code: string;
    physical_stock: number;
    reserved_stock: number;
    available_stock: number;
    reserved_percentage: number;
};

export type DashboardOrder = {
    id: number;
    number: string;
    customer_name: string;
    status: string;
    lines_count: number;
    total: number;
    ordered_units: number;
    reserved_units: number;
    fulfillment_rate: number;
    created_at: string;
};

export type DashboardInventoryAlert = {
    id: number;
    name: string;
    sku: string;
    physical_stock: number;
    reserved_stock: number;
    available_stock: number;
    severity: 'critical' | 'low';
};
