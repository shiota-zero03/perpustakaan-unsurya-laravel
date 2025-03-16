<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Resources\Responses\ApiResponse;

use App\Models\Banner;
use App\Services\Base64FileService;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    protected $res;
    public function __construct( ApiResponse $res )
    {
        $this->res = $res;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $title = $request->input('title');

        $query = Banner::orderByDesc('id');

        if (!empty($name)) {
            $query->where('title', 'like', "%{$title}%");
        }

        $datas = $query->paginate($limit, ['*'], 'page', $page);

        $items = $datas->map(function ($user) {
            return [
                'id' =>  $user->id,
                'title' =>  $user->title,
                'subtitle' => $user->subtitle,
                'picture' => $user->picture,
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

        return $this->res->successResponse('Data banner berhasil didapatkan', $data, 200);

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

            if($request->picture) {
                $file = Base64FileService::saveBase64File($request->picture, $mimeMap, 'banner');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['picture' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'picture' => $image
            ];

            Banner::create($data);

            DB::commit();

            return $this->res->successResponse('Data fakultas berhasil ditambahkan', $data, 201);

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

            $datas = Banner::find($id);
            if(!$datas) {
                return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $datas->id,
                'title' => $datas->title,
                'subtitle' => $datas->subtitle,
                'picture' => $datas->picture,
            ];

            DB::commit();

            return $this->res->successResponse('Data banner berhasil didapatkan', $data, 200);

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
        $datas = Banner::find($id);
        if(!$datas) {
            return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $datas);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $image = null;
            $mimeMap = [
                "image/png" => "png",
                "image/jpeg" => "jpg",
                "image/jpg" => "jpg"
            ];

            if($request->picture) {
                $file = Base64FileService::saveBase64File($request->picture, $mimeMap, 'banner');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['picture' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'title' => $request->title,
                'subtitle' => $request->subtitle,
            ];

            if($request->picture) {
                $data['picture'] = $image;
            }

            $datas->update($data);

            DB::commit();

            return $this->res->successResponse('Data fakultas berhasil diperbarui', $data, 200);

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
        $datas = Banner::find($id);
        if(!$datas) {
            return $this->res->errorResponse("Data dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $datas->delete();

            DB::commit();

            return $this->res->successResponse('Data fakultas berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function __rules(string $type, Request $request, $data = null) {
        $message = [
            "title.string" =>"Judul tidak valid.",
            "title.min" =>"Judul minimal harus memiliki 3 karakter.",
            "title.max" =>"Judul maksimal harus memiliki 255 karakter.",
            "title.required" =>"Judul wajib diisi.",
            "picture.string" =>"Gambar banner tidak valid.",
            "picture.required" =>"Gambar banner wajib diisi.",

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'title' => ['required', 'string', 'min:3', 'max:255'],
                'picture' => ['required', 'string'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'title' => ['required', 'string', 'min:3', 'max:255'],
            ];

            if($request->picture) {
                $rules['picture'] = ['required', 'string'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
