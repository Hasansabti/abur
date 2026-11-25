<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Companion extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'national_id',
        'birth_date',
        'dob_gregorian',    // التاريخ الميلادي
        'gender',
        'id_document_path', // مسار صورة الهوية/المستند
        'relationship',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'dob_gregorian' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
