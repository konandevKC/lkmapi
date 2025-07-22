<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payementogo extends Model
{
    protected $fillable = [
        'ref',
        'telephone',
        'name',
        'pname',
        'montant',
        'currency',
        'numcommande',
        'otp',
        'pays',
        'operateurs',
        'status',
    ];
} 