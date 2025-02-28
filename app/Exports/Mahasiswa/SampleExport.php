<?php
namespace App\Exports\Mahasiswa;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SampleExport implements WithMultipleSheets
{
    protected $prodiData;

    public function __construct($prodiData)
    {
        $this->prodiData = $prodiData;
    }

    public function sheets(): array
    {
        return [
            'Data Mahasiswa' => new MahasiswaSheetExport(),
            'Data Program Studi' => new ProgramStudiSheetExport($this->prodiData),
        ];
    }
}
