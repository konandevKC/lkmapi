<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proprietaire extends Model
{
    /** @use HasFactory<\Database\Factories\ProprietaireFactory> */
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'code_proprio',
        'password',
        'wallet',
    ];

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
}
