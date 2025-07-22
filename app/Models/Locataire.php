<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locataire extends Model
{
    /** @use HasFactory<\Database\Factories\LocataireFactory> */
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'code_proprio',
        'proprietaire_id',   
    ];

    public function proprietaire()
    {
        return $this->belongsTo(Proprietaire::class);
    }
}
