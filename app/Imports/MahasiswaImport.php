<?php
namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class MahasiswaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $map = [
            'Nama' => 'nama',
            'Email' => 'email',
            'NIM' => 'nim',
            'JenisKelamin' => 'jenis_kelamin',
            'Telepon' => 'no_hp',
            'ProgramStudi' => 'program_studi',
        ];

        $row = array_change_key_case($row, CASE_LOWER);

        $formattedRow = [];
        foreach ($map as $oldKey => $newKey) {
            $lowerOldKey = strtolower($oldKey);
            $formattedRow[$newKey] = $row[$lowerOldKey] ?? null;
        }

        if (!$formattedRow['nama'] || !$formattedRow['email'] || !$formattedRow['nim']) {
            session()->flash('import_errors', ["Data tidak lengkap. Pastikan Nama, Email, dan NIDN ada."]);
            return null;
        }
        // Cek apakah email sudah terdaftar
        if (User::where('email', $formattedRow['email'])->exists()) {
            session()->flash('import_errors', ["Email '{$formattedRow['email']}' sudah terdaftar."]);
            return null;
        }

        // Cek apakah NIDN sudah ada
        if (User::where('identityNumber', $formattedRow['nim'])->exists()) {
            session()->flash('import_errors', ["nim '{$formattedRow['nim']}' sudah terdaftar."]);
            return null;
        }

        // Buat user baru
        $user = User::create([
            'name' => $formattedRow['nama'],
            'userId' => Str::uuid(),
            'email' => $formattedRow['email'],
            'password' => Hash::make($formattedRow['nim']),
            'identityNumber' => $formattedRow['nim'],
            'role' => 'Student',
            'status' => 'Active'
        ]);

        // Buat student baru
        $dataStudent = [
            'userId' => $user->id,
            'gender' => $formattedRow['jenis_kelamin'] ?? null,
            'phoneNumber' => $formattedRow['no_hp'] ?? '',
        ];
        $checkProdi = StudyProgram::where('name', $formattedRow['program_studi'])->first();
        if($checkProdi) {
            $dataStudent['studyProgramId'] = $checkProdi['id'];
            $dataStudent['facultyId'] = $checkProdi['fakultas_id'];
        }

        return new Student($dataStudent);
    }
}
