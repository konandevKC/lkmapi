<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Paiement;
use App\Models\Locataire;
use App\Models\Proprietaire;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'locataire_id' => 'required|exists:locataires,id',
            'montant' => 'required|numeric|min:0.01',
            'mois' => 'required|string',
            'annee' => 'required|string',
            'mode_paiement' => 'required|string',
            'valide' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $locataire = Locataire::findOrFail($validated['locataire_id']);
            $proprietaire = $locataire->proprietaire;

            $paiement = Paiement::create([
                'locataire_id' => $locataire->id,
                'proprietaire_id' => $proprietaire->id,
                'montant' => $validated['montant'],
                'mois' => $validated['mois'],
                'annee' => $validated['annee'],
                'mode_paiement' => $validated['mode_paiement'],
                'valide' => $validated['valide'] ?? false,
            ]);

            // Incrémenter le wallet du propriétaire si le paiement est validé
            if ($paiement->valide) {
                $proprietaire->wallet += $paiement->montant;
                $proprietaire->save();
            }

            return response()->json($paiement, 200);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
