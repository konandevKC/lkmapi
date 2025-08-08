<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocataireController extends Controller
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
     * Lier un locataire à un propriétaire via le code_proprio
     */
    public function linkToProprietaire(Request $request)
    {
        $request->validate([
            'code_proprio' => 'required|exists:proprietaires,code_proprio',
        ]);
        $user = $request->user();
        if (!$user || $user->role !== 'locataire') {
            return response()->json(['error' => 'Seul un locataire authentifié peut effectuer cette opération'], 403);
        }
        $locataire = \App\Models\Locataire::where('email', $user->email)->first();
        if (!$locataire) {
            // Création du locataire à partir du user connecté
            $locataire = \App\Models\Locataire::create([
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->phone,
                // 'code_proprio' et 'proprietaire_id' seront remplis juste après
            ]);
        }
        $proprio = \App\Models\Proprietaire::where('code_proprio', $request->code_proprio)->first();
        $locataire->proprietaire_id = $proprio->id;
        $locataire->code_proprio = $proprio->code_proprio;
        $locataire->save();
        return response()->json([
            'message' => 'Locataire lié au propriétaire avec succès',
            'locataire' => $locataire,
            'proprietaire' => [
                'id' => $proprio->id,
                'nom' => $proprio->nom,
                'prenom' => $proprio->prenom,
                'code_proprio' => $proprio->code_proprio,
            ],
        ]);
    }

    /**
     * Récupère le propriétaire lié au locataire connecté
     */
    public function getLinkedProprietaire(Request $request)
{
    $user = $request->user();

    if (!$user || $user->role !== 'locataire') {
        return response()->json(['error' => 'Seul un locataire authentifié peut effectuer cette opération'], 403);
    }

    // Récupérer le locataire à partir de l'email de l'utilisateur connecté
    $locataire = \App\Models\Locataire::where('email', $user->email)->first();
    dd($locataire);

    if (!$locataire || !$locataire->proprietaire_id) {
        return response()->json(['message' => 'Aucun propriétaire lié à ce locataire'], 404);
    }

    // Récupérer les infos du propriétaire lié
    $proprio = \App\Models\Proprietaire::find($locataire->proprietaire_id);

    if (!$proprio) {
        return response()->json(['error' => 'Propriétaire introuvable'], 404);
    }

    return response()->json([
        'message' => 'Propriétaire associé trouvé avec succès',
        'locataire' => $locataire,
        'proprietaire' => [
            'id' => $proprio->id,
            'nom' => $proprio->nom,
            'prenom' => $proprio->prenom,
            'code_proprio' => $proprio->code_proprio,
           'phone' => $proprio->telephone,
        ],
    ]);
}


    /**
     * Associe un locataire (créé si besoin) à un propriétaire
     */
    public static function associateLocataireToProprio($user, $proprio)
    {
        $locataire = \App\Models\Locataire::where('email', $user->email)->first();
        if (!$locataire) {
            $locataire = \App\Models\Locataire::create([
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->phone,
            ]);
        }
        if ($locataire->proprietaire_id !== $proprio->id) {
            $locataire->proprietaire_id = $proprio->id;
            $locataire->code_proprio = $proprio->code_proprio;
            $locataire->save();
        }
        return $locataire;
    }

    /**
     * Retourne les infos du locataire connecté
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'locataire') {
            return response()->json(['error' => 'Seul un locataire authentifié peut accéder à cette ressource'], 403);
        }
        $locataire = \App\Models\Locataire::where('email', $user->email)->first();
        if (!$locataire) {
            return response()->json(['error' => 'Aucun locataire trouvé pour cet utilisateur'], 404);
        }
        return response()->json($locataire);
    }
    /** Delete locataire connecté  */
    public function destroy(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'locataire') {
            return response()->json(['error' => 'Seul un locataire authentifié peut supprimer son compte'], 403);
        }
        // Supprimer le locataire
        $locataire = \App\Models\Locataire::where('email', $user->email)->first();
        if ($locataire) {
            $locataire->delete();
        }
        // Supprimer l'utilisateur
        $user->delete();
        return response()->json(['message' => 'Compte de locataire supprimé avec succès']);
    }
}
