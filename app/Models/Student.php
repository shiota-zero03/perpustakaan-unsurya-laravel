<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $fillable = [
        'userId',
        'profilePicture',
        'gender',
        'phoneNumber',
        'facultyId',
        'studyProgramId',
        'validUntil',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function fakultas()
    {
        return $this->belongsTo(Faculty::class, 'facultyId');
    }

    public function prodi()
    {
        return $this->belongsTo(StudyProgram::class, 'studyProgramId');
    }
}
