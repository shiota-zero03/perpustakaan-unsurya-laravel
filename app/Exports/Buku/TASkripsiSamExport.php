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

class TASkripsiSamExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return new Collection([
            ["Nama" => "AHMAD SODIQ", "NPM" => "191071026", "Jenis Karya Tulis" => "Skripsi", "Nomor Urut Karya Tulis" => "-", "Kode Klasifikasi Koleksi Perpustakaan" => "2024 AHM MAN", "Judul Karya Tulis" => "PENGARUH MOTIVASI KERJA DAN LINGKUNGAN KERJA TERHADAP KINERJA KARYAWAN PADA PT. LEN TELEKOMUNIKASI INDONESIA DI JAKARTA", "Tahun Terbit" => "2024", "Fakultas" => "Ekonomi dan Bisnis", "Program Studi" => "Manajemen", "Tanggal Masuk Perpustakaan" => "02/04/2026", "Kode Rak" => "Rak Nomor 2 MAN", "Abstrak" => ""],
            ["Nama" => "AHMAD SODIQ", "NPM" => "191071026", "Jenis Karya Tulis" => "TA", "Nomor Urut Karya Tulis" => "-", "Kode Klasifikasi Koleksi Perpustakaan" => "2024 AHM MAN", "Judul Karya Tulis" => "PENGARUH MOTIVASI KERJA DAN LINGKUNGAN KERJA TERHADAP KINERJA KARYAWAN PADA PT. LEN TELEKOMUNIKASI INDONESIA DI JAKARTA", "Tahun Terbit" => "2024", "Fakultas" => "Ekonomi dan Bisnis", "Program Studi" => "Manajemen", "Tanggal Masuk Perpustakaan" => "02/04/2026", "Kode Rak" => "Rak Nomor 2 MAN", "Abstrak" => ""],
            ["Nama" => "AHMAD SODIQ", "NPM" => "191071026", "Jenis Karya Tulis" => "Tesis", "Nomor Urut Karya Tulis" => "-", "Kode Klasifikasi Koleksi Perpustakaan" => "2024 AHM MAN", "Judul Karya Tulis" => "PENGARUH MOTIVASI KERJA DAN LINGKUNGAN KERJA TERHADAP KINERJA KARYAWAN PADA PT. LEN TELEKOMUNIKASI INDONESIA DI JAKARTA", "Tahun Terbit" => "2024", "Fakultas" => "Ekonomi dan Bisnis", "Program Studi" => "Manajemen", "Tanggal Masuk Perpustakaan" => "02/04/2026", "Kode Rak" => "Rak Nomor 2 MAN", "Abstrak" => ""],
            ["Nama" => "AHMAD SODIQ", "NPM" => "191071026", "Jenis Karya Tulis" => "Disertasi", "Nomor Urut Karya Tulis" => "-", "Kode Klasifikasi Koleksi Perpustakaan" => "2024 AHM MAN", "Judul Karya Tulis" => "PENGARUH MOTIVASI KERJA DAN LINGKUNGAN KERJA TERHADAP KINERJA KARYAWAN PADA PT. LEN TELEKOMUNIKASI INDONESIA DI JAKARTA", "Tahun Terbit" => "2024", "Fakultas" => "Ekonomi dan Bisnis", "Program Studi" => "Manajemen", "Tanggal Masuk Perpustakaan" => "02/04/2026", "Kode Rak" => "Rak Nomor 2 MAN", "Abstrak" => ""],
        ]);
    }

    public function headings(): array
    {
        return ["Nama", "NPM", "Jenis Karya Tulis", "Nomor Urut Karya Tulis", "Kode Klasifikasi Koleksi Perpustakaan", "Judul Karya Tulis", "Tahun Terbit", "Fakultas", "Program Studi", "Tanggal Masuk Perpustakaan", "Kode Rak", "Abstrak" ];
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
