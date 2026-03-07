<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
        private ?string $status = null,
    ) {}

    public function collection()
    {
        return Transaction::with(['customer', 'warehouse', 'employee'])
            ->when($this->from, fn($q) => $q->whereDate('order_date', '>=', $this->from))
            ->when($this->to,   fn($q) => $q->whereDate('order_date', '<=', $this->to))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Order',
            'Tanggal',
            'Customer',
            'Gudang',
            'Karyawan',
            'Status',
            'Total (Rp)',
            'Catatan',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->order_number,
            $row->order_date,
            $row->customer->name ?? '-',
            $row->warehouse->name ?? '-',
            $row->employee->name ?? '-',
            ucfirst($row->status),
            number_format($row->total_amount, 0, ',', '.'),
            $row->notes ?? '-',
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
        return 'Laporan Penjualan';
    }
}
