<?php
namespace App\Exports\Dosen;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SampleExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    public function collection()
    {
        return new Collection([
            ["No" => 1, "Nama" => "John Doe", "NIDN" => "123481231231", "JenisKelamin" => "L", "Telepon" => "081241231312", "Email" => "youremail@gmail.com"],
            ["No" => 2, "Nama" => "Jane Doe", "NIDN" => "987654321123", "JenisKelamin" => "P", "Telepon" => "081298765432", "Email" => "jane@example.com"],
            ["No" => 3, "Nama" => "Michael Smith", "NIDN" => "567812345678", "JenisKelamin" => "L", "Telepon" => "081234567890", "Email" => "michael@example.com"],
        ]);
    }

    public function headings(): array
    {
        return ["No", "Nama", "NIDN", "JenisKelamin", "Telepon", "Email"];
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

                // Tambahkan border ke semua sel
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Freeze header (baris pertama)
                $sheet->freezePane('A2');
            },
        ];
    }
}
