<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Resources\Responses\ApiResponse;
use App\Models\Visitor;
use App\Models\User;

class VisitorController extends Controller
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
        $name = $request->input('name');
        $startDate = $request->input('date');
        $endDate = $request->input('end');
        $identityNumber = $request->input('identityNumber');

        $query = Visitor::with('user')->orderByDesc('id');

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        if (!empty($startDate) && !empty($endDate)) {
            if ($startDate == $endDate || $startDate > $endDate) {
                $query->whereDate('date', $startDate);
            } else {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
        } elseif (!empty($startDate)) {
            $query->whereDate('date', $startDate);
        } elseif (!empty($endDate)) {
            $query->whereDate('date', $endDate);
        }

        if (!empty($identityNumber)) {
            $query->whereHas('user', function ($q) use ($identityNumber) {
                $q->where('identityNumber', 'like', "%{$identityNumber}%");
            });
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                "id" => $user->id,
                "member" => $user->userId ? $user->user->identityNumber : $user->email,
                "name" => $user->name,
                "activity" => $user->activity,
                "time" => $user->date
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

        return $this->res->successResponse('Data admin berhasil didapatkan', $data, 200);
    }
}
