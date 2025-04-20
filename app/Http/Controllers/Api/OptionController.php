<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Resources\Responses\ApiResponse;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\MasterBuku;

class OptionController extends Controller
{
    protected $res;
    public function __construct( ApiResponse $res )
    {
        $this->res = $res;
    }

    public function faculty()
    {
        $data = Faculty::orderByDesc('id')->select('id', 'name')->get()->toArray();

        return $this->res->successResponse('Data fakultas berhasil didapatkan', $data, 200);
    }

    public function prodi(Request $request)
    {
        $data = [];
        if($request->facultyId) {
            $data = StudyProgram::where('fakultas_id', $request->facultyId)->orderByDesc('id')->select('id', 'name')->get()->toArray();
        }

        return $this->res->successResponse('Data program studi berhasil didapatkan', $data, 200);
    }

    public function anggota(Request $request) {
        $data = User::whereIn('role', ['Teacher', 'Student'])->orderByDesc('id')->select('id', 'name', 'identityNumber')->get()->toArray();

        return $this->res->successResponse('Data anggota berhasil didapatkan', $data, 200);
    }

    public function buku(Request $request) {
        $buku = MasterBuku::orderByDesc('id')->with(['buku', 'karya'])->get();

        $data = [];
        foreach ($buku as $key => $value) {

            $judul = "";
            $isbn = "";
            $penulis = "";
            $type = "";
            if(isset($value->buku)) {
                $judul = $value->buku->judul;
                $penulis = $value->buku->penulis;
                $isbn = $value->buku->isbn;
                $type = 'Buku';
            } elseif(isset($value->karya)) {
                $judul = $value->karya->judul;
                $isbn = null;
                $penulis = $value->karya->penulis;
                $type = $value->karya->jenis;
            }

            $data[] = [
                'id' => $value->id,
                'isbn' => $isbn,
                'type' => $type,
                'book_id' => $value->book_id,
                'judul' => $judul,
                'penulis' => $penulis
            ];
        }

        return $this->res->successResponse('Data buku berhasil didapatkan', $data, 200);
    }
}
