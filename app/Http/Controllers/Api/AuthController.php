<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use App\Resources\Responses\ApiResponse;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Helpers\NotificationHelpers;

class AuthController extends Controller
{
    private $userModel, $res, $notif;

    public function __construct(User $userModel, ApiResponse $res, NotificationHelpers $notif)
    {
        $this->userModel = $userModel;
        $this->res = $res;
        $this->notif = $notif;
    }

    public function sign_in(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = $this->__rules('sign-in', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            if(!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->res->errorResponse('Email atau password anda salah', [], 400);
            }

            $user = Auth::user();

            if($user->status !== 'Active') {
                Auth::user()->tokens()->delete();
                return $this->res->errorResponse('Akun anda tidak aktif', [], 400);
            }
            $token = $user->createToken('auth_token')->plainTextToken;

            $data = [
                'token' => $token,
                'role' => $user->role
            ];
            DB::commit();

            return $this->res->successResponse('Berhasil Login', $data);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function sign_up(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = $this->__rules('sign-up', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $data = [
                'userId' => Str::uuid(),
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'role' => $request->accountType,
                'status' => "InActive",
                'identityNumber' => $request->identityNumber
            ];

            $create = $this->userModel::create($data);

            if($request->accountType == 'Teacher') {
                Teacher::create([
                    'userId' => $create->id
                ]);
            } elseif ($request->accountType == 'Student') {
                Student::create([
                    'userId' => $create->id
                ]);
            }
            $this->notif->CreateNotification("registration", "Akun baru atas nama $request->name telah mendaftar ke sistem", $create->id);

            DB::commit();

            return $this->res->successResponse('Data berhasil ditambahkan', $create->toArray());

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function forgot_password(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = $this->__rules('forgot', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $user = User::where('email', $request->email)->first();
            if(!$user) {
                return $this->res->errorResponse("Akun dengan email $request->email tidak ditemukan", [], 404);
            }

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            $token = Str::random(60);
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);
            $link = env('PERPUSTAKAAN_UNSURYA_FRONTEND_URL')."/auth/reset-password?e=$request->email&t=$token";

            $data = [
                'email' => $user->email,
                'link' => $link
            ];

            Mail::to($user->email)->send(new ResetPasswordMail($data));
            DB::commit();

            return $this->res->successResponse('Link untuk melakukan reset password berhasil dikirim, silahkan cek email anda', $data);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function reset_password(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = $this->__rules('reset', $request);

            if ($validator->fails()) {
                return $this->res->errorResponse('Terjadi kesalahan validasi, silahkan cek kembali form anda', $validator->errors()->toArray(), 422);
            }

            $user = User::where('email', $request->email)->first();
            if(!$user) {
                return $this->res->errorResponse("Akun dengan email $request->email tidak ditemukan", [], 404);
            }

            $resetToken = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->first();

            if (!$resetToken || !Hash::check($request->token, $resetToken->token)) {
                return $this->res->errorResponse("Token dan email anda tidak sesuai atau sudah kadaluarsa", [], 400);
            }

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            DB::commit();

            return $this->res->successResponse('Password anda berhasil diperbarui', []);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error saat mengirim data: " . $th->getMessage());
            return $this->res->errorResponse($th->getMessage(), [], 500);
        }
    }

    public function get_profile()
    {
        $user = Auth::user();
        $data = [];
        if($user->role == 'SuperAdmin') {
            $data = [
                'name' => $user->name,
                'role' => $user->role,
                'identityNumber' => $user->identityNumber,
                'email' => $user->email,
                'status' => $user->status,
            ];
        }
        return $this->res->successResponse('Berhasil mendapatkan data profil', $data);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return $this->res->successResponse('Berhasil logout', []);
    }

    private function __rules(string $type, Request $request)
    {
        $message = [
            'token.required' => 'Token tidak ditemukan',
            'name.required' => 'Nama wajib diisi',
            'name.max' => 'Nama maksimal harus memiliki 255 karakter',
            'identityNumber.required' => 'Nomor identitas wajib diisi',
            'identityNumber.min' => 'Nomor identitas minimum harus memiliki 3 karakter',
            'identityNumber.max' => 'Nomor identitas maksimal harus memiliki 255 karakter',
            'identityNumber.unique' => 'Nomor identitas sudah pernah digunakan',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Email tidak valid',
            'email.min' => 'Email minimum harus memiliki 3 karakter',
            'email.max' => 'Email maksimal harus memiliki 255 karakter',
            'email.unique' => 'Email sudah pernah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimum harus memiliki 6 karakter',
            'password.max' => 'Password maksimal harus memiliki 255 karakter',
            'accountType.required' => 'Tipe akun wajib diisi',
            'accountType.in' => 'Tipe akun harus diantara Teacher atau Student.',
            'new_password.required' => 'Password baru wajib diisi',
            'new_password.min' => 'Password baru minimal harus memiliki 6 karakter',
            'new_password.max' => 'Password baru maksimal harus memiliki 255 karakter',
            'confirmation_password.required' => 'Konfirmasi password wajib diisi',
            'confirmation_password.min' => 'Konfirmasi password minimal harus memiliki 6 karakter',
            'confirmation_password.max' => 'Konfirmasi password maksimal harus memiliki 255 karakter',
            'confirmation_password.same' => 'Konfirmasi password tidak sama dengan password baru anda',
        ];
        if($type == 'sign-up'){
            return Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'identityNumber' => ['required', 'string', 'min:3', 'max:255', 'unique:users,identityNumber'],
                'email' => ['required', 'email', 'min:3', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'accountType' => ['required', 'in:Teacher,Student']
            ], $message);
        } elseif($type == 'sign-in'){
            return Validator::make($request->all(), [
                'email' => ['required', 'email', 'min:3', 'max:255'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
            ], $message);
        } elseif($type == 'forgot'){
            return Validator::make($request->all(), [
                'email' => ['required', 'email', 'min:3', 'max:255'],
            ], $message);
        }  elseif($type == 'reset'){
            return Validator::make($request->all(), [
                'email' => ['required', 'email', 'min:3', 'max:255'],
                'token' => ['required', 'string', 'min:3', 'max:255'],
                'new_password' => ['required', 'string', 'min:6', 'max:255'],
                'confirmation_password' => ['required', 'string', 'min:6', 'max:255', 'same:new_password'],
            ], $message);
        }
    }
}
