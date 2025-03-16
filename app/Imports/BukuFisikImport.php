<?php

namespace App\Imports;

use App\Models\MasterBuku;
use App\Models\Buku;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class BukuFisikImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $map = [
            "Kode" => 'kode_klasifikasi',
            "Judul" => 'judul',
            "Pengarang" => 'penulis',
            "Penerbit" => 'penerbit',
            "Tahun" => 'tahun_terbit',
            "Jumlah" => 'stok',
        ];

        $row = array_change_key_case($row, CASE_LOWER);
        $formattedRow = [];
        foreach ($map as $oldKey => $newKey) {
            $lowerOldKey = strtolower($oldKey);
            $formattedRow[$newKey] = $row[$lowerOldKey] ?? null;
        }

        $user = MasterBuku::create([
            'book_id' => Str::uuid(),
            'type' => "Buku Fisik",
        ]);

        $dataMahasiswa = [
            'book_id' => $user->id,
            'kode_klasifikasi' => $formattedRow['kode_klasifikasi'],
            'judul' => $formattedRow['judul'],
            'penulis' => $formattedRow['penulis'],
            'penerbit' => $formattedRow['penerbit'],
            'tahun_terbit' => $formattedRow['tahun_terbit'],
            'stok' => $formattedRow['stok'],
        ];

        return new Buku($dataMahasiswa);
    }
}
