<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Resources\Responses\ApiResponse;
use Illuminate\Support\Facades\Http;
use App\Models\SecondUser;
use Illuminate\Support\Str;

class SincronController extends Controller
{
    protected $res;

    public function __construct(ApiResponse $res)
    {
        $this->res = $res;
    }

    public function SinkronAnggota(Request $request)
    {
        $token = $request->bearerToken();

        // ambil data mahasiswa
        $responseMahasiswa = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
        ])->get(env('UNSURYA_MIDDLEWARE_API') . '/get_all_mahasiswa', [
            'refresh_token' => $token
        ]);

        // ambil data pegawai
        $responsePegawai = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
        ])->get(env('UNSURYA_MIDDLEWARE_API') . '/get_all_pegawai', [
            'refresh_token' => $token
        ]);

        if (!$responseMahasiswa->successful() || !$responsePegawai->successful()) {
            \Log::info([
                'mahasiswa' => $responseMahasiswa->body(),
                'pegawai'   => $responsePegawai->body(),
            ]);
            return $this->res->successResponse('Data gagal disinkronkan', [], 200);
        }

        $dMahasiswa = collect($responseMahasiswa->json()['data'] ?? []);
        $dPegawai   = collect($responsePegawai->json()['data'] ?? []);

        $dataMahasiswa = $dMahasiswa->map(function ($m) {
            $tahun    = substr($m['tahun_masuk'], 0, 4);
            $semester = substr($m['tahun_masuk'], -1) === '1' ? 'Ganjil' : 'Genap';

            return [
                'id'              => (string) Str::uuid(),
                'user_id'         => $m['user_id'],
                'name'            => $m['nama'],
                'email'           => $m['email'],
                'nim'             => $m['user_id'],
                'status'          => $m['status_aktif'],
                'waktu_terdaftar' => "{$tahun} {$semester}",
                'gender'          => $m['jeniskelamin'],
                'phone_number'    => $m['no_hp'],
                'faculty'         => $m['fakultas'],
                'department'      => $m['prodi'],
                'otoritas'        => "MAHASISWA",
            ];
        });

        $dataDosen = $dPegawai->map(function ($m) {
            return [
                'id'              => (string) Str::uuid(),
                'user_id'         => $m['nip'],
                'name'            => $m['nama'],
                'email'           => $m['email'],
                'nim'             => $m['nip'],
                'status'          => $m['statusaktif'],
                'waktu_terdaftar' => "",
                'gender'          => $m['jeniskelamin'],
                'phone_number'    => $m['no_telpon'],
                'faculty'         => $m['fakultas'],
                'department'      => $m['prodi'],
                'otoritas'        => "PEGAWAI",
            ];
        });

        SecondUser::truncate();
        $allData   = $dataDosen->merge($dataMahasiswa);
        $userIds   = $allData->pluck('user_id')->toArray();
        $existing  = SecondUser::whereIn('user_id', $userIds)->pluck('user_id')->toArray();

        // upsert semua data
        $allData->chunk(500)->each(function ($chunk) {
            SecondUser::upsert(
                $chunk->toArray(),
                ['user_id'],
                [
                    'name',
                    'email',
                    'nim',
                    'status',
                    'waktu_terdaftar',
                    'gender',
                    'phone_number',
                    'faculty',
                    'department',
                    'otoritas'
                ]
            );
        });

        // hitung hasil
        $total    = $allData->count();
        $exist    = count($existing);
        $inserted = $total - $exist; // data baru
        $updated  = $exist;          // data lama yang terupdate
        $error    = 0;               // upsert seharusnya tidak gagal

        return $this->res->successResponse('Data berhasil disinkronkan', [
            'total'   => $total,
            'insert'  => $inserted,
            'update'  => $updated,
            'error'   => $error,
            'dataPegawai' => $dataDosen->toArray()
        ], 200);
    }
}
