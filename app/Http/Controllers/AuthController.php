<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Proprietaire;
use App\Models\Locataire;
use App\Models\Kyc;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required',
            'prenom' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
            'role' => 'required|in:locataire,proprietaire',
        ]);
        // Vérifier OTP ici (voir OtpController)
       // $code = rand(100000, 999999);
        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);
        //$this->sendCodeOtpService($user, $code);
        return response()->json(['message' => 'Inscription réussie', 'user' => $user]);
    }
  

  
    
    public function login(Request $request)
    {
        $request->validate([
            'identifiant' => 'required|string', // Peut être email ou téléphone
            'password' => 'required|string',
        ]);

        // Chercher l'utilisateur par email ou téléphone
        $user = User::where('email', $request->identifiant)
            ->orWhere('phone', $request->identifiant)
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // 2. Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Mot de passe incorrect'], 401);
        }

        // (Optionnel) Vérifier si le compte est actif
        // if (!$user->active) {
        //     return response()->json(['error' => 'Compte désactivé'], 403);
        // }

        // 3. Créer le token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Ajout des infos locataire si besoin
        $userArr = $user->toArray();
        if ($user->role === 'locataire') {
            $locataire = \App\Models\Locataire::where('email', $user->email)->first();
            if ($locataire) {
                $userArr['code_proprio'] = $locataire->code_proprio;
                $userArr['proprietaire_id'] = $locataire->proprietaire_id;
            }
        }

        return response()->json([
            'token' => $token,
            'user' =>  $userArr,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function updateMontantLoyer(Request $request)
    {
        try {
            $request->validate([
                'montant_loyer' => 'required|integer|min:1000',
            ], [
                'montant_loyer.required' => 'Le montant du loyer est requis.',
                'montant_loyer.integer' => 'Le montant du loyer doit être un nombre entier.',
                'montant_loyer.min' => 'Le montant du loyer doit être au moins de 1000 FCFA.',
            ]);

            $user = Auth::user();
            $oldMontant = $user->montant_loyer;
            $user->montant_loyer = $request->montant_loyer;
            $user->save();

            return response()->json([
                'message' => 'Montant du loyer mis à jour avec succès.',
                'old_montant' => $oldMontant,
                'new_montant' => $user->montant_loyer,
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors()['montant_loyer'][0] ?? 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du montant.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Récupère les informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        // ajouter les information du qyc si c'est un propriétaire
        if ($user->role === 'proprietaire') {
            $proprio = \App\Models\Proprietaire::where('email', $user->email)->first();
            if ($proprio) {
                $user->code_proprio = $proprio->code_proprio;
                $user->proprietaire_id = $proprio->id;

            }
            $kyc = \App\Models\Kyc::where('user_id', $user->id)->first();
            if ($kyc) {
                $user->kyc_status = $kyc->status;
                $user->kyc_comment = $kyc->comment;
            } else {
                $user->kyc_status = 'non soumis';
                $user->kyc_comment = null; 
            }
        } else {
            $user->code_proprio = null;
            $user->proprietaire_id = null;
            $user->kyc_status = null;
            $user->kyc_comment = null;
        }
        return response()->json($user);
    }

    /**
     * suprimer le compte de l'utilisateur connecté
     */
  public function deleteAccount(Request $request)
{
    $user = $request->user(); // ou Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non authentifié'], 401);
    }

    try {
        // Supprimer le KYC lié à l'utilisateur
        Kyc::where('user_id', $user->id)->delete();

        // Supprimer le propriétaire lié
        Proprietaire::where('email', $user->email)->delete();

        // Supprimer le locataire lié
        Locataire::where('email', $user->email)->delete();

        // Supprimer l'utilisateur lui-même
        $user->delete();

        return response()->json(['message' => 'Compte supprimé avec succès'], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Une erreur est survenue lors de la suppression du compte.',
            'details' => $e->getMessage(),
        ], 500);
    }
}


     
    /** update user all */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|unique:users,phone,' . $user->id,
        ]);

        $user->update($request->only('nom', 'prenom', 'email', 'phone'));

        return response()->json(['message' => 'Informations mises à jour avec succès', 'user' => $user]);
    }

} 