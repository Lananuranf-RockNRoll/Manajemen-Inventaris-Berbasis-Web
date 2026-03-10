<?php

namespace App\Enums;

/**
 * Role — Daftar role yang valid dalam sistem.
 * Urutan dari paling rendah ke paling tinggi aksesnya.
 */
enum Role: string
{
    case VIEWER  = 'viewer';
    case STAFF   = 'staff';
    case MANAGER = 'manager';
    case ADMIN   = 'admin';

    /**
     * Kembalikan semua Permission yang dimiliki role ini.
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::VIEWER  => self::viewerPermissions(),
            self::STAFF   => self::staffPermissions(),
            self::MANAGER => self::managerPermissions(),
            self::ADMIN   => Permission::cases(), // admin dapat semua
        };
    }

    // ── Permission sets per role ──────────────────────────────────────────────

    /** Viewer: hanya baca semua resource */
    private static function viewerPermissions(): array
    {
        return [
            Permission::DASHBOARD_VIEW,
            Permission::PRODUCT_VIEW,
            Permission::CATEGORY_VIEW,
            Permission::WAREHOUSE_VIEW,
            Permission::INVENTORY_VIEW,
            Permission::CUSTOMER_VIEW,
            Permission::EMPLOYEE_VIEW,
            Permission::TRANSACTION_VIEW,
        ];
    }

    /**
     * Staff: baca semua + tambah transaksi + edit notes transaksi + tambah/edit customer.
     * Staff TIDAK bisa: ubah status transaksi, hapus transaksi, tambah/edit/hapus produk,
     * transfer inventory, manage user, lihat laporan.
     */
    private static function staffPermissions(): array
    {
        return [
            ...self::viewerPermissions(),
            Permission::TRANSACTION_CREATE,
            Permission::TRANSACTION_UPDATE,  // edit notes saja
            Permission::CUSTOMER_CREATE,
            Permission::CUSTOMER_UPDATE,
        ];
    }

    /**
     * Manager (Manajer Gudang): staff permissions + CRUD produk, kategori, warehouse,
     * inventory, employee, ubah status transaksi, credit customer, laporan.
     * Manager TIDAK bisa: hapus transaksi selesai (delivered), hapus kategori/produk
     * (masih diperbolehkan hapus kategori/produk oleh spec — tetapi TIDAK hapus transaksi
     * apapun kecuali pending, dan tidak bisa hapus customer, tidak manage user).
     *
     * Sesuai spec: Manager "bisa tambah/edit/hapus produk dan tambah/edit transaksi,
     * tapi tidak bisa hapus transaksi yang sudah selesai."
     * → DELETE transaksi: hanya admin. Hapus produk/kategori/warehouse: manager + admin.
     */
    private static function managerPermissions(): array
    {
        return [
            ...self::staffPermissions(),
            // Products
            Permission::PRODUCT_CREATE,
            Permission::PRODUCT_UPDATE,
            Permission::PRODUCT_DELETE,
            // Categories
            Permission::CATEGORY_CREATE,
            Permission::CATEGORY_UPDATE,
            Permission::CATEGORY_DELETE,
            // Warehouses
            Permission::WAREHOUSE_CREATE,
            Permission::WAREHOUSE_UPDATE,
            Permission::WAREHOUSE_DELETE,
            // Inventory
            Permission::INVENTORY_UPDATE,
            Permission::INVENTORY_TRANSFER,
            // Employees
            Permission::EMPLOYEE_CREATE,
            Permission::EMPLOYEE_UPDATE,
            Permission::EMPLOYEE_DELETE,
            // Transactions
            Permission::TRANSACTION_UPDATE_STATUS,
            // Customers
            Permission::CUSTOMER_CREDIT,
            // Reports
            Permission::REPORT_VIEW,
        ];
    }
}
