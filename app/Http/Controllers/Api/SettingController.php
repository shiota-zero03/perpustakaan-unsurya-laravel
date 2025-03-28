<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

use App\Resources\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SettingController extends Controller
{
    protected $res;
    public function __construct( ApiResponse $res )
    {
        $this->res = $res;
    }
    public function index(){
        $set = Setting::first();
        $data = [
            'profil' => $set->profil,
            'petunjuk' => $set->petunjuk,
            'prosedur' => $set->prosedur,
        ];
        return $this->res->successResponse('data setting berhasil didapatkan', $data, 201);
    }

    public function profil(Request $request)
    {

        try {
            DB::beginTransaction();

            if($request->profil) {
                $data['profil'] = $request->profil;
            }
            if($request->petunjuk) {
                $data['petunjuk'] = $request->petunjuk;
            }
            if($request->prosedur) {
                $data['prosedur'] = $request->prosedur;
            }

            Setting::first()->update($data);

            DB::commit();

            return $this->res->successResponse('Data profil berhasil diperbarui', $data, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }
}
