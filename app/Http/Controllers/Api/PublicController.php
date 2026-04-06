<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Visitor;
use App\Models\Banner;
use App\Models\News;
use App\Models\SecondUser;

use App\Resources\Responses\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    protected $res;
    public function __construct( ApiResponse $res )
    {
        $this->res = $res;
    }

    public function visitor_store ( Request $request )
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'type' => ['required', 'string'],
                'activity' => ['required', 'string', 'max:255']
            ],[
                'type.required' => 'Tipe pengunjung tidak boleh kosong',
                'type.string' => 'Tipe pengunjung tidak valid',
                'activity.required' => 'Kegiatan tidak boleh kosong',
                'activity.string' => 'Kegiatan tidak valid',
                'activity.max' => 'Kegiatan maksimal 255 karakter'
            ]);

            $date = Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $time = Carbon::now('Asia/Jakarta')->format('H:i:s');

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            if($request->type === "Akademisi") {
                if(!$request->member) {
                    return $this->res->errorResponse('Masukkan Nomor Identitas terlebih dahulu', [], 422);
                }
                $checkMember = SecondUser::where('user_id', $request->member)->first();
                if(!$checkMember) {
                    return $this->res->errorResponse('Pengguna tidak ditemukan', [], 404);
                }
                $visitorCheck = Visitor::where('userId', $checkMember->id)->where('date', $date)->first();

                if($visitorCheck) {
                    return $this->res->errorResponse('Anda sudah berkunjung hari ini', [], 400);
                }
                $data = [
                    'name' => $checkMember->name,
                    'id_anggota' => $checkMember->user_id,
                    'email' => $checkMember->email,
                    'activity' => $request->activity,
                    'date' => $date,
                    'time' => $time,
                    'prodi' => $checkMember->department
                ];
            } else {
                if(!$request->name) {
                    return $this->res->errorResponse('Masukkan Nama terlebih dahulu', [], 422);
                }
                if(!$request->email) {
                    return $this->res->errorResponse('Masukkan Email terlebih dahulu', [], 422);
                }

                $visitorCheck = Visitor::where('email', $request->email)->where('date', $date)->first();

                if($visitorCheck) {
                    return $this->res->errorResponse('Anda sudah berkunjung hari ini', [], 400);
                }
                $data = [
                    'email' => $request->email,
                    'name' => $request->name,
                    'activity' => $request->activity,
                    'date' => $date,
                    'time' => $time,
                ];
            }

            $create = Visitor::firstOrCreate($data);
            DB::commit();

            return $this->res->successResponse('Data pengunjung berhasil ditambahkan', $create->toArray(), 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function banner ()
    {
        $banner = Banner::all()->toArray();
        return $this->res->successResponse('Data pengunjung berhasil ditambahkan', $banner, 201);
    }

    public function news (Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $title = $request->input('title');

        $query = News::orderByDesc('id');

        if (!empty($title)) {
            $query->where('title', 'like', "%{$title}%");
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);
        $items = $users->map(function ($user) {
            return [
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

    public function newsDetail (string $slug)
    {
        $news = News::where('slug', $slug)->first();
        if(!$news) {
            return $this->res->errorResponse('Berita tidak ditemukan', [], 404);
        }

        return $this->res->successResponse('Data berita berhasil ditambahkan', $news, 201);
    }
}
