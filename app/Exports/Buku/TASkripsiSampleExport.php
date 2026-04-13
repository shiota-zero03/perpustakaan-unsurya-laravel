<?php

namespace App\Exports\Buku;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TASkripsiSampleExport implements WithMultipleSheets
{
    protected $prodiData;

    public function __construct($prodiData)
    {
        $this->prodiData = $prodiData;
    }

    public function sheets(): array
    {
        return [
            'Data Skripsi/TA' => new TASkripsiSamExport(),
            'Data Program Studi' => new ProgramStudiSheetExport($this->prodiData),
        ];
    }
}
