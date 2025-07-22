<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proprietaire;
use App\Http\Controllers\Api\LocataireController;

class ProprietaireController extends Controller
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
        //
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

    /**
     * Retourne les infos du propriétaire connecté (dont code_proprio)
     */
    public function getCurrent(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        $proprio = Proprietaire::where('email', $user->email)->first();
        if (!$proprio) {
            return response()->json(['error' => 'Aucun propriétaire trouvé pour cet utilisateur'], 404);
        }
        return response()->json([
            'id' => $proprio->id,
            'nom' => $proprio->nom,
            'prenom' => $proprio->prenom,
            'telephone' => $proprio->telephone,
            'email' => $proprio->email,
            'code_proprio' => $proprio->code_proprio,
        ]);
    }

    /**
     * Retourne le solde du wallet du propriétaire connecté
     */
    public function wallet(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        $proprio = Proprietaire::where('email', $user->email)->first();
        if (!$proprio) {
            return response()->json(['error' => 'Aucun propriétaire trouvé pour cet utilisateur'], 404);
        }
        return response()->json([
            'wallet' => $proprio->wallet,
        ]);
    }

    /**
     * Recherche un propriétaire par code_proprio
     */
    public function byCode(Request $request, $code)
    {
        $proprio = Proprietaire::where('code_proprio', $code)->first();
        if (!$proprio) {
            return response()->json(['error' => 'Aucun propriétaire trouvé pour ce code'], 404);
        }
        // Si un utilisateur locataire est authentifié, on le lie automatiquement
        $user = $request->user();
        if ($user && $user->role === 'locataire') {
            LocataireController::associateLocataireToProprio($user, $proprio);
        }
        return response()->json([
            'id' => $proprio->id,
            'nom' => $proprio->nom,
            'prenom' => $proprio->prenom,
            'telephone' => $proprio->telephone,
            'email' => $proprio->email,
            'code_proprio' => $proprio->code_proprio,
        ]);
    }
}
