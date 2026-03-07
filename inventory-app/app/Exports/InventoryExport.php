<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private ?int $warehouseId = null
    ) {}

    public function collection()
    {
        return Inventory::with(['product.category', 'warehouse'])
            ->when($this->warehouseId, fn($q) => $q->where('warehouse_id', $this->warehouseId))
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'SKU',
            'Nama Produk',
            'Kategori',
            'Gudang',
            'Stok Di Tangan',
            'Stok Direservasi',
            'Stok Tersedia',
            'Min Stok',
            'Max Stok',
            'Status',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->product->sku,
            $row->product->name,
            $row->product->category->name ?? '-',
            $row->warehouse->name,
            $row->qty_on_hand,
            $row->qty_reserved,
            $row->qty_on_hand - $row->qty_reserved,
            $row->min_stock,
            $row->max_stock,
            ($row->qty_on_hand - $row->qty_reserved) <= $row->min_stock ? 'Stok Rendah' : 'Normal',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Inventaris';
    }
}
