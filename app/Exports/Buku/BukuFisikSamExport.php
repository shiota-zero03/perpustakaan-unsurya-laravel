<?php

namespace App\Exports\Buku;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BukuFisikSamExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return new Collection([
            ["No" => 1, "ISBN" => "330.13 GRA d 1 TI", "Judul" => "Dasar-dasar Ekonomi Teknik Jilid 1", "Pengarang" => "Eugene L.Grant", "Penerbit" => "Gramedia Pustaka", "Tahun" => 2024, "Jumlah" => 100, "ProgramStudi" => "Sistem Informasi", "DeskripsiFisik" => "vi+266 hlm; 16x23 cm1", "Lokasi" => "Unsurya", "Bahasa" => "Indonesia", "Edisi" => "1"],
            ["No" => 2, "ISBN" => "330.13 GRA d 1 TE", "Judul" => "Dasar-dasar Ekonomi Teknik Jilid 2", "Pengarang" => "Eugene L.Grant", "Penerbit" => "Gramedia Pustaka", "Tahun" => 2024, "Jumlah" => 100, "ProgramStudi" => "Sistem Informasi", "DeskripsiFisik" => "vi+266 hlm; 16x23 cm1", "Lokasi" => "Unsurya", "Bahasa" => "Indonesia", "Edisi" => "1"],
            ["No" => 3, "ISBN" => "330.13 GRA d 1 KOM", "Judul" => "Dasar-dasar Ekonomi Teknik Jilid 3", "Pengarang" => "Eugene L.Grant", "Penerbit" => "Gramedia Pustaka", "Tahun" => 2024, "Jumlah" => 100, "ProgramStudi" => "Sistem Informasi", "DeskripsiFisik" => "vi+266 hlm; 16x23 cm1", "Lokasi" => "Unsurya", "Bahasa" => "Indonesia", "Edisi" => "1"],
        ]);
    }

    public function headings(): array
    {
        return ["No", "ISBN", "Judul", "Pengarang", "Penerbit", "Tahun", "Jumlah", "ProgramStudi", "DeskripsiFisik", "Lokasi", "Bahasa", "Edisi"];
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
