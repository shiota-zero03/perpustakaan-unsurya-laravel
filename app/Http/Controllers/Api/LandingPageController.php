<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Banner;
use App\Models\News;
use App\Models\Setting;
use App\Models\MasterBuku;
use App\Resources\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    protected $res;
    public function __construct( ApiResponse $res )
    {
        $this->res = $res;
    }
    public function banner()
    {
        $data = Banner::all();
        return $this->res->successResponse('Data banner berhasil didapatkan', $data->toArray(), 200);
    }
    public function profil()
    {
        $set = Setting::first();
        $data = [
            'profil' => $set->profil,
            'petunjuk' => $set->petunjuk,
            'prosedur' => $set->prosedur,
        ];
        return $this->res->successResponse('data setting berhasil didapatkan', $data, 201);
    }
    public function news(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $title = $request->input('title');
        $author = $request->input('author');

        $query = News::orderByDesc('created');

        if (!empty($title)) {
            $query->where('title', 'like', "%{$title}%");
        }
        if (!empty($author)) {
            $query->where('author', 'like', "%{$author}%");
        }
        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'title' => $user->title,
                'author' => $user->author,
                'created' => $user->created,
                'tags' => $user->tags,
                'picture' => $user->picture,
                'content' => $user->content,
                'slug' => $user->slug
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
                'hasMore' => $users->currentPage() < $users->lastPage()
            ]
        ];

        return $this->res->successResponse('Data berita berhasil didapatkan', $data, 200);
    }
    public function news_detail(string $slug)
    {
        try {
            DB::beginTransaction();

            $user = News::where('slug', $slug)->first();
            if(!$user) {
                return $this->res->errorResponse("Berita dengan slug {$slug} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->id,
                'title' => $user->title,
                'author' => $user->author,
                'created' => $user->created,
                'tags' => $user->tags,
                'picture' => $user->picture,
                'content' => $user->content,
                'slug' => $user->slug
            ];

            DB::commit();

            return $this->res->successResponse('Data berita berhasil didapatkan', $data, 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }
    public function repository(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $nim = $request->input('nim');
        $judul = $request->input('judul');
        $penulis = $request->input('penulis');
        $tahun = $request->input('tahun');

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

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->book_id,
                'penulis' => $user->karya->penulis,
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
                'hasMore' => $users->currentPage() < $users->lastPage()
            ]
        ];

        return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);
    }
    public function repository_detail(string $id)
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

    public function katalog(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $judul = $request->input('judul');
        $penulis = $request->input('penulis');
        $tahun = $request->input('tahun');
        $prodi = $request->input('prodi');

        $query = MasterBuku::with('buku')->orderByDesc('id')->whereIn('type', ['Buku Digital', 'Buku Fisik']);

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
        if (!empty($prodi)) {
            $query->whereHas('buku', function ($q) use ($prodi) {
                $q->whereHas('prodi', function ($p) use ($prodi) {
                    $p->where('name', 'like', "%{$prodi}%");
                });
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
                'hasMore' => $users->currentPage() < $users->lastPage()
            ]
        ];

        return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);
    }
    public function katalog_detail(string $id)
    {
        try {
            DB::beginTransaction();

            $user = MasterBuku::where('book_id', $id)->whereIn('type', ['Buku Digital', 'Buku Fisik'])->with(['buku'])->first();
            if(!$user) {
                return $this->res->errorResponse("Buku dengan id {$id} tidak ditemukan", [], 404);
            }


            $data = [
                'id' => $user->book_id,
                'type' => $user->type,
                'no_urut' => $user->buku->no_urut,
                'cover' => $user->buku->cover,
                'kode_klasifikasi' => $user->buku->kode_klasifikasi,
                'judul' => $user->buku->judul,
                'penulis' => $user->buku->penulis,
                'penerbit' => $user->buku->penerbit,
                'tahun_terbit' => $user->buku->tahun_terbit,
                'isbn' => $user->buku->isbn,
                'tanggal_masuk' => $user->buku->tanggal_masuk,
                'kode_rak' => $user->buku->kode_rak,
                'stok' => $user->buku->stok,
                'denda_harian' => $user->buku->denda_harian,
                'link_book' => $user->buku->link_book,
                'book_description' => $user->buku->book_description,
                'prodi' => [
                    'id' => $user->buku->prodi->id ?? '',
                    'name' => $user->buku->prodi->name ?? ''
                ]
            ];


            DB::commit();

            return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }
}
