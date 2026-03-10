<?php

namespace App\Enums;

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Permission — Single Source of Truth untuk seluruh hak akses aplikasi.
 *
 * Role matrix:
 *
 * | Permission                     | viewer | staff | manager | admin |
 * |--------------------------------|--------|-------|---------|-------|
 * | VIEW_*                         |  ✅    |  ✅   |   ✅    |  ✅   |
 * | TRANSACTION_CREATE             |  ❌    |  ✅   |   ✅    |  ✅   |
 * | TRANSACTION_UPDATE (notes)     |  ❌    |  ✅   |   ✅    |  ✅   |
 * | TRANSACTION_UPDATE_STATUS      |  ❌    |  ❌   |   ✅    |  ✅   |
 * | TRANSACTION_DELETE             |  ❌    |  ❌   |   ❌    |  ✅   |
 * | PRODUCT_CREATE/UPDATE          |  ❌    |  ❌   |   ✅    |  ✅   |
 * | PRODUCT_DELETE                 |  ❌    |  ❌   |   ❌    |  ✅   |
 * | CATEGORY_CREATE/UPDATE         |  ❌    |  ❌   |   ✅    |  ✅   |
 * | CATEGORY_DELETE                |  ❌    |  ❌   |   ❌    |  ✅   |
 * | WAREHOUSE_CREATE/UPDATE        |  ❌    |  ❌   |   ✅    |  ✅   |
 * | WAREHOUSE_DELETE               |  ❌    |  ❌   |   ❌    |  ✅   |
 * | INVENTORY_UPDATE/TRANSFER      |  ❌    |  ❌   |   ✅    |  ✅   |
 * | CUSTOMER_CREATE/UPDATE         |  ❌    |  ✅   |   ✅    |  ✅   |
 * | CUSTOMER_CREDIT                |  ❌    |  ❌   |   ✅    |  ✅   |
 * | CUSTOMER_DELETE                |  ❌    |  ❌   |   ❌    |  ✅   |
 * | EMPLOYEE_CREATE/UPDATE/DELETE  |  ❌    |  ❌   |   ✅    |  ✅   |
 * | USER_MANAGE                    |  ❌    |  ❌   |   ❌    |  ✅   |
 * | REPORT_VIEW                    |  ❌    |  ❌   |   ✅    |  ✅   |
 * ─────────────────────────────────────────────────────────────────────────────
 */
enum Permission: string
{
    // ── Dashboard ───────────────────────────────────────────────────────────
    case DASHBOARD_VIEW = 'dashboard.view';

    // ── Products ────────────────────────────────────────────────────────────
    case PRODUCT_VIEW   = 'product.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_UPDATE = 'product.update';
    case PRODUCT_DELETE = 'product.delete';

    // ── Categories ──────────────────────────────────────────────────────────
    case CATEGORY_VIEW   = 'category.view';
    case CATEGORY_CREATE = 'category.create';
    case CATEGORY_UPDATE = 'category.update';
    case CATEGORY_DELETE = 'category.delete';

    // ── Warehouses ──────────────────────────────────────────────────────────
    case WAREHOUSE_VIEW   = 'warehouse.view';
    case WAREHOUSE_CREATE = 'warehouse.create';
    case WAREHOUSE_UPDATE = 'warehouse.update';
    case WAREHOUSE_DELETE = 'warehouse.delete';

    // ── Inventory ───────────────────────────────────────────────────────────
    case INVENTORY_VIEW     = 'inventory.view';
    case INVENTORY_UPDATE   = 'inventory.update';
    case INVENTORY_TRANSFER = 'inventory.transfer';

    // ── Customers ───────────────────────────────────────────────────────────
    case CUSTOMER_VIEW   = 'customer.view';
    case CUSTOMER_CREATE = 'customer.create';
    case CUSTOMER_UPDATE = 'customer.update';
    case CUSTOMER_DELETE = 'customer.delete';
    case CUSTOMER_CREDIT = 'customer.credit';

    // ── Employees ───────────────────────────────────────────────────────────
    case EMPLOYEE_VIEW   = 'employee.view';
    case EMPLOYEE_CREATE = 'employee.create';
    case EMPLOYEE_UPDATE = 'employee.update';
    case EMPLOYEE_DELETE = 'employee.delete';

    // ── Transactions ─────────────────────────────────────────────────────────
    case TRANSACTION_VIEW          = 'transaction.view';
    case TRANSACTION_CREATE        = 'transaction.create';
    case TRANSACTION_UPDATE        = 'transaction.update';
    case TRANSACTION_UPDATE_STATUS = 'transaction.update_status';
    case TRANSACTION_DELETE        = 'transaction.delete';

    // ── Users ────────────────────────────────────────────────────────────────
    case USER_MANAGE = 'user.manage';

    // ── Reports ──────────────────────────────────────────────────────────────
    case REPORT_VIEW = 'report.view';
}
