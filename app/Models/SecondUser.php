<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SecondUser extends Model
{
    use HasFactory;
    protected $table = 'second_users';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'email',
        'nim',
        'status',
        'gender',
        'phone_number',
        'waktu_terdaftar',
        'profile_picture',
        'faculty',
        'department',
        'otoritas',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid(); // 👈 UUID string
            }
        });
    }
}
