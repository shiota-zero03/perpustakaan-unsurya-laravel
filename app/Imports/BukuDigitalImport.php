<?php

namespace App\Imports;

use App\Models\MasterBuku;
use App\Models\Buku;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class BukuDigitalImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $map = [
            "Judul" => 'judul',
            "Pengarang" => 'penulis',
            "Penerbit" => 'penerbit',
            "Tahun" => 'tahun_terbit',
            "URL" => 'link_book',
        ];

        $row = array_change_key_case($row, CASE_LOWER);
        $formattedRow = [];
        foreach ($map as $oldKey => $newKey) {
            $lowerOldKey = strtolower($oldKey);
            $formattedRow[$newKey] = $row[$lowerOldKey] ?? null;
        }

        $user = MasterBuku::create([
            'book_id' => Str::uuid(),
            'type' => "Buku Digital",
        ]);

        $dataMahasiswa = [
            'book_id' => $user->id,
            'judul' => $formattedRow['judul'],
            'penulis' => $formattedRow['penulis'],
            'penerbit' => $formattedRow['penerbit'],
            'tahun_terbit' => $formattedRow['tahun_terbit'],
            'link_book' => $formattedRow['link_book'],
        ];

        return new Buku($dataMahasiswa);
    }
}
