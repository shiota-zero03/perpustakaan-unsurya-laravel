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
class BukuFisikSampleExport implements WithMultipleSheets
{
    protected $prodiData;

    public function __construct($prodiData)
    {
        $this->prodiData = $prodiData;
    }

    public function sheets(): array
    {
        return [
            'Data Buku Fisik' => new BukuFisikSamExport(),
            'Data Program Studi' => new ProgramStudiSheetExport($this->prodiData),
        ];
    }
}
