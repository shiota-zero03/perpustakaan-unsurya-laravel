<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class DosenImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!$row['Nama'] || !$row['Email'] || !$row['NIDN']) {
                $errors[] = "Baris " . ($index + 2) . ": Nama, Email, dan NIDN wajib diisi.";
                continue;
            }

            // Cek apakah email sudah ada
            if (User::where('email', $row['Email'])->exists()) {
                $errors[] = "Baris " . ($index + 2) . ": Email '{$row['Email']}' sudah terdaftar.";
                continue;
            }

            // Cek apakah NIDN sudah ada
            if (Teacher::where('nidn', $row['NIDN'])->exists()) {
                $errors[] = "Baris " . ($index + 2) . ": NIDN '{$row['NIDN']}' sudah terdaftar.";
                continue;
            }

            // Buat user baru
            $user = User::create([
                'name' => $row['Nama'],
                'email' => $row['Email'],
                'password' => Hash::make($row['NIDN']), // Default password
                'identityNumber' => $row['NIDN'],
                'role' => 'Teacher',
                'status' => 'Active'
            ]);

            // Buat teacher baru
            Teacher::create([
                'userId' => $user->id,
                'gender' => $row['jenis_kelamin'],
                'phoneNumber' => $row['No. Hp'],
            ]);
        }

        // Jika ada error, kembalikan sebagai response
        if (!empty($errors)) {
            session()->flash('import_errors', $errors);
        }
    }
}
