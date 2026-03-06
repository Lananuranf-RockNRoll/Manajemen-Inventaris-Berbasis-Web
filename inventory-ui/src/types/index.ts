export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'manager' | 'staff' | 'viewer'
  is_active: boolean
  created_at: string
}

export interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  is_active: boolean
  products_count?: number
  created_at: string
  updated_at: string
}

export interface Product {
  id: number
  sku: string
  name: string
  description: string | null
  standard_cost: string
  list_price: string
  profit_margin: number
  profit_percentage: number
  is_active: boolean
  category: Category
  created_at: string
  updated_at: string
}

export interface Warehouse {
  id: number
  name: string
  region: string | null
  country: string | null
  state: string | null
  city: string | null
  postal_code: string | null
  address: string | null
  phone: string | null
  email: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Inventory {
  id: number
  product_id: number
  warehouse_id: number
  qty_on_hand: number
  qty_reserved: number
  qty_available: number
  min_stock: number
  max_stock: number
  is_low_stock: boolean
  last_restocked_at: string | null
  product: Product
  warehouse: Warehouse
  updated_at: string
}

export interface Customer {
  id: number
  name: string
  email: string | null
  phone: string | null
  address: string | null
  credit_limit: string
  credit_used: string
  credit_available: number
  status: 'active' | 'inactive' | 'blacklisted'
  transactions_count?: number
  created_at: string
  updated_at: string
}

export interface Employee {
  id: number
  name: string
  email: string
  phone: string | null
  job_title: string | null
  department: string | null
  hire_date: string | null
  is_active: boolean
  warehouse_id: number | null
  warehouse?: Warehouse
  created_at: string
  updated_at: string
}

export interface TransactionItem {
  id: number
  transaction_id: number
  product_id: number
  quantity: number
  unit_price: string
  subtotal: number
  product: Product
}

export interface Transaction {
  id: number
  order_number: string
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'canceled'
  order_date: string
  shipped_date: string | null
  total_amount: string
  notes: string | null
  customer_id: number
  employee_id: number | null
  warehouse_id: number
  customer: Customer
  employee?: Employee
  warehouse: Warehouse
  items?: TransactionItem[]
  created_at: string
  updated_at: string
}

export interface DashboardSummary {
  total_revenue: number
  total_orders: number
  shipped_orders: number
  pending_orders: number
  canceled_orders: number
  total_products: number
  total_warehouses: number
  total_customers: number
  low_stock_alerts: number
  top_category: string
  revenue_this_month: number
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}
