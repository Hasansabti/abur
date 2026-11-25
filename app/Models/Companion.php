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
        'id_document_path',
        'relationship',
        'nationality_id',
        'identity_version',
        'identity_issue_place',
        'identity_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'dob_gregorian' => 'date',
            'identity_expiry_date' => 'date',
        ];
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
