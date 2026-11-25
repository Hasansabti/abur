<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    // الحقول المسموح بحفظها تلقائياً
    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'message',
        'status',
        'reply'
    ];

    // علاقة التذكرة بالمستخدم (كل تذكرة تنتمي لمستخدم واحد)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}