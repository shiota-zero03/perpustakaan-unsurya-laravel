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

class MahasiswaSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
    public function collection()
    {
        return new Collection([
            ["No" => 1, "Nama" => "John Doe", "Email" => "youremails@gmail.com", "NIM" => "1234831231", "JenisKelamin" => "L", "Telepon" => "081241231312", "ProgramStudi" => "Sistem Informasi" ],
            ["No" => 2, "Nama" => "Jane Doe", "Email" => "janes@example.com", "NIM" => "987651123", "JenisKelamin" => "P", "Telepon" => "081298765432", "ProgramStudi" => "Sistem Informasi"],
        ]);
    }

    public function title(): string
    {
        return 'Sample Data Mahasiswa';
    }

    public function headings(): array
    {
        return ["No", "Nama", "Email", "NIM", "JenisKelamin", "Telepon", "ProgramStudi"];
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
