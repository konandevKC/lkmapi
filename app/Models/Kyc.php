<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'piece_type',
        'piece_number',
        'piece_recto',
        'piece_verso',
        'selfie',
        'status',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 