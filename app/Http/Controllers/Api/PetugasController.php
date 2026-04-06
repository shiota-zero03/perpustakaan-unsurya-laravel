<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\User;
use App\Models\Admin;

class PetugasController extends Controller
{
    protected $user, $admin, $res;
    public function __construct( User $user, Admin $admin, ApiResponse $res )
    {
        $this->user = $user;
        $this->admin = $admin;
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
        $email = $request->input('email');
        $status = $request->input('status');

        $query = User::with('admin')->orderByDesc('id')->where('role', 'Admin');

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        if (!empty($email)) {
            $query->where('email', 'like', "%{$email}%");
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

        return $this->res->successResponse('Data admin berhasil didapatkan', $data, 200);
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
            $imageUrl = null;

            if ($request->hasFile('profilePicture')) {
                $file = $request->file('profilePicture');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/profile');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/profile/{$fileName}");
            }

            $data = [
                'userId' => Str::uuid(),
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'role' => 'Admin',
                'status' => $request->status,
                'email_verified_at' => now(),
            ];

            $user = User::create($data);

            $dataDosen = [
                'userId' => $user->id,
                'profilePicture' => $imageUrl,
                'gender' => $request->gender,
                'position' => $request->position,
            ];

            Admin::create($dataDosen);

            $dataToShow = array_merge($data, $dataDosen);

            DB::commit();

            return $this->res->successResponse('Data petugas berhasil ditambahkan', $dataToShow, 201);

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

            $user = User::where('userId', $id)->with(['admin'])->first();
            if(!$user) {
                return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status == 'Active' ? 'Aktif' : ($user->email_verified_at ? 'Tidak Aktif' : 'Belum Diverifikasi'),
                'gender' => $user->admin->gender,
                'position' => $user->admin->position,
                'waktu_terdaftar' => $user->created_at,
                'profile_picture' => $user->admin->profilePicture
            ];

            DB::commit();

            return $this->res->successResponse('Data petugas berhasil didapatkan', $data, 200);

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
                $userUpdate = User::where('userId', $value)->with(['admin'])->first();
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::where('userId', $id)->with(['admin'])->first();
        if(!$user) {
            return $this->res->errorResponse("User dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $user);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }
            $imageUrl = null;

            if ($request->hasFile('profilePicture')) {
                $file = $request->file('profilePicture');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/profile');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/profile/{$fileName}");
            }

            $data = [
                'email' => $request->email,
                'name' => $request->name,
                'status' => $request->status,
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
                'position' => $request->position,
            ];

            if($request->profilePicture) {
                $dataDosen['profilePicture'] = $imageUrl;
            }

            Admin::find($user->admin->id)->update($dataDosen);

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
        $user = User::where('userId', $id)->with(['admin'])->first();
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

    public function __rules(string $type, Request $request, $user = null) {
        $message = [
            "name.string" =>"Nama tidak valid.",
            "name.min" =>"Nama minimal harus memiliki 3 karakter.",
            "name.max" =>"Nama maksimal harus memiliki 255 karakter.",
            "name.required" =>"Nama wajib diisi.",
            "gender.string" =>"Jenis kelamin tidak valid.",
            "gender.in" =>"Jenis kelamin harus di antara Laki - Laki atau Perempuan.",
            "gender.required" =>"Jenis kelamin wajib diisi.",
            "email.string" =>"Email tidak valid.",
            "email.email" =>"Email harus berupa alamat email yang valid.",
            "email.max" =>"Email maksimal harus memiliki 255 karakter.",
            "email.required" =>"Email wajib diisi.",
            "email.unique" =>"Email sudah pernah digunakan.",
            "password.string" =>"Password tidak valid.",
            "password.min" =>"Password minimal harus memiliki 6 karakter.",
            "password.max" =>"Password maksimal harus memiliki 255 karakter.",
            "password.required" =>"Password wajib diisi.",
            "position.string" =>"Jabatan tidak valid.",
            "position.min" =>"Jabatan minimal harus memiliki 6 karakter.",
            "position.max" =>"Jabatan maksimal harus memiliki 255 karakter.",
            "position.required" =>"Jabatan wajib diisi.",
            "status.string" =>"Status tidak valid.",
            "status.in" =>"Status harus di antara 'Active' atau 'InActive'.",
            "status.required" =>"Status wajib diisi.",
            "profilePicture.file" =>"Gambar tidak valid.",
            "profilePicture.required" =>"Gambar wajib diisi.",
            "profilePicture.mimes" => "Format gambar yang diizinkan adalah png, jpg atau jpeg"

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'gender' => ['required', 'string', 'in:L,P'],
                'email' => ['required', 'email', 'min:3', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'position' => ['required', 'string', 'min:6', 'max:255'],
                'status' => ['required', 'string', 'in:Active,InActive'],
                'profilePicture' => ['required', 'file', 'mimes:png,jpg,jpeg'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'gender' => ['required', 'string', 'in:L,P'],
                'position' => ['required', 'string', 'min:6', 'max:255'],
                'status' => ['required', 'string', 'in:Active,InActive'],
            ];

            if($request->profilePicture) {
                $rules['profilePicture'] = ['required', 'file', 'mimes:png,jpg,jpeg'];
            }
            if($request->email !== $user->email) {
                $rules['email'] = ['required', 'email', 'min:3', 'max:255', 'unique:users,email'];
            }
            if($request->password) {
                $rules['password'] = ['required', 'string', 'min:6', 'max:255'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
