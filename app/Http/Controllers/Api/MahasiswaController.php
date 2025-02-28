<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use App\Exports\Mahasiswa\SampleExport;
use App\Exports\Mahasiswa\DataExport;
use App\Imports\MahasiswaImport;
use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\User;
use App\Models\StudyProgram;
use App\Models\Student;

class MahasiswaController extends Controller
{
    protected $user, $student, $res;
    public function __construct( User $user, Student $student, ApiResponse $res )
    {
        $this->user = $user;
        $this->student = $student;
        $this->res = $res;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $name = $request->input('name');
        $nim = $request->input('nim');
        $status = $request->input('status');

        $query = User::with('student')->orderByDesc('id')->where('role', 'Student');

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        if (!empty($nim)) {
            $query->where('identityNumber', 'like', "%{$nim}%");
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->userId,
                'name' =>  $user->name,
                'email' => $user->email,
                'nim' => $user->identityNumber,
                'status' => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                'waktu_terdaftar' => $user->created_at,
                'gender' => $user->student->gender,
                'phone' => $user->student->phoneNumber,
                'faculty' => $user->student->fakultas->name ?? '',
                'department' => $user->student->prodi->name ?? '',
            ];
        });

        $data = [
            'data' => $items,
            'pagination' => [
                'from' => ($users->currentPage() - 1) * $users->perPage() + 1,
                'to' => min($users->currentPage() * $users->perPage(), $users->total()),
                'currentPage' => $users->currentPage(),
                'totalPages' => $users->lastPage(),
                'totalItems' => $users->total(),
                'limit' => $users->perPage(),
            ]
        ];

        return $this->res->successResponse('Data mahasiswa berhasil didapatkan', $data, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = $this->__rules('create', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }
            $image = null;
            $mimeMap = [
                "image/png" => "png",
                "image/jpeg" => "jpg",
                "image/jpg" => "jpg"
            ];

            if($request->profilePicture) {
                $file = Base64FileService::saveBase64File($request->profilePicture, $mimeMap, 'mahasiswa');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['profilePicture' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'userId' => Str::uuid(),
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'role' => 'Student',
                'status' => $request->status,
                'identityNumber' => $request->nim,
                'email_verified_at' => now(),
            ];

            $user = User::create($data);

            $dataMahasiswa = [
                'userId' => $user->id,
                'profilePicture' => $image,
                'gender' => $request->gender,
                'phoneNumber' => $request->phoneNumber,
                'facultyId' => $request->faculty,
                'studyProgramId' => $request->department,
                'validUntil' => $request->validUntil,
            ];

            Student::create($dataMahasiswa);

            $dataToShow = array_merge($data, $dataMahasiswa);

            DB::commit();

            return $this->res->successResponse('Data mahasiswa berhasil ditambahkan', $dataToShow, 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            DB::beginTransaction();

            $user = User::where('userId', $id)->with(['student'])->first();
            if(!$user) {
                return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'nim' => $user->identityNumber,
                'status' => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                'gender' => $user->student->gender,
                'phone_number' => $user->student->phoneNumber,
                'valid_until' => $user->student->validUntil,
                'waktu_terdaftar' => $user->created_at,
                'profile_picture' => $user->student->profilePicture,
                'faculty' => [
                    'id' => $user->student->fakultas->id ?? '',
                    'name' => $user->student->fakultas->name ?? ''
                ],
                'department' => [
                    'id' => $user->student->prodi->id ?? '',
                    'name' => $user->student->prodi->name ?? ''
                ],
            ];

            DB::commit();

            return $this->res->successResponse('Data mahasiswa berhasil didapatkan', $data, 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function selected_action(Request $request)
    {
        $arraySelected = ['deleted', 'activated', 'non-activated'];
        if(!$request->action || !in_array($request->action, $arraySelected)) {
            return $this->res->errorResponse("Aksi yang anda lakukan tidak diizinkan", [], 400);
        }

        if (!$request->selectedId || !is_array($request->selectedId)) {
            return $this->res->errorResponse("Pilih salah satu data yang valid", [], 400);
        }

        try {
            DB::beginTransaction();

            $dataId = [];
            foreach ($request->selectedId as $key => $value) {
                $userUpdate = User::where('userId', $value)->with(['student'])->first();
                if(!$userUpdate) {
                    $dataId[] = $value;
                } else {
                    if($request->action == "deleted") {
                        $userUpdate->delete();
                    } elseif($request->action == "activated") {
                        if($userUpdate->email_verified_at) {
                            $userUpdate->update(['status' => 'Active']);
                        } else {
                            $userUpdate->update(['email_verified_at' => now(), 'status' => 'Active']);
                        }
                    } else {
                        $userUpdate->update(['status' => 'InActive']);
                    }
                }
            }

            $stringId = implode(", ", $dataId);
            $message = "Data berhasil diperbarui ".(count($dataId) > 0 ? "kecuali data {$stringId}" : "");

            DB::commit();

            return $this->res->errorResponse($message, [], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
        Log::info($request->all());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::where('userId', $id)->with(['student'])->first();
        if(!$user) {
            return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $user);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }
            $image = null;
            $mimeMap = [
                "image/png" => "png",
                "image/jpeg" => "jpg",
                "image/jpg" => "jpg"
            ];

            if($request->profilePicture) {
                $file = Base64FileService::saveBase64File($request->profilePicture, $mimeMap, 'mahasiswa');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['profilePicture' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'email' => $request->email,
                'name' => $request->name,
                'status' => $request->status,
                'identityNumber' => $request->nim,
            ];

            if($request->password) {
                $data['password'] = Hash::make($request->password);
            }

            if(!$user->email_verfied_at) {
                $data['email_verified_at'] = now();
            }

            $user->update($data);

            $dataMahasiswa = [
                'userId' => $user->id,
                'gender' => $request->gender,
                'phoneNumber' => $request->phoneNumber,
                'facultyId' => $request->faculty,
                'studyProgramId' => $request->department,
                'validUntil' => $request->validUntil,
            ];

            if($request->profilePicture) {
                $data['profilePicture'] = $image;
            }

            Student::find($user->student->id)->update($dataMahasiswa);

            $dataToShow = array_merge($data, $dataMahasiswa);

            DB::commit();

            return $this->res->successResponse('Data mahasiswa berhasil diperbarui', $dataToShow, 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::where('userId', $id)->with(['teacher'])->first();
        if(!$user) {
            return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return $this->res->successResponse('Data mahasiswa berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function sample_export()
    {
        $programStudy = StudyProgram::all()->toArray();
        $data = [];
        foreach($programStudy as $index => $value) {
            $data[] = [
                'No' => $index + 1,
                'Nama Program Studi' => $value['name']
            ];
        }
        return Excel::download(new SampleExport($data), 'sample_data_mahasiswa.xlsx');
    }

    public function mahasiswa_export()
    {
        $users = User::with('student')->orderByDesc('id')->where('role', 'Student')->get();
        $items = $users->map(function ($user, $index) {
            return [
                "No" => $index + 1,
                "Nama" =>$user->name,
                "Email" => $user->email,
                "NIM" => $user->identityNumber,
                "Jenis Kelamin" => $user->student->gender == 'L' ? 'Laki - Laki' : ($user->student->gender == 'P' ? 'Perempuan' : '-'),
                "No. HP" => $user->student->phoneNumber,
                "Status" => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                "Fakultas" => $user->student->fakultas->name,
                "Program Studi" => $user->student->prodi->name,
                "Tanggal Bergabung" => date('d/m/Y', strtotime($user->created_at))
            ];
        });
        return Excel::download(new DataExport($items), 'data_mahasiswa.xlsx');
    }

    public function mahasiswa_import(Request $request)
    {
        $request->validate([
            'dataImport' => 'required|string',
        ]);

        try {
            // Pisahkan metadata (prefix) dari base64
            if (str_contains($request->dataImport, ';base64,')) {
                [, $base64Data] = explode(';base64,', $request->dataImport);
            } else {
                return $this->res->errorResponse("Format base64 tidak valid", [], 422);
            }

            // Decode base64 menjadi data mentah
            $fileContent = base64_decode($base64Data, true);
            if ($fileContent === false) {
                return $this->res->errorResponse("Data base64 tidak valid", [], 422);
            }

            // Simpan ke file sementara
            $tempPath = storage_path('app/temp_import.xlsx');
            file_put_contents($tempPath, $fileContent);

            // Import file Excel dari path
            Excel::import(new MahasiswaImport, $tempPath);

            // Hapus file setelah import selesai
            unlink($tempPath);

            return $this->res->successResponse('Data mahasiswa berhasil diimport', [], 200);
        } catch (\Exception $e) {
            Log::error('Import Excel Error: ' . $e->getMessage());
            return $this->res->errorResponse("Beberapa data gagal diimport", [], 422);
        }
    }


    public function __rules(string $type, Request $request, $user = null) {
        $message = [
            "name.string" =>"Nama tidak valid.",
            "name.min" =>"Nama minimal harus memiliki 3 karakter.",
            "name.max" =>"Nama maksimal harus memiliki 255 karakter.",
            "name.required" =>"Nama wajib diisi.",
            "nim.string" =>"NIM tidak valid.",
            "nim.min" =>"NIM minimal harus memiliki 3 karakter.",
            "nim.max" =>"NIM maksimal harus memiliki 255 karakter.",
            "nim.required" =>"NIM wajib diisi.",
            "nim.unique" => "NIM sudah pernah digunakan",
            "gender.string" =>"Jenis kelamin tidak valid.",
            "gender.in" =>"Jenis kelamin harus di antara Laki - Laki atau Perempuan.",
            "gender.required" =>"Jenis kelamin wajib diisi.",
            "phoneNumber.string" =>"Nomor telepon tidak valid.",
            "phoneNumber.min" =>"Nomor telepon minimal harus memiliki 10 karakter.",
            "phoneNumber.max" =>"Nomor telepon maksimal harus memiliki 18 karakter.",
            "phoneNumber.required" =>"Nomor telepon wajib diisi.",
            "email.string" =>"Email tidak valid.",
            "email.email" =>"Email harus berupa alamat email yang valid.",
            "email.max" =>"Email maksimal harus memiliki 255 karakter.",
            "email.required" =>"Email wajib diisi.",
            "email.unique" =>"Email sudah pernah digunakan.",
            "password.string" =>"Password tidak valid.",
            "password.min" =>"Password minimal harus memiliki 6 karakter.",
            "password.max" =>"Password maksimal harus memiliki 255 karakter.",
            "password.required" =>"Password wajib diisi.",
            "status.string" =>"Status tidak valid.",
            "status.in" =>"Status harus di antara 'Active' atau 'InActive'.",
            "status.required" =>"Status wajib diisi.",
            "validUntil.string" =>"Masa berlaku tidak valid",
            "validUntil.required" =>"Masa berlaku wajib diisi.",

            "faculty.string" =>"ID fakultas tidak valid.",
            "faculty.max" =>"ID fakultas maksimal harus memiliki 255 karakter.",
            "faculty.required" =>"ID fakultas wajib diisi.",

            "department.string" =>"ID program studi tidak valid.",
            "department.max" =>"ID program studi maksimal harus memiliki 255 karakter.",
            "department.required" =>"ID program studi wajib diisi.",

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'nim' => ['required', 'string', 'min:3', 'max:255', 'unique:users,identityNumber'],
                'gender' => ['required', 'string', 'in:L,P'],
                'phoneNumber' => ['required', 'string', 'min:10', 'max:18'],
                'email' => ['required', 'email', 'min:3', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'status' => ['required', 'string', 'in:Active,InActive'],
                'validUntil' => ['required', 'string'],
                'faculty' => ['required', 'numeric'],
                'department' => ['required', 'numeric'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'gender' => ['required', 'string', 'in:L,P'],
                'phoneNumber' => ['required', 'string', 'min:10', 'max:18'],
                'status' => ['required', 'string', 'in:Active,InActive'],
                'validUntil' => ['required', 'string'],
                'faculty' => ['required', 'numeric'],
                'department' => ['required', 'numeric'],
            ];

            if($request->email !== $user->email) {
                $rules['email'] = ['required', 'email', 'min:3', 'max:255', 'unique:users,email'];
            }
            if($request->nim !== $user->identityNumber) {
                $rules['nim'] = ['required', 'string', 'min:3', 'max:255', 'unique:users,identityNumber'];
            }
            if($request->password) {
                $rules['password'] = ['required', 'string', 'min:6', 'max:255'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
