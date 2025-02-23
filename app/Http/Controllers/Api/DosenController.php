<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Resources\Responses\ApiResponse;

use App\Models\User;
use App\Models\Teacher;

class DosenController extends Controller
{
    protected $user, $teacher, $res;
    public function __construct( User $user, Teacher $teacher, ApiResponse $res )
    {
        $this->user = $user;
        $this->teacher = $teacher;
        $this->res = $res;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            DB::beginTransaction();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
