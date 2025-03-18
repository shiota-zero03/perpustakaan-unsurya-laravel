<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use App\Exports\Buku\BukuDigitalExport;
use App\Exports\Buku\BukuDigitalSampleExport;
use App\Imports\BukuDigitalImport;
use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\MasterBuku;
use App\Models\Buku;

class BukuDigitalController extends Controller
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
        $judul = $request->input('judul');
        $penulis = $request->input('penulis');
        $tahun = $request->input('tahun');

        $query = MasterBuku::with('buku')->orderByDesc('id')->where('type', 'Buku Digital');

        if (!empty($judul)) {
            $query->whereHas('buku', function ($q) use ($judul) {
                $q->where('judul', 'like', "%{$judul}%");
            });
        }
        if (!empty($penulis)) {
            $query->whereHas('buku', function ($q) use ($penulis) {
                $q->where('penulis', 'like', "%{$penulis}%");
            });
        }
        if (!empty($tahun)) {
            $query->whereHas('buku', function ($q) use ($tahun) {
                $q->where('tahun_terbit', 'like', "%{$tahun}%");
            });
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->book_id,
                'cover' =>  $user->buku->cover,
                'judul' => $user->buku->judul,
                'penulis' => $user->buku->penulis,
                'tahun_terbit' => $user->buku->tahun_terbit,
                'link_book' => $user->buku->link_book,
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

        return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);
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

            if($request->cover) {
                $file = Base64FileService::saveBase64File($request->cover, $mimeMap, 'buku');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['cover' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }

            $data = [
                'book_id' => Str::uuid(),
                'type' => "Buku Digital",
            ];

            $user = MasterBuku::create($data);

            $dataMahasiswa = [
                'book_id' => $user->id,
                'cover' => $image,
                'judul' => $request->judul,
                'penulis' => $request->penulis,
                'penerbit' => $request->penerbit,
                'tahun_terbit' => $request->tahun_terbit,
                'isbn' => $request->isbn,
                'link_book' => $request->link_book,
            ];

            Buku::create($dataMahasiswa);

            $dataToShow = array_merge($data, $dataMahasiswa);

            DB::commit();

            return $this->res->successResponse('Data buku berhasil ditambahkan', $dataToShow, 201);

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

            $user = MasterBuku::where('book_id', $id)->where('type', 'Buku Digital')->with(['buku'])->first();
            if(!$user) {
                return $this->res->errorResponse("Buku dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->book_id,
                'cover' => $user->buku->cover,
                'judul' => $user->buku->judul,
                'penulis' => $user->buku->penulis,
                'penerbit' => $user->buku->penerbit,
                'tahun_terbit' => $user->buku->tahun_terbit,
                'isbn' => $user->buku->isbn,
                'link_book' => $user->buku->link_book,
            ];

            DB::commit();

            return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);

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
        $arraySelected = ['deleted'];
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
                $userUpdate = MasterBuku::where('book_id', $value)->with(['buku'])->first();
                if(!$userUpdate) {
                    $dataId[] = $value;
                } else {
                    if($request->action == "deleted") {
                        $userUpdate->delete();
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
        $user = MasterBuku::where('book_id', $id)->with(['buku'])->first();
        if(!$user) {
            return $this->res->errorResponse("Buku dengan id {$id} tidak ditemukan", [], 404);
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

            if($request->cover) {
                $file = Base64FileService::saveBase64File($request->cover, $mimeMap, 'buku');
                if(!$file['success']) {
                    return $this->res->errorResponse('Format file yang anda kirim tidak dapat diproses', ['cover' => 'Format file tidak sesuai'], 422);
                }
                $image = $file['filePath'];
            }


            $dataMahasiswa = [
                'cover' => $image,
                'judul' => $request->judul,
                'penulis' => $request->penulis,
                'penerbit' => $request->penerbit,
                'tahun_terbit' => $request->tahun_terbit,
                'isbn' => $request->isbn,
                'link_book' => $request->link_book,
            ];

            if($request->cover) {
                $dataMahasiswa['cover'] = $image;
            }

            Buku::find($user->buku->id)->update($dataMahasiswa);

            $dataToShow = array_merge($dataMahasiswa);

            DB::commit();

            return $this->res->successResponse('Data buku berhasil diperbarui', $dataToShow, 200);

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
        $user = MasterBuku::where('book_id', $id)->with(['buku'])->first();
        if(!$user) {
            return $this->res->errorResponse("Buku dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return $this->res->successResponse('Data buku berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function sample_export()
    {
        return Excel::download(new BukuDigitalSampleExport(), 'sample_data_buku_digital.xlsx');
    }

    public function buku_digital_export()
    {
        $users = MasterBuku::where('type', 'Buku Digital')->with(['buku'])->get();
        $items = $users->map(function ($user, $index) {
            return [
                "No" => $index + 1,
                "Judul Buku" => $user->buku->judul,
                "Penulis" => $user->buku->penulis,
                "Penerbit" => $user->buku->penerbit,
                "Tahun Terbit" => $user->buku->tahun_terbit,
                "ISBN" => $user->buku->isbn,
                "URL Buku" => $user->buku->link_book,
            ];
        });
        return Excel::download(new BukuDigitalExport($items), 'data_export.xlsx');
    }

    public function buku_digital_import(Request $request)
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
            Excel::import(new BukuDigitalImport, $tempPath);

            // Hapus file setelah import selesai
            unlink($tempPath);

            return $this->res->successResponse('Data buku berhasil diimport', [], 200);
        } catch (\Exception $e) {
            Log::error('Import Excel Error: ' . $e->getMessage());
            return $this->res->errorResponse("Beberapa data gagal diimport", [], 422);
        }
    }


    public function __rules(string $type, Request $request, $user = null) {
        $message = [

            "judul.string" => "Judul tidak valid.",
            "judul.max" => "Judul maksimal harus memiliki 255 karakter.",
            "judul.required" => "Judul wajib diisi.",

            "penulis.string" => "Penulis tidak valid.",
            "penulis.max" => "Penulis maksimal harus memiliki 255 karakter.",
            "penulis.required" => "Penulis wajib diisi.",

            "penerbit.string" => "Penerbit tidak valid.",
            "penerbit.max" => "Penerbit maksimal harus memiliki 255 karakter.",
            "penerbit.required" => "Penerbit wajib diisi.",

            "tahun_terbit.numeric" => "Tahun terbit tidak valid.",
            "tahun_terbit.required" => "Tahun terbit wajib diisi.",

            "isbn.string" => "ISBN tidak valid.",
            "isbn.max" => "ISBN maksimal harus memiliki 255 karakter.",
            "isbn.required" => "ISBN wajib diisi.",

            "link_book.string" => "URL Buku tidak valid.",
            "link_book.max" => "URL Buku maksimal harus memiliki 255 karakter.",
            "link_book.required" => "URL Buku wajib diisi.",

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                "judul" => ['required', 'string', 'max:255'],
                "penulis" => ['required', 'string', 'max:255'],
                "penerbit" => ['required', 'string', 'max:255'],
                "tahun_terbit" => ['required', 'numeric'],
                "isbn" => ['required', 'string', 'max:255'],
                "link_book" => ['required', 'string', 'max:255'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                "judul" => ['required', 'string', 'max:255'],
                "penulis" => ['required', 'string', 'max:255'],
                "penerbit" => ['required', 'string', 'max:255'],
                "tahun_terbit" => ['required', 'numeric'],
                "isbn" => ['required', 'string', 'max:255'],
                "link_book" => ['required', 'string', 'max:255'],
            ];

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
