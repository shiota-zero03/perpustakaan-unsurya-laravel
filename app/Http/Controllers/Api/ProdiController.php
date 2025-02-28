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

use App\Resources\Responses\ApiResponse;

use App\Models\Faculty;
use App\Models\StudyProgram;

class ProdiController extends Controller
{
    protected $fakultas, $prodi, $res;
    public function __construct( Faculty $fakultas, StudyProgram $prodi, ApiResponse $res )
    {
        $this->prodi = $prodi;
        $this->fakultas = $fakultas;
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
        $code = $request->input('code');
        $fakultas = $request->input('fakultas');

        $query = StudyProgram::orderByDesc('id')->with(['fakultas']);

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        if (!empty($code)) {
            $query->where('code', 'like', "%{$code}%");
        }

        if (!empty($fakultas)) {
            $query->where('fakultas_id', 'like', "%{$fakultas}%");
        }

        $datas = $query->paginate($limit, ['*'], 'page', $page);

        $items = $datas->map(function ($user) {
            return [
                'id' =>  $user->id,
                'name' =>  $user->name,
                'code' => $user->code,
                'fakultas_id' => $user->fakultas_id,
                'fakultas' => [
                    'id' => $user->fakultas->id,
                    'name' => $user->fakultas->name,
                    'code' => $user->fakultas->code,
                ]
            ];
        });

        $data = [
            'data' => $items,
            'pagination' => [
                'from' => ($datas->currentPage() - 1) * $datas->perPage() + 1,
                'to' => min($datas->currentPage() * $datas->perPage(), $datas->total()),
                'currentPage' => $datas->currentPage(),
                'totalPages' => $datas->lastPage(),
                'totalItems' => $datas->total(),
                'limit' => $datas->perPage(),
            ]
        ];

        return $this->res->successResponse('Data program studi berhasil didapatkan', $data, 200);

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

            if(!Faculty::find($request->fakultasId)) {
                return $this->res->errorResponse('Fakultas yang anda masukkan tidak ditemukan', [], 404);
            }

            $data = [
                'fakultas_id' => $request->fakultasId,
                'name' => $request->name,
                'code' => $request->code
            ];

            StudyProgram::create($data);

            DB::commit();

            return $this->res->successResponse('Data program studi berhasil ditambahkan', $data, 201);

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

            $datas = StudyProgram::find($id);
            if(!$datas) {
                return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $datas->id,
                'name' => $datas->name,
                'code' => $datas->code,
                'fakultas_id' => $datas->fakultas_id,
                'fakultas' => [
                    'id' => $datas->fakultas->id,
                    'name' => $datas->fakultas->name,
                    'code' => $datas->fakultas->code,
                ]
            ];

            DB::commit();

            return $this->res->successResponse('Data program studi berhasil didapatkan', $data, 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $datas = StudyProgram::find($id);
        if(!$datas) {
            return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $datas);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $data = [
                'fakultas_id' => $request->fakultasId,
                'name' => $request->name,
                'code' => $request->code,
            ];

            $datas->update($data);

            DB::commit();

            return $this->res->successResponse('Data program studi berhasil diperbarui', $data, 200);

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
        $datas = StudyProgram::find($id);
        if(!$datas) {
            return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $datas->delete();

            DB::commit();

            return $this->res->successResponse('Data program studi berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function __rules(string $type, Request $request, $data = null) {
        $message = [
            "fakultasId.string" =>"ID fakultas tidak valid.",
            "fakultasId.max" =>"ID fakultas maksimal harus memiliki 255 karakter.",
            "fakultasId.required" =>"ID fakultas wajib diisi.",
            "name.string" =>"Nama program studi tidak valid.",
            "name.min" =>"Nama program studi minimal harus memiliki 3 karakter.",
            "name.max" =>"Nama program studi maksimal harus memiliki 255 karakter.",
            "name.required" =>"Nama program studi wajib diisi.",
            "code.string" =>"Kode program studi tidak valid.",
            "code.max" =>"Kode program studi maksimal harus memiliki 255 karakter.",
            "code.required" =>"Kode program studi wajib diisi.",
            "code.unique" => "Kode program studi sudah pernah digunakan",

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'fakultasId' => ['required', 'numeric'],
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'code' => ['required', 'string', 'max:255', 'unique:faculties,code'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'fakultasId' => ['required', 'numeric'],
                'name' => ['required', 'string', 'min:3', 'max:255'],
            ];

            if($request->code !== $data->code) {
                $rules['code'] = ['required', 'string', 'max:255', 'unique:faculties,code'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
