<?php

namespace App\Imports;

use App\Models\MasterBuku;
use App\Models\Buku;
use App\Models\StudyProgram;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class BukuFisikImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $map = [
            "ISBN" => 'isbn',
            "Judul" => 'judul',
            "Pengarang" => 'penulis',
            "Penerbit" => 'penerbit',
            "Tahun" => 'tahun_terbit',
            "Jumlah" => 'stok',
            'ProgramStudi' => 'program_studi',
        ];

        $row = array_change_key_case($row, CASE_LOWER);
        $formattedRow = [];
        foreach ($map as $oldKey => $newKey) {
            $lowerOldKey = strtolower($oldKey);
            $formattedRow[$newKey] = $row[$lowerOldKey] ?? null;
        }

        if (empty($formattedRow['isbn']) || empty($formattedRow['judul'])) {
            return null;
        }

        $user = MasterBuku::create([
            'book_id' => Str::uuid(),
            'type' => "Buku Fisik",
        ]);

        $dataMahasiswa = [
            'book_id' => $user->id,
            'isbn' => $formattedRow['isbn'],
            'judul' => $formattedRow['judul'],
            'penulis' => $formattedRow['penulis'],
            'penerbit' => $formattedRow['penerbit'],
            'tahun_terbit' => $formattedRow['tahun_terbit'],
            'stok' => $formattedRow['stok'],
        ];
        \Log::info($formattedRow);
        $checkProdi = StudyProgram::where('name', $formattedRow['program_studi'])->first();
        if($checkProdi) {
            $dataStudent['studyProgramId'] = $checkProdi['id'];
        }

        return new Buku($dataMahasiswa);
    }
}
