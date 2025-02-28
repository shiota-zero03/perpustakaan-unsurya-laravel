<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Resources\Responses\ApiResponse;

use App\Models\Faculty;
use App\Models\StudyProgram;

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
}
