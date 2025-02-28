<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use App\Exports\Dosen\SampleExport;
use App\Exports\Dosen\DataExport;
use App\Imports\DosenImport;
use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\User;
use App\Models\Teacher;

class DosenController extends Controller
{
    protected $user, $teacher, $res;
    public function __construct( User $user, Teacher $teacher, ApiResponse $res )
    {
        $this->user = $user;
        $this->teacher = $teacher;
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
        $nidn = $request->input('nidn');
        $status = $request->input('status');

        $query = User::with('teacher')->orderByDesc('id')->where('role', 'Teacher');

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        if (!empty($nidn)) {
            $query->where('identityNumber', 'like', "%{$nidn}%");
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
                'nidn' => $user->identityNumber,
                'status' => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                'waktu_terdaftar' => $user->created_at
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

        return $this->res->successResponse('Data dosen berhasil didapatkan', $data, 200);
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
                $file = Base64FileService::saveBase64File($request->profilePicture, $mimeMap, 'dosen');
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
                'role' => 'Teacher',
                'status' => $request->status,
                'identityNumber' => $request->nidn,
                'email_verified_at' => now(),
            ];

            $user = User::create($data);

            $dataDosen = [
                'userId' => $user->id,
                'profilePicture' => $image,
                'gender' => $request->gender,
                'phoneNumber' => $request->phoneNumber,
                'validUntil' => $request->validUntil
            ];

            Teacher::create($dataDosen);

            $dataToShow = array_merge($data, $dataDosen);

            DB::commit();

            return $this->res->successResponse('Data dosen berhasil ditambahkan', $dataToShow, 201);

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

            $user = User::where('userId', $id)->with(['teacher'])->first();
            if(!$user) {
                return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'nidn' => $user->identityNumber,
                'status' => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                'gender' => $user->teacher->gender,
                'phone_number' => $user->teacher->phoneNumber,
                'valid_until' => $user->teacher->validUntil,
                'waktu_terdaftar' => $user->created_at,
                'profile_picture' => $user->teacher->profilePicture
            ];

            DB::commit();

            return $this->res->successResponse('Data dosen berhasil didapatkan', $data, 200);

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
                $userUpdate = User::where('userId', $value)->with(['teacher'])->first();
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
        $user = User::where('userId', $id)->with(['teacher'])->first();
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
                $file = Base64FileService::saveBase64File($request->profilePicture, $mimeMap, 'dosen');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['profilePicture' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'email' => $request->email,
                'name' => $request->name,
                'status' => $request->status,
                'identityNumber' => $request->nidn,
            ];

            if($request->password) {
                $data['password'] = Hash::make($request->password);
            }

            if(!$user->email_verfied_at) {
                $data['email_verified_at'] = now();
            }

            $user->update($data);

            $dataDosen = [
                'gender' => $request->gender,
                'phoneNumber' => $request->phoneNumber,
                'validUntil' => $request->validUntil
            ];

            if($request->profilePicture) {
                $data['profilePicture'] = $image;
            }

            Teacher::find($user->teacher->id)->update($dataDosen);

            $dataToShow = array_merge($data, $dataDosen);

            DB::commit();

            return $this->res->successResponse('Data dosen berhasil diperbarui', $dataToShow, 200);

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

            return $this->res->successResponse('Data dosen berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function sample_export()
    {
        return Excel::download(new SampleExport, 'sample_data_dosen.xlsx');
    }

    public function dosen_export()
    {
        $users = User::with('teacher')->orderByDesc('id')->where('role', 'Teacher')->get();
        $items = $users->map(function ($user, $index) {
            return [
                "No" => $index + 1,
                "Nama" =>$user->name,
                "Email" => $user->email,
                "NIDN" => $user->identityNumber,
                "Jenis Kelamin" => $user->teacher->gender == 'L' ? 'Laki - Laki' : ($user->teacher->gender == 'P' ? 'Perempuan' : '-'),
                "No. HP" => $user->teacher->phoneNumber,
                "Status" => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                "Tanggal Bergabung" => date('d/m/Y', strtotime($user->created_at))
            ];
        });
        return Excel::download(new DataExport($items), 'data_dosen.xlsx');
    }

    public function dosen_import(Request $request)
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
            Excel::import(new DosenImport, $tempPath);

            // Hapus file setelah import selesai
            unlink($tempPath);

            return $this->res->successResponse('Data dosen berhasil diimport', [], 200);
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
            "nidn.string" =>"NIDN tidak valid.",
            "nidn.min" =>"NIDN minimal harus memiliki 3 karakter.",
            "nidn.max" =>"NIDN maksimal harus memiliki 255 karakter.",
            "nidn.required" =>"NIDN wajib diisi.",
            "nidn.unique" => "NIDN sudah pernah digunakan",
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
            "validUntil.required" =>"Masa berlaku wajib diisi."

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'nidn' => ['required', 'string', 'min:3', 'max:255', 'unique:users,identityNumber'],
                'gender' => ['required', 'string', 'in:L,P'],
                'phoneNumber' => ['required', 'string', 'min:10', 'max:18'],
                'email' => ['required', 'email', 'min:3', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'status' => ['required', 'string', 'in:Active,InActive'],
                'validUntil' => ['required', 'string'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'gender' => ['required', 'string', 'in:L,P'],
                'phoneNumber' => ['required', 'string', 'min:10', 'max:18'],
                'status' => ['required', 'string', 'in:Active,InActive'],
                'validUntil' => ['required', 'string'],
            ];

            if($request->email !== $user->email) {
                $rules['email'] = ['required', 'email', 'min:3', 'max:255', 'unique:users,email'];
            }
            if($request->nidn !== $user->identityNumber) {
                $rules['nidn'] = ['required', 'string', 'min:3', 'max:255', 'unique:users,identityNumber'];
            }
            if($request->password) {
                $rules['password'] = ['required', 'string', 'min:6', 'max:255'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
