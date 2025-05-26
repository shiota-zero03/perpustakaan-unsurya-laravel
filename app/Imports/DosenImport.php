<?php
namespace App\Imports;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class DosenImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $map = [
            'Nama' => 'nama',
            'NIDN' => 'nidn',
            'JenisKelamin' => 'jenis_kelamin',
            'Telepon' => 'no_hp',
            'Email' => 'email',
        ];

        $row = array_change_key_case($row, CASE_LOWER);


        $formattedRow = [];
        foreach ($map as $oldKey => $newKey) {
            $lowerOldKey = strtolower($oldKey);
            $formattedRow[$newKey] = $row[$lowerOldKey] ?? null;
        }

        if (!$formattedRow['nama'] || !$formattedRow['email'] || !$formattedRow['nidn']) {
            session()->flash('import_errors', ["Data tidak lengkap. Pastikan Nama, Email, dan NIDN ada."]);
            return null;
        }

        // Cek apakah email sudah terdaftar
        if (User::where('email', $formattedRow['email'])->exists()) {
            session()->flash('import_errors', ["Email '{$formattedRow['email']}' sudah terdaftar."]);
            return null;
        }

        // Cek apakah NIDN sudah ada
        if (User::where('identityNumber', $formattedRow['nidn'])->exists()) {
            session()->flash('import_errors', ["NIDN '{$formattedRow['nidn']}' sudah terdaftar."]);
            return null;
        }

        // Buat user baru
        $user = User::create([
            'name' => $formattedRow['nama'],
            'userId' => Str::uuid(),
            'email' => $formattedRow['email'],
            'password' => Hash::make($formattedRow['nidn']),
            'identityNumber' => $formattedRow['nidn'],
            'role' => 'Teacher',
            'status' => 'Active'
        ]);

        $date = date('Y-m-d', strtotime('+1 year'));
        // Buat teacher baru
        return new Teacher([
            'userId' => $user->id,
            'gender' => $formattedRow['jenis_kelamin'] ?? null,
            'phoneNumber' => $formattedRow['no_hp'] ?? '',
            'validUntil' => $date,
        ]);
    }
}
