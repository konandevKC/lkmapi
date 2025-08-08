<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'code',
        'expires_at',
     ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unused OTPs.
     */
    public function scopeUnused($query)
    {
        return $query->where('used', false);
    }

    /**
     * Check if the OTP is valid.
     */
    public function isValid()
    {
        return !$this->used && $this->expires_at > now();
    }
}
