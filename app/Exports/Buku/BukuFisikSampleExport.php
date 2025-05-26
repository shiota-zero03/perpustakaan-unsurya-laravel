<?php

namespace App\Exports\Buku;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

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
