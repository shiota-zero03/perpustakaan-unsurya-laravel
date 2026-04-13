<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use App\Exports\Buku\TASExport;
use App\Exports\Buku\BukuFisikSampleExport;
use App\Exports\Buku\TASkripsiSampleExport;
use App\Imports\BukuFisikImport;
use App\Imports\TASkripsiImport;
use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\MasterBuku;
use App\Models\KaryaTulis;
use App\Models\DokumenKaryaTulis;
use App\Models\Buku;
use App\Models\StudyProgram;

class BukuKaryaTulisController extends Controller
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
        $nim = $request->input('nim');
        $judul = $request->input('judul');
        $penulis = $request->input('penulis');
        $tahun = $request->input('tahun');
        $prodi = $request->input('prodi');

        $query = MasterBuku::with('karya')->orderByDesc('id')->where('type', 'Karya Tulis');

        if (!empty($judul)) {
            $query->whereHas('karya', function ($q) use ($judul) {
                $q->where('judul', 'like', "%{$judul}%");
            });
        }
        if (!empty($penulis)) {
            $query->whereHas('karya', function ($q) use ($penulis) {
                $q->where('penulis', 'like', "%{$penulis}%");
            });
        }
        if (!empty($nim)) {
            $query->whereHas('karya', function ($q) use ($nim) {
                $q->where('nim', 'like', "%{$nim}%");
            });
        }
        if (!empty($tahun)) {
            $query->whereHas('karya', function ($q) use ($tahun) {
                $q->where('tahun_terbit', 'like', "%{$tahun}%");
            });
        }
        if (!empty($prodi)) {
            $query->whereHas('karya', function ($q) use ($prodi) {
                $q->whereHas('prodi', function ($p) use ($prodi) {
                    $p->where('name', 'like', "%{$prodi}%");
                });
            });
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->book_id,
                'penulis' => $user->karya->penulis,
                'program_studi' => $user->karya->prodi ? $user->karya->prodi->name : null,
                'nim' => $user->karya->nim,
                'cover' =>  $user->karya->cover,
                'judul' => $user->karya->judul,
                'tahun_terbit' => $user->karya->tahun_terbit,
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
            $imageUrl = null;

            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/buku');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/buku/{$fileName}");
            }

            $data = [
                'book_id' => Str::uuid(),
                'type' => "Karya Tulis",
            ];

            $user = MasterBuku::create($data);

            $dataMahasiswa = [
                'book_id' => $user->id,
                'cover' => $imageUrl,
                'judul' => $request->judul,
                'penulis' => $request->penulis,
                'nim' => $request->nim,
                'facultyId' => $request->facultyId,
                'studyProgramId' => $request->studyProgramId,
                'tahun_terbit' => $request->tahun_terbit,
                'jenis' => $request->jenis,
                'no_urut' => $request->no_urut,
                'kode_klasifikasi' => $request->kode_klasifikasi,
                'tanggal_masuk' => $request->tanggal_masuk,
                'kode_rak' => $request->kode_rak,
                'abstrak' => $request->abstrak,
            ];

            $karyaTulisStore = KaryaTulis::create($dataMahasiswa);

            if ($request->hasFile('document')) {
                foreach ($request->file('document') as $index => $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $destinationPath = public_path('assets/pdf/buku');
                    $file->move($destinationPath, $fileName);
                    $filePDFDocument = url("assets/pdf/buku/{$fileName}");

                    DokumenKaryaTulis::create([
                        'karya_id' => $karyaTulisStore->id,
                        'judul_dokumen' => $request->document_title[$index] ?? 'Tanpa Judul',
                        'file_dokumen' => $filePDFDocument,
                    ]);
                }
            }

            $dataToShow = array_merge($data, $dataMahasiswa);

            DB::commit();

            return $this->res->successResponse('Data karya tulis berhasil ditambahkan', $dataToShow, 201);


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

            $user = MasterBuku::where('book_id', $id)->where('type', 'Karya Tulis')->with(['karya'])->first();
            if(!$user) {
                return $this->res->errorResponse("Buku dengan id {$id} tidak ditemukan", [], 404);
            }

            $dokumen = [];
            foreach ($user->karya->dokumen as $key => $document) {
                $dokumen[] = [
                    "id" => $document->id,
                    "title" => $document->judul_dokumen,
                    "file" => $document->file_dokumen
                ];
            }

            $data = [
                'id' => $user->book_id,
                'no_urut' => $user->karya->no_urut,
                'cover' => $user->karya->cover,
                'kode_klasifikasi' => $user->karya->kode_klasifikasi,
                'judul' => $user->karya->judul,
                'penulis' => $user->karya->penulis,
                'nim' => $user->karya->nim,
                'faculty' => [
                    'id' => $user->karya->fakultas->id ?? '',
                    'name' => $user->karya->fakultas->name ?? ''
                ],
                'department' => [
                    'id' => $user->karya->prodi->id ?? '',
                    'name' => $user->karya->prodi->name ?? ''
                ],
                'tahun_terbit' => $user->karya->tahun_terbit,
                'jenis' => $user->karya->jenis,
                'no_urut' => $user->karya->no_urut,
                'kode_klasifikasi' => $user->karya->kode_klasifikasi,
                'tanggal_masuk' => $user->karya->tanggal_masuk,
                'kode_rak' => $user->karya->kode_rak,
                'abstrak' => $user->karya->abstrak,
                'dokumen' => $dokumen
            ];

            DB::commit();

            return $this->res->successResponse('Data karya tulis berhasil didapatkan', $data, 200);

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
                $userUpdate = MasterBuku::where('book_id', $value)->with(['karya'])->first();
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = MasterBuku::where('book_id', $id)->with(['karya'])->first();
        if(!$user) {
            return $this->res->errorResponse("Karya tulis dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $user);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }
            $imageUrl = null;

            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/buku');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/buku/{$fileName}");
            }


            $dataMahasiswa = [
                'judul' => $request->judul,
                'penulis' => $request->penulis,
                'nim' => $request->nim,
                'facultyId' => $request->facultyId,
                'studyProgramId' => $request->studyProgramId,
                'tahun_terbit' => $request->tahun_terbit,
                'jenis' => $request->jenis,
                'no_urut' => $request->no_urut,
                'kode_klasifikasi' => $request->kode_klasifikasi,
                'tanggal_masuk' => $request->tanggal_masuk,
                'kode_rak' => $request->kode_rak,
                'abstrak' => $request->abstrak,
            ];

            if($request->cover) {
                $dataMahasiswa['cover'] = $imageUrl;
            }

            KaryaTulis::find($user->karya->id)->update($dataMahasiswa);

            DokumenKaryaTulis::where('karya_id', $user->karya->id)->delete();

            $dataReturn = [];
            $documentsFile = $request->file('document') ?? [];
            $documentsLink = $request->input('document') ?? [];
            $documentTitles = $request->input('document_title') ?? [];

            $countLink = count($documentsLink);
            foreach($documentsLink as $docKey => $document) {
                DokumenKaryaTulis::create([
                    'karya_id' => $user->karya->id,
                    'judul_dokumen' => $documentTitles[$docKey] ?? 'Tanpa Judul',
                    'file_dokumen' => $document,
                ]);
            }

            foreach ($documentsFile as $key => $document) {
                $file = $documentsFile[$key];
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/pdf/buku');
                $file->move($destinationPath, $fileName);
                $filePDFDocument = url("assets/pdf/buku/{$fileName}");
                DokumenKaryaTulis::create([
                    'karya_id' => $user->karya->id,
                    'judul_dokumen' => $documentTitles[$key + $countLink] ?? 'Tanpa Judul',
                    'file_dokumen' => $filePDFDocument,
                ]);
            }

            $dataToShow = array_merge($dataMahasiswa);

            DB::commit();
            return $this->res->successResponse('Data karya tulis berhasil diperbarui', $dataReturn, 200);


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
        $user = MasterBuku::where('book_id', $id)->with(['karya'])->first();
        if(!$user) {
            return $this->res->errorResponse("Karya tulis dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return $this->res->successResponse('Data karya tulis berhasil dihapus', [], 200);

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
        return Excel::download(new TASkripsiSampleExport($data), 'sample_data_ta_skripsi.xlsx');
    }

    public function karya_export()
    {
        $users = MasterBuku::where('type', 'Karya Tulis')->with(['karya'])->get();
        $items = $users->map(function ($user, $index) {
            return [
                "No" => $index + 1,
                "No. Urut" => $user->karya->no_urut,
                "Kode Klasifikasi" => $user->karya->kode_klasifikasi,
                "Judul Karya" => $user->karya->judul,
                "Penulis" => $user->karya->penulis,
                "NIM" => $user->karya->nim,
                "Tahun Terbit" => $user->karya->tahun_terbit,
                "Jenis" => $user->karya->jenis,
                "Tanggal Masuk" => $user->karya->tanggal_masuk ? date('d/m/Y', strtotime($user->karya->tanggal_masuk)) : "",
                "Kode Rak" => $user->karya->kode_rak,
            ];
        });
        return Excel::download(new TASExport($items), 'data_export.xlsx');
    }

    public function ta_skripsi_import(Request $request)
    {
        set_time_limit(120);
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
            Excel::import(new TASkripsiImport, $tempPath);

            // Hapus file setelah import selesai
            unlink($tempPath);

            return $this->res->successResponse('Data TA/Skripsi berhasil diimport', [], 200);
        } catch (\Exception $e) {
            Log::error('Import Excel Error: ' . $e->getMessage());
            return $this->res->errorResponse("Beberapa data gagal diimport", [], 422);
        }
    }

    public function __rules(string $type, Request $request, $user = null) {
        $message = [
            "no_urut.string" => "Nomor urut tidak valid.",
            "no_urut.max" => "Nomor urut maksimal harus memiliki 255 karakter.",
            "no_urut.required" => "Nomor urut wajib diisi.",

            "kode_klasifikasi.string" => "Kode klasifikasi tidak valid.",
            "kode_klasifikasi.max" => "Kode klasifikasi maksimal harus memiliki 255 karakter.",
            "kode_klasifikasi.required" => "Kode klasifikasi wajib diisi.",

            "judul.string" => "Judul tidak valid.",
            "judul.max" => "Judul maksimal harus memiliki 255 karakter.",
            "judul.required" => "Judul wajib diisi.",

            "penulis.string" => "Penulis tidak valid.",
            "penulis.max" => "Penulis maksimal harus memiliki 255 karakter.",
            "penulis.required" => "Penulis wajib diisi.",

            "nim.string" => "NIM tidak valid.",
            "nim.max" => "NIM maksimal harus memiliki 255 karakter.",
            "nim.required" => "NIM wajib diisi.",

            "tahun_terbit.numeric" => "Tahun terbit tidak valid.",
            "tahun_terbit.required" => "Tahun terbit wajib diisi.",

            "tanggal_masuk.string" => "Tanggal masuk tidak valid.",
            "tanggal_masuk.max" => "Tanggal masuk maksimal harus memiliki 255 karakter.",
            "tanggal_masuk.required" => "Tanggal masuk wajib diisi.",

            "kode_rak.string" => "Kode rak tidak valid.",
            "kode_rak.max" => "Kode rak maksimal harus memiliki 255 karakter.",
            "kode_rak.required" => "Kode rak wajib diisi.",

            "abstrak.string" => "Abstrak tidak valid.",
            "abstrak.required" => "Abstrak wajib diisi.",

            "facultyId.string" =>"ID fakultas tidak valid.",
            "facultyId.max" =>"ID fakultas maksimal harus memiliki 255 karakter.",
            "facultyId.required" =>"ID fakultas wajib diisi.",

            "studyProgramId.string" =>"ID program studi tidak valid.",
            "studyProgramId.max" =>"ID program studi maksimal harus memiliki 255 karakter.",
            "studyProgramId.required" =>"ID program studi wajib diisi.",

            "jenis.string" =>"Jenis karya tulis tidak valid.",
            "jenis.max" =>"Jenis karya tulis maksimal harus di antara Skripsi, TA, Tesis, Disertasi.",
            "jenis.required" =>"Jenis karya tulis wajib diisi.",

            "cover.file" =>"Gambar tidak valid.",
            "cover.required" =>"Gambar wajib diisi.",
            "cover.mimes" => "Format gambar yang diizinkan adalah png, jpg atau jpeg"

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                "no_urut" => ['required', 'string', 'max:255'],
                "kode_klasifikasi" => ['required', 'string', 'max:255'],
                "judul" => ['required', 'string', 'max:255'],
                "penulis" => ['required', 'string', 'max:255'],
                "nim" => ['required', 'string', 'max:255'],
                "tahun_terbit" => ['required', 'numeric'],
                "tanggal_masuk" => ['required', 'string', 'max:255'],
                "kode_rak" => ['required', 'string', 'max:255'],
                "abstrak" => ['required', 'string'],
                "jenis" => ['required', 'string', 'in:Skripsi,TA,Tesis,Disertasi'],
                'facultyId' => ['required', 'numeric'],
                'studyProgramId' => ['required', 'numeric'],
                'cover' => ['required', 'file', 'mimes:png,jpg,jpeg'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                "no_urut" => ['required', 'string', 'max:255'],
                "kode_klasifikasi" => ['required', 'string', 'max:255'],
                "judul" => ['required', 'string', 'max:255'],
                "penulis" => ['required', 'string', 'max:255'],
                "nim" => ['required', 'string', 'max:255'],
                "tahun_terbit" => ['required', 'numeric'],
                "tanggal_masuk" => ['required', 'string', 'max:255'],
                "kode_rak" => ['required', 'string', 'max:255'],
                "jenis" => ['required', 'string', 'in:Skripsi,TA,Tesis,Disertasi'],
                "abstrak" => ['required', 'string'],
                'facultyId' => ['required', 'numeric'],
                'studyProgramId' => ['required', 'numeric'],
            ];

            if($request->cover) {
                $rules['cover'] = ['required', 'file', 'mimes:png,jpg,jpeg'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
