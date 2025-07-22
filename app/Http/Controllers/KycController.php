<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kyc;
use App\Models\User;

class KycController extends Controller
{
    public function submit(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'piece_type' => 'required|string',
            'piece_number' => 'required|string',
            'piece_recto' => 'required|file|image|max:4096',
            'piece_verso' => 'required|file|image|max:4096',
            'selfie' => 'required|file|image|max:4096',
        ]);
        // Vérifier que c'est un propriétaire
        if ($user->role !== 'proprietaire') {
            return response()->json(['error' => 'Seuls les propriétaires peuvent soumettre un KYC'], 403);
        }
        // Upload des fichiers
        $rectoPath = $request->file('piece_recto')->store('kyc', 'public');
        $versoPath = $request->file('piece_verso')->store('kyc', 'public');
        $selfiePath = $request->file('selfie')->store('kyc', 'public');
        // Générer un code bailleur unique
        $codeBailleur = $this->generateBailleurCode();
        // Ajouter dans la table proprietaires si pas déjà présent
        $proprio = \App\Models\Proprietaire::firstOrCreate(
            ['email' => $user->email],
            [
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'telephone' => $user->phone,
                'email' => $user->email,
                'code_proprio' => $codeBailleur,
                'password' => $user->password, // déjà hashé
            ]
        );
        // On peut choisir de n'autoriser qu'un seul KYC actif par user
        $kyc = Kyc::updateOrCreate(
            ['user_id' => $user->id],
            [
                'piece_type' => $request->piece_type,
                'piece_number' => $request->piece_number,
                'piece_recto' => $rectoPath,
                'piece_verso' => $versoPath,
                'selfie' => $selfiePath,
                'status' => 'pending',
                'comment' => null,
            ]
        );
        return response()->json([
            'message' => 'KYC soumis, en attente de validation',
            'kyc' => $kyc,
            'code_bailleur' => $proprio->code_proprio,
        ]);
    }

    private function generateBailleurCode()
    {
        $prefix = 'BAIL';
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        while (\App\Models\Proprietaire::where('code_proprio', $prefix . $random)->exists()) {
            $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        }
        return $prefix . $random;
    }

    public function status(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        $kyc = $user->kyc;
        if (!$kyc) {
            return response()->json(['message' => 'Aucun KYC soumis'], 404);
        }
        return response()->json([
            'status' => $kyc->status,
            'comment' => $kyc->comment,
            'kyc' => $kyc,
        ]);
    }

    // Pour l'admin :
    public function validateKyc(Request $request, $userId)
    {
        $request->validate([
            'status' => 'required|in:validated,refused',
            'comment' => 'nullable|string',
        ]);
        $kyc = Kyc::where('user_id', $userId)->firstOrFail();
        $kyc->status = $request->status;
        $kyc->comment = $request->comment;
        $kyc->save();
        return response()->json(['message' => 'KYC mis à jour', 'kyc' => $kyc]);
    }
} 