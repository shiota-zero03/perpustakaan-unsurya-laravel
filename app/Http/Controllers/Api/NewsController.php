<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Resources\Responses\ApiResponse;

use App\Models\News;

class NewsController extends Controller
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
        $author = $request->input('author');

        $query = News::orderByDesc('id');

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
            ]
        ];

        return $this->res->successResponse('Data berita berhasil didapatkan', $data, 200);
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

            if ($request->hasFile('picture')) {
                $file = $request->file('picture');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/news');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/news/{$fileName}");
            }

            $slug = Str::slug($request->title . '-' . strtotime(now()));

            $data = [
                'title' => $request->title,
                'author' => $request->author,
                'created' => $request->created,
                'picture' => $imageUrl,
                'content' => $request->content,
                'slug' => $slug
            ];

            $create = News::create($data);

            DB::commit();

            return $this->res->successResponse('Data berita berhasil ditambahkan', $create->toArray(), 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        $user = News::where('slug', $slug)->first();
        if(!$user) {
            return $this->res->errorResponse("Berita dengan slug {$slug} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request, $user);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }
            $imageUrl = null;

            if ($request->hasFile('picture')) {
                $file = $request->file('picture');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('assets/picture/news');
                $file->move($destinationPath, $fileName);
                $imageUrl = url("assets/picture/news/{$fileName}");
            }

            $slug = Str::slug($request->title . '-' . strtotime(now()));

            $data = [
                'title' => $request->title,
                'author' => $request->author,
                'created' => $request->created,
                'content' => $request->content,
                'slug' => $slug
            ];

            if($request->picture) {
                $data['picture'] = $imageUrl;
            }

            News::find($user->id)->update($data);

            $dataToShow = array_merge($data);

            DB::commit();

            return $this->res->successResponse('Data berita berhasil diperbarui', $dataToShow, 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        $user = News::where('slug', $slug)->first();
        if(!$user) {
            return $this->res->errorResponse("Berita dengan slug {$slug} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return $this->res->successResponse('Data berita berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function __rules(string $type, Request $request, $user = null) {
        $message = [

            "title.string" => "Judul tidak valid.",
            "title.max" => "Judul maksimal harus memiliki 255 karakter.",
            "title.required" => "Judul wajib diisi.",

            "author.string" => "Penulis tidak valid.",
            "author.max" => "Penulis maksimal harus memiliki 255 karakter.",
            "author.required" => "Penulis wajib diisi.",

            "created.string" => "Tanggal berita tidak valid.",
            "created.max" => "Tanggal berita maksimal harus memiliki 255 karakter.",
            "created.required" => "Tanggal berita wajib diisi.",

            "content.string" => "Isi berita tidak valid.",
            "content.required" => "Isi berita wajib diisi.",

            "picture.file" =>"Gambar thumbnail tidak valid.",
            "picture.required" =>"Gambar thumbnail wajib diisi.",
            "picture.mimes" => "Format gambar yang diizinkan adalah png, jpg atau jpeg"

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'title' => ['required', 'string', 'max:255'],
                'author' => ['required', 'string', 'max:255'],
                'created' => ['required', 'string', 'max:255'],
                'picture' => ['required', 'file', 'mimes:png,jpg,jpeg'],
                'content' => ['required', 'string']
            ], $message);
        } elseif ($type == 'update') {

            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'author' => ['required', 'string', 'max:255'],
                'created' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string']
            ];

            if($request->picture) {
                $rules['picture'] = ['required', 'file', 'mimes:png,jpg,jpeg'];
            }

            return Validator::make($request->all(), $rules, $message);
        }
    }
}
