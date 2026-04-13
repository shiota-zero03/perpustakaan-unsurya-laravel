<?php

namespace App\Imports;

use App\Models\Faculty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;
use App\Models\MasterBuku;
use App\Models\KaryaTulis;
use App\Models\StudyProgram;

class TASkripsiImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $map = [
            "nama" => 'penulis',
            "npm" => 'nim',
            "jenis_karya_tulis" => 'jenis',
            "nomor_urut_karya_tulis" => 'no_urut',
            "kode_klasifikasi_koleksi_perpustakaan" => 'kode_klasifikasi',
            "judul_karya_tulis" => 'judul',
            "tahun_terbit" => 'tahun_terbit',
            "fakultas" => 'faculty',
            "program_studi" => 'department',
            "tanggal_masuk_perpustakaan" => 'tanggal_masuk',
            "kode_rak" => 'kode_rak',
            "abstrak" => 'abstrak',
        ];

        $prodiList = StudyProgram::select('id', 'name')->get()
            ->mapWithKeys(function ($item) {
                $normalized = strtolower(trim($item->name));
                $normalized = preg_replace('/\s+/', '_', $normalized);
                $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized);

                return [$normalized => $item->id];
            });

        $departList = Faculty::select('id', 'name')->get()
            ->mapWithKeys(function ($item) {
                $normalized = strtolower(trim($item->name));
                $normalized = preg_replace('/\s+/', '_', $normalized);
                $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized);

                return [$normalized => $item->id];
            });

        $headerRowIndex = null;
        $headers = [];

        // 🔍 STEP 1: DETECT HEADER
        foreach ($rows as $index => $row) {
            $rowArray = array_map(function ($v) {
                $v = strtolower(trim($v));
                $v = preg_replace('/\s+/', '_', $v);
                $v = preg_replace('/[^a-z0-9_]/', '', $v);
                return $v;
            }, $row->toArray());

            $matchCount = 0;

            foreach (array_keys($map) as $key) {
                if (in_array($key, $rowArray)) {
                    $matchCount++;
                }
            }

            if ($matchCount >= 3) {
                $headerRowIndex = $index;
                $headers = $rowArray;
                break;
            }
        }

        if ($headerRowIndex === null) {
            \Log::error("Header tidak ditemukan");
            return;
        }

        \Log::info("Header ditemukan di baris ke-" . ($headerRowIndex + 1), $headers);

        // 🔄 STEP 2: PROCESS DATA
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {

            $row = $rows[$i]->toArray();
            $formattedRow = [];

            foreach ($headers as $colIndex => $headerName) {
                if (!$headerName) continue;

                if (isset($map[$headerName])) {
                    $formattedRow[$map[$headerName]] = $row[$colIndex] ?? null;
                }
            }

            // skip kalau kosong
            if (empty(array_filter($formattedRow))) {
                continue;
            }

            $dept = $formattedRow['department'] ?? null;
            $studyProgramId = null;
            if ($dept) {
                $normalizedDept = strtolower(trim($dept));
                $normalizedDept = preg_replace('/\s+/', '_', $normalizedDept);
                $normalizedDept = preg_replace('/[^a-z0-9_]/', '', $normalizedDept);

                $studyProgramId = $prodiList[$normalizedDept] ?? null;
            }

            $fac = $formattedRow['faculty'] ?? null;
            $facId = null;
            if ($fac) {
                $normalizedDept = strtolower(trim($fac));
                $normalizedDept = preg_replace('/\s+/', '_', $normalizedDept);
                $normalizedDept = preg_replace('/[^a-z0-9_]/', '', $normalizedDept);

                $facId = $departList[$normalizedDept] ?? null;
            }

            try {
                // 🔥 CREATE MASTER BUKU
                $master = MasterBuku::create([
                    'book_id' => Str::uuid(),
                    'type' => "Karya Tulis",
                ]);

                // 🔥 PREPARE DATA BUKU
                $data = [
                    'book_id' => $master->id,
                    'judul' => $formattedRow['judul'] ?? null,
                    'penulis' => $formattedRow['penulis'] ?? null,
                    'tahun_terbit' => $formattedRow['tahun_terbit'] ?? null,
                    'kode_klasifikasi' => $formattedRow['kode_klasifikasi'] ?? null,
                    'kode_rak' => $formattedRow['kode_rak'] ?? null,
                    'abstrak' => $formattedRow['abstrak'] ?? null,

                    'book_id' => $master->id,
                    'judul' => $formattedRow['judul'] ?? null,
                    'penulis' => $formattedRow['penulis'] ?? null,
                    'nim' => $formattedRow['nim'] ?? null,
                    // 'facultyId' => $request->facultyId,
                    // 'studyProgramId' => $request->studyProgramId,
                    'tahun_terbit' => $formattedRow['tahun_terbit'] ?? null,
                    'jenis' => $formattedRow['jenis'] ?? null,
                    'no_urut' => $formattedRow['no_urut'] ?? null,
                    'kode_klasifikasi' => $formattedRow['kode_klasifikasi'] ?? null,
                    'tanggal_masuk' => $this->parseDate($formattedRow['tanggal_masuk'] ?? null),
                    'kode_rak' => $formattedRow['kode_rak'] ?? null,
                    'abstrak' => $formattedRow['abstrak'] ?? null,
                ];

                if ($studyProgramId) {
                    $data['studyProgramId'] = $studyProgramId;
                }
                if ($facId) {
                    $data['facultyId'] = $facId;
                }

                // 🔥 INSERT BUKU
                KaryaTulis::create($data);

                // \Log::info("SUCCESS ROW KE-" . ($i + 1), $formattedRow);

            } catch (\Exception $e) {
                \Log::error("ERROR ROW KE-" . ($i + 1), [
                    'data' => $formattedRow,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function parseDate($value)
    {
        if (!$value) return null;

        // hapus tanda petik di depan (kayak '02/04/2026)
        $value = ltrim($value, "'");

        try {
            // format: 2026-04-02 (ISO)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            }

            // format: 02/04/2026 (dd/mm/yyyy)
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            // fallback (biar Carbon coba sendiri)
            return \Carbon\Carbon::parse($value)->format('Y-m-d');

        } catch (\Exception $e) {
            \Log::error("Format tanggal tidak valid", [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
