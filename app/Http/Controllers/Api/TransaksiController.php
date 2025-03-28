<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\MasterBuku;
use App\Models\Denda;

use App\Resources\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PeminjamanExport;
use Carbon\Carbon;

class TransaksiController extends Controller
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
        $title = $request->input('title');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');


        $query = Transaction::with('denda')->orderByDesc('id');

        if (!empty($name)) {
            $query->where('nama_anggota', 'like', "%{$name}%");
        }

        if (!empty($title)) {
            $query->where('judul_buku', 'like', "%{$title}%");
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('tanggal_peminjaman', [$startDate, $endDate]);
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'transaction_code' => $user->transaction_code,
                'userId' => $user->userId,
                'id_anggota' => $user->id_anggota,
                'nama_anggota' => $user->nama_anggota,
                'bukuId' => $user->bukuId,
                'id_buku' => $user->id_buku,
                'judul_buku' => $user->judul_buku,
                'penulis' => $user->penulis,
                'tanggal_peminjaman' => $user->tanggal_peminjaman,
                'jatuh_tempo' => $user->jatuh_tempo,
                'keterangan_peminjaman' => $user->keterangan_peminjaman,
                'tanggal_pengembalian' => $user->tanggal_pengembalian,
                'status_pengembalian' => $user->status_pengembalian,
                'keterangan_pengembalian' => $user->keterangan_pengembalian,
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

        return $this->res->successResponse('Data transaksi berhasil didapatkan', $data, 200);
    }

    public function denda_index(Request $request)
    {
        $limit = $request->input('limit', 10); // Default 10 data per halaman
        $page = $request->input('page', 1); // Default halaman pertama
        $name = $request->input('name');
        $kode = $request->input('kode');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');


        $query = Denda::with('transaksi')->orderByDesc('id');

        if (!empty($name)) {
            $query->whereHas('transaksi', function ($q) use ($name) {
                $q->where('nama_anggota', 'like', "%{$name}%");
            });
        }

        if (!empty($kode)) {
            $query->whereHas('transaksi', function ($q) use ($kode) {
                $q->where('transaction_code', 'like', "%{$kode}%");
            });
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_peminjaman', [$startDate, $endDate])
                ->orWhereBetween('tanggal_pengembalian', [$startDate, $endDate]);
            });
        }

        $users = $query->paginate($limit, ['*'], 'page', $page);

        $items = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'transaction_code' => $user->transaksi->transaction_code,
                'userId' => $user->transaksi->userId,
                'id_anggota' => $user->transaksi->id_anggota,
                'nama_anggota' => $user->transaksi->nama_anggota,
                'bukuId' => $user->transaksi->bukuId,
                'id_buku' => $user->transaksi->id_buku,
                'judul_buku' => $user->transaksi->judul_buku,
                'penulis' => $user->transaksi->penulis,
                'tanggal_peminjaman' => $user->transaksi->tanggal_peminjaman,
                'jatuh_tempo' => $user->transaksi->jatuh_tempo,
                'keterangan_peminjaman' => $user->transaksi->keterangan_peminjaman,
                'tanggal_pengembalian' => $user->transaksi->tanggal_pengembalian,
                'status_pengembalian' => $user->transaksi->status_pengembalian,
                'keterangan_pengembalian' => $user->transaksi->keterangan_pengembalian,
                'total_keterlambatan' => $user->total_keterlambatan,
                'denda_keterlambatan' => $user->denda_keterlambatan,
                'status_pembayaran' => $user->status_pembayaran,
                'tanggal_bayar' => $user->tanggal_bayar,
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

        return $this->res->successResponse('Data transaksi berhasil didapatkan', $data, 200);
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

            $checkUser = User::find($request->userId);
            if (!$checkUser) {
                return $this->res->errorResponse("User tidak ditemukan", [], 404);
            }
            $checkBuku = MasterBuku::with(['buku', 'karya'])->find($request->bukuId);
            if (!$checkBuku) {
                return $this->res->errorResponse("Buku tidak ditemukan", [], 404);
            }
            $data = [
                'userId' => $checkUser->id,
                'id_anggota' => $checkUser->identityNumber,
                'nama_anggota' => $checkUser->name,
                'bukuId' => $checkBuku->id,
                'id_buku' => $checkBuku->book_id,
                'judul_buku' => isset($checkBuku->buku) ? $checkBuku->buku->judul : (isset($checkBuku->karya) ? $checkBuku->karya->judul : ""),
                'penulis' => isset($checkBuku->buku) ? $checkBuku->buku->penulis : (isset($checkBuku->karya) ? $checkBuku->karya->penulis : ""),
                'tanggal_peminjaman' => $request->tanggal_peminjaman,
                'jatuh_tempo' => $request->jatuh_tempo,
                'keterangan_peminjaman' => $request->keterangan_peminjaman,
            ];

            Transaction::create($data);

            DB::commit();

            return $this->res->successResponse('Data peminjaman berhasil ditambahkan', $data, 201);

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

            $data = Transaction::where('transaction_code', $id)->first();
            if(!$data) {
                return $this->res->errorResponse("Peminjaman dengan id {$id} tidak ditemukan", [], 404);
            }

            DB::commit();

            return $this->res->successResponse('Data peminjaman berhasil didapatkan', $data->toArray(), 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function denda_show(string $id)
    {

        try {
            DB::beginTransaction();

            $data = Transaction::where('transaction_code', $id)->with('denda')->first();
            if(!$data || !$data->denda) {
                return $this->res->errorResponse("Peminjaman dengan id {$id} tidak ditemukan", [], 404);
            }

            $merged = array_merge($data->toArray(), ['total_keterlambatan' => $data->denda->total_keterlambatan, 'denda_keterlambatan' => $data->denda->denda_keterlambatan, 'status_pembayaran' => $data->denda->status_pembayaran, 'tanggal_bayar' => $data->denda->tanggal_bayar]);

            DB::commit();

            return $this->res->successResponse('Data peminjaman berhasil didapatkan', $merged, 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
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
        $transaction = Transaction::where('transaction_code', $id)->first();
        if(!$transaction) {
            return $this->res->errorResponse("Peminjaman dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('update', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $checkDenda = Denda::where('transactionId', $transaction->id)->first();
            if($checkDenda) {
                Denda::where('transactionId', $transaction->id)->delete();
            }

            $tanggal_pengembalian = $request->tanggal_pengembalian;
            $jatuh_tempo = $transaction->jatuh_tempo;

            $tgl_pengembalian = Carbon::parse($tanggal_pengembalian);
            $tgl_jatuh_tempo = Carbon::parse($jatuh_tempo);

            if ($tgl_pengembalian->gt($tgl_jatuh_tempo)) {
                $status_pengembalian = 'Terlambat';
                $jumlah_hari_terlambat = $tgl_pengembalian->diffInDays($tgl_jatuh_tempo);
            } else {
                $status_pengembalian = 'Tepat Waktu';
                $jumlah_hari_terlambat = 0;
            }

            $data = [
                'tanggal_pengembalian' => $request->tanggal_pengembalian,
                'status_pengembalian' => $status_pengembalian,
                'keterangan_pengembalian' => $request->keterangan_pengembalian,
            ];

            $transaction->update($data);

            if ($tgl_pengembalian->gt($tgl_jatuh_tempo)) {
                $status_pengembalian = 'Terlambat';
                $jumlah_hari_terlambat = $tgl_pengembalian->diffInDays($tgl_jatuh_tempo);

                $buku = MasterBuku::find($transaction->bukuId)->with(['buku', 'karya']);
                $dendaNominal = 0;
                if(!$buku) {
                    $dendaNominal = 1000 * $jumlah_hari_terlambat;
                } else {
                    if(isset($buku->buku)) {
                        $dendaNominal = $buku->buku->denda_harian * $jumlah_hari_terlambat;
                    } elseif(isset($buku->karya)) {
                        $dendaNominal = $buku->karya->denda_harian * $jumlah_hari_terlambat;
                    } else {
                        $dendaNominal = 1000 * $jumlah_hari_terlambat;
                    }
                }

                $denda = [
                    'transactionId' => $transaction->id,
                    'total_keterlambatan' => $jumlah_hari_terlambat,
                    'denda_keterlambatan' => $dendaNominal,
                ];

                Denda::createOrFirst(
                    ['transactionId'],
                    $denda
                );
            }


            DB::commit();

            return $this->res->successResponse('Data peminjaman berhasil diperbarui', $data, 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function denda_update(Request $request, string $id)
    {
        $transaction = Transaction::where('transaction_code', $id)->with('denda')->first();
        if(!$transaction || !$transaction->denda) {
            return $this->res->errorResponse("Peminjaman dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $validator = $this->__rules('denda', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $checkDenda = Denda::where('transactionId', $transaction->id)->first();

            $data = [
                'status_pembayaran' => $request->status_pembayaran,
                'tanggal_bayar' => $request->tanggal_bayar
            ];

            $checkDenda->update($data);

            DB::commit();

            return $this->res->successResponse('Data denda berhasil diperbarui', $data, 201);

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
        $user = Transaction::where('transaction_code', $id)->first();
        if(!$user) {
            return $this->res->errorResponse("Peminjaman dengan id {$id} tidak ditemukan", [], 404);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return $this->res->successResponse('Data peminjaman berhasil dihapus', [], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function peminjaman_export()
    {
        $users = Transaction::get();
        $items = $users->map(function ($user, $index) {
            return [
                "No" => $index + 1,
                "Kode Transaksi" => $user->transaction_code,
                "Nama Peminjam" => $user->nama_anggota,
                "ID Peminjam" => $user->id_anggota,
                "Judul Buku" => $user->judul_buku,
                "Tanggal Peminjaman" => $user->tanggal_peminjaman,
                "Jatuh Tempo" => $user->jatuh_tempo,
                "Tanggal Pengembalian" => $user->tanggal_pengembalian
            ];
        });
        return Excel::download(new PeminjamanExport($items),
        'data_export.xlsx');
    }

    public function __rules(string $type, Request $request) {
        $message = [
            "bukuId.string" =>"Buku tidak valid.",
            "bukuId.max" =>"Buku maksimal harus memiliki 255 karakter.",
            "bukuId.required" =>"Buku wajib diisi.",

            "userId.string" =>"Peminjam tidak valid.",
            "userId.max" =>"Peminjam harus memiliki 255 karakter.",
            "userId.required" =>"Peminjam wajib diisi.",

            "tanggal_peminjaman.string" =>"Tanggal peminjaman tidak valid.",
            "tanggal_peminjaman.max" =>"Tanggal peminjaman harus memiliki 255 karakter.",
            "tanggal_peminjaman.required" =>"Tanggal peminjaman wajib diisi.",

            "jatuh_tempo.string" =>"Tanggal jatuh tempo tidak valid.",
            "jatuh_tempo.max" =>"Tanggal jatuh tempo harus memiliki 255 karakter.",
            "jatuh_tempo.required" =>"Tanggal jatuh tempo wajib diisi.",

            "keterangan_peminjaman.string" =>"Keterangan peminjaman tidak valid.",
            "keterangan_peminjaman.max" =>"Keterangan peminjaman harus memiliki 255 karakter.",
            "keterangan_peminjaman.required" =>"Keterangan peminjaman wajib diisi.",


            "tanggal_pengembalian.string" =>"Tanggal pengembalian tidak valid.",
            "tanggal_pengembalian.max" =>"Tanggal pengembalian harus memiliki 255 karakter.",
            "tanggal_pengembalian.required" =>"Tanggal pengembalian wajib diisi.",

            "keterangan_pengembalian.string" =>"Keterangan peminjaman tidak valid.",
            "keterangan_pengembalian.max" =>"Keterangan peminjaman harus memiliki 255 karakter.",
            "keterangan_pengembalian.required" =>"Keterangan peminjaman wajib diisi.",

            "tanggal_bayar.string" =>"Tanggal pembayaran tidak valid.",
            "tanggal_bayar.max" =>"Tanggal pembayaran harus memiliki 255 karakter.",
            "tanggal_bayar.required" =>"Tanggal pembayaran wajib diisi.",

            "status_pembayaran.string" =>"Status pembayaran tidak valid.",
            "status_pembayaran.max" =>"Status pembayaran harus memiliki 255 karakter.",
            "status_pembayaran.required" =>"Status pembayaran wajib diisi.",

        ];
        if($type == 'create') {
            return Validator::make($request->all(), [
                'bukuId' => ['required', 'string', 'max:255'],
                'userId' => ['required', 'string', 'max:255'],
                'tanggal_peminjaman' => ['required', 'string', 'max:255'],
                'jatuh_tempo' => ['required', 'string', 'max:255'],
                'keterangan_peminjaman' => ['nullable', 'string', 'max:255'],
            ], $message);
        } elseif ($type == 'update') {
            $rules = [
                'tanggal_pengembalian' => ['required', 'string', 'max:255'],
                'keterangan_pengembalian' => ['nullable', 'string', 'max:255'],
            ];
            return Validator::make($request->all(), $rules, $message);
        } elseif ($type == 'denda') {
            $rules = [
                'tanggal_bayar' => ['required', 'string', 'max:255'],
                'status_pembayaran' => ['required', 'string', 'max:255'],
            ];
            return Validator::make($request->all(), $rules, $message);
        }
    }
}
