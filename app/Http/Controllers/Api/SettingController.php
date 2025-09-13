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
        if($set) {
            $data = [
                'profil' => $set->profil,
                'petunjuk' => $set->petunjuk,
                'prosedur' => $set->prosedur,
            ];
        } else {
            $data = [
                'profil' => "",
                'petunjuk' => "",
                'prosedur' => "",
            ];
        }
        return $this->res->successResponse('data setting berhasil didapatkan', $data, 201);
    }

    public function profil(Request $request)
    {

        try {
            DB::beginTransaction();

            $setting = Setting::first();

            if($request->profil) {
                $data['profil'] = $request->profil;
                $data['petunjuk'] = $setting ? $setting->petunjuk : "";
                $data['prosedur'] = $setting ? $setting->prosedur : "";
            }
            if($request->petunjuk) {
                $data['petunjuk'] = $request->petunjuk;
                $data['profil'] = $setting ? $setting->petunjuk : "";
                $data['prosedur'] = $setting ? $setting->prosedur : "";
            }
            if($request->prosedur) {
                $data['prosedur'] = $request->prosedur;
                $data['petunjuk'] = $setting ? $setting->petunjuk : "";
                $data['profil'] = $setting ? $setting->prosedur : "";
            }


            if ($setting) {
                $setting->update($data);
            } else {
                $setting = Setting::create($data);
            }

            DB::commit();

            return $this->res->successResponse('Data profil berhasil diperbarui', $data, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }
}
