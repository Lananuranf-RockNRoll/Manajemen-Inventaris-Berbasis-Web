<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportInventoryCSV extends Command
{
    protected $signature   = 'inventory:import {file : Path to the CSV file}';
    protected $description = 'Import inventory data from ML-Dataset.csv';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Membaca file: {$filePath}");

        $rows    = array_map('str_getcsv', file($filePath));
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);

        $this->info('Total baris: ' . count($rows));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        DB::transaction(function () use ($rows, $headers, $bar): void {
            foreach ($rows as $row) {
                $data = array_combine($headers, array_map('trim', $row));

                // Category
                $category = Category::firstOrCreate(
                    ['name' => $data['CategoryName']],
                    ['slug' => \Illuminate\Support\Str::slug($data['CategoryName'])]
                );

                // Warehouse
                $warehouse = Warehouse::firstOrCreate(
                    ['name' => $data['WarehouseName']],
                    [
                        'region'      => $data['RegionName'],
                        'country'     => $data['CountryName'],
                        'state'       => $data['State'],
                        'city'        => $data['City'],
                        'postal_code' => $data['PostalCode'],
                        'address'     => $data['WarehouseAddress'],
                    ]
                );

                // Employee
                $employee = Employee::firstOrCreate(
                    ['email' => $data['EmployeeEmail']],
                    [
                        'name'         => $data['EmployeeName'],
                        'phone'        => $data['EmployeePhone'],
                        'job_title'    => $data['EmployeeJobTitle'],
                        'hire_date'    => $this->parseDate($data['EmployeeHireDate']),
                        'warehouse_id' => $warehouse->id,
                    ]
                );

                // FIX: generate SKU pakai uniqid() langsung — tidak bergantung pada $product->id
                // yang belum ada saat firstOrCreate dipanggil pertama kali
                $product = Product::firstOrCreate(
                    ['name' => $data['ProductName']],
                    [
                        'category_id'   => $category->id,
                        'sku'           => strtoupper(substr($data['CategoryName'], 0, 3)) . '-' . uniqid(),
                        'description'   => $data['ProductDescription'],
                        'standard_cost' => (float) $data['ProductStandardCost'],
                        'list_price'    => (float) $data['ProductListPrice'],
                    ]
                );

                // Customer
                $customer = Customer::firstOrCreate(
                    ['email' => $data['CustomerEmail']],
                    [
                        'name'         => $data['CustomerName'],
                        'address'      => $data['CustomerAddress'],
                        'phone'        => $data['CustomerPhone'],
                        'credit_limit' => (float) $data['CustomerCreditLimit'],
                    ]
                );

                // Transaction
                $status      = strtolower($data['Status']);
                $quantity    = (int) $data['OrderItemQuantity'];
                $unitPrice   = (float) $data['PerUnitPrice'];
                $totalAmount = $unitPrice * $quantity;

                $transaction = Transaction::create([
                    'customer_id'  => $customer->id,
                    'employee_id'  => $employee->id,
                    'warehouse_id' => $warehouse->id,
                    'status'       => match ($status) {
                        'shipped'  => 'shipped',
                        'canceled' => 'canceled',
                        default    => 'pending',
                    },
                    'order_date'   => $this->parseDate($data['OrderDate']),
                    'total_amount' => $totalAmount,
                ]);

                // FIX: tambahkan field subtotal yang wajib ada di TransactionItem
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $product->id,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'subtotal'       => $totalAmount,
                ]);

                // Inventory
                Inventory::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                    ['qty_on_hand' => 500, 'qty_reserved' => 0, 'min_stock' => 10]
                );

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Import selesai!');

        return self::SUCCESS;
    }

    private function parseDate(string $date): ?string
    {
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12',
        ];

        if (preg_match('/(\d{2})-([A-Za-z]{3})-(\d{2})/', $date, $m)) {
            $year  = (int) $m[3] < 50 ? '20' . $m[3] : '19' . $m[3];
            $month = $months[$m[2]] ?? '01';
            return "{$year}-{$month}-{$m[1]}";
        }

        return null;
    }
}
