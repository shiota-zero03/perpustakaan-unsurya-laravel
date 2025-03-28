<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use App\Exports\Buku\BukuFisikExport;
use App\Exports\Buku\BukuFisikSampleExport;
use App\Imports\BukuFisikImport;
use App\Resources\Responses\ApiResponse;
use App\Services\Base64FileService;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Denda;
use App\Models\MasterBuku;
use App\Models\Visitor;
use Carbon\Carbon;

class DashboardController extends Controller
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
        $start = Carbon::now()->startOfDay()->toDateString();
        $end = Carbon::now()->endOfDay()->toDateString();

        $anggota = User::whereIn('role', ['Teacher', 'Student'])->count();
        $buku = MasterBuku::count();

        $transaksi = Visitor::whereBetween('date', [$start, $end])->count();

        $startBulanIni = Carbon::now()->startOfMonth();
        $endBulanIni = Carbon::now()->endOfMonth();

        $startBulanKemarin = Carbon::now()->subMonth()->startOfMonth();
        $endBulanKemarin = Carbon::now()->subMonth()->endOfMonth();

        $transaksiHariIni = Transaction::whereBetween('tanggal_peminjaman', [$startBulanIni, $endBulanIni])->count();
        $transaksiKemarin = Transaction::whereBetween('tanggal_peminjaman', [$startBulanKemarin, $endBulanKemarin])->count();
        $selisihTransaksi = $transaksiHariIni - $transaksiKemarin;
        $persentaseTransaksi = $transaksiKemarin > 0 ? ($selisihTransaksi / max($transaksiKemarin, 1)) * 100 : ($transaksiHariIni > 0 ? 100 : 0);
        $statusTransaksi = $selisihTransaksi > 0 ? 'up' : ($selisihTransaksi < 0 ? 'down' : 'up');

        $pengembalianHariIni = Transaction::whereBetween('tanggal_pengembalian', [$startBulanIni, $endBulanIni])
            ->where('status_pengembalian', 'Terlambat')
            ->count();

        $pengembalianKemarin = Transaction::whereBetween('tanggal_pengembalian', [$startBulanKemarin, $endBulanKemarin])
            ->where('status_pengembalian', 'Terlambat')
            ->count();
        $selisihPengembalian = $pengembalianHariIni - $pengembalianKemarin;
        $persentasePengembalian = $pengembalianKemarin > 0 ? ($selisihPengembalian / max($pengembalianKemarin, 1)) * 100 : ($pengembalianHariIni > 0 ? 100 : 0);
        $statusPengembalian = $selisihPengembalian > 0 ? 'up' : ($selisihPengembalian < 0 ? 'down' : 'up');


        $bayarHariIni = Denda::whereBetween('tanggal_bayar', [$startBulanIni, $endBulanIni])
            ->sum('denda_keterlambatan');
        $bayarKemarin = Denda::whereBetween('tanggal_bayar', [$startBulanKemarin, $endBulanKemarin])
            ->sum('denda_keterlambatan');
        $selisihbayar = $bayarHariIni - $bayarKemarin;
        $persentasebayar = $bayarKemarin > 0 ? ($selisihbayar / max($bayarKemarin, 1)) * 100 : ($bayarHariIni > 0 ? 100 : 0);
        $statusbayar = $selisihbayar > 0 ? 'up' : ($selisihbayar < 0 ? 'down' : 'up');

        $data = [
            'anggota' => $anggota,
            'buku' => $buku,
            'transaksi' => $transaksi,

            'transaksiHariIni' => $transaksiHariIni,
            'transaksiKemarin' => $transaksiKemarin,
            'selisihTransaksi' => $persentaseTransaksi,
            'statusTransaksi' => $statusTransaksi,

            'pengembalianHariIni' => $pengembalianHariIni,
            'pengembalianKemarin' => $pengembalianKemarin,
            'selisihPengembalian' => $persentasePengembalian,
            'statusPengembalian' => $statusPengembalian,

            'bayarHariIni' => $bayarHariIni,
            'bayarKemarin' => $bayarKemarin,
            'selisihBayar' => $persentasebayar,
            'statusbayar' => $statusbayar,
        ];
        return $this->res->successResponse('Data dashboard berhasil didapatkan', $data, 200);
    }

    public function getTransaksiPerTahun(Request $request)
    {
        $bulanSingkat = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $tahun = $request->tahun ?? date('Y');
        $data = Transaction::selectRaw('MONTH(tanggal_peminjaman) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_peminjaman', $tahun)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month' => $bulanSingkat[$i],
                'total' => isset($data[$i]) ? $data[$i]->total : 0
            ];
        }
        return $this->res->successResponse('Data dashboard berhasil didapatkan', $result, 200);
    }

    public function getKunjunganPerTahun(Request $request)
    {
        $bulanSingkat = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $tahun = $request->tahun ?? date('Y');
        $data = Visitor::selectRaw('MONTH(date) as bulan, COUNT(*) as total')
            ->whereYear('date', $tahun)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month' => $bulanSingkat[$i],
                'total' => isset($data[$i]) ? $data[$i]->total : 0
            ];
        }
        return $this->res->successResponse('Data dashboard berhasil didapatkan', $result, 200);
    }
}
