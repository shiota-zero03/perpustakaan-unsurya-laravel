<?php

namespace App\Exports\Mahasiswa;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramStudiSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithTitle
{
    protected $prodiData;

    // Terima data lewat constructor
    public function __construct($prodiData)
    {
        $this->prodiData = $prodiData;
    }

    public function title(): string
    {
        return 'Data Program Studi';
    }

    public function collection()
    {
        return new Collection($this->prodiData);
    }

    public function headings(): array
    {
        return ["ID", "Nama Program Studi"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '085C94']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $cellRange = "A1:{$highestColumn}{$highestRow}";

                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->freezePane('A2'); // Freeze baris pertama
            },
        ];
    }
}
