<?php

namespace App\Http\Controllers\Api;
  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Payementogo;
class PayementogoController extends Controller
{
 

 

      /**
       * Obtenir le token d'authentification
       */
    

public function getToken($montant)
{
    $headers = [
      "X-SYCA-MERCHANDID: " . env("SYCA_MERCHAND_ID"),
      "X-SYCA-APIKEY: " . env("SYCA_API_KEY"),
        'X-SYCA-REQUEST-DATA-FORMAT: JSON',
        'X-SYCA-RESPONSE-DATAFORMAT: JSON',
    ];

    $paramsend = [
        "montant" => $montant,
        "curr" => "XOF"
    ];

    $url = "https://dev.sycapay.com/login.php";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paramsend));

    $rawResponse = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false) {
        // Tu peux aussi lever une exception ici
        return null;
    }

    $response = json_decode($rawResponse, true);

    if (isset($response['code']) && $response['code'] == 0) {
        return $response['token'];
    }

    return null;
}


    /**
     * Méthode privée pour obtenir le token Sycapay
     */
    private function getSycapayToken($montant)
    {
        $headers = [
            "X-SYCA-MERCHANDID: " . env("SYCA_MERCHAND_ID"),
            "X-SYCA-APIKEY: " . env("SYCA_API_KEY"),
            "X-SYCA-REQUEST-DATA-FORMAT: JSON",
            "X-SYCA-RESPONSE-DATA-FORMAT: JSON",
        ];

        $payload = [
            "montant" => $montant,
            "currency" => "XOF"
        ];

        $url = "https://dev.sycapay.com/login.php";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['token'] ?? null;
    }

    public function testToken(Request $request)
{
    $montant = $request->input('montant'); // valeur par défaut 100
    $token = $this->getToken($montant);
    return response()->json(['token' => $token]);
}
    /**
     * Génère une chaîne aléatoire de 15 caractères pour numcommande
     */
    private function generateNumCommande($length = 15)
    {
        return substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
    }

    /**
     * Initier un paiement (checkout) adapté depuis le code Dart
     */
    public function checkout(Request $request)
{
    // Validation des champs requis
    $request->validate([
        'telephone' => 'required|string',
        'montant' => 'required|numeric',
        'operateurs' => 'required|string',
        'name' => 'required|string',
        'pname' => 'required|string',
        // 'otp' => 'sometimes|string', // Décommente si nécessaire
        // 'pays' => 'sometimes|string',
        // 'numcommande' => 'sometimes|string', // Optionnel
    ]);

    // Récupération du token Sycapay
    $token = $this->getToken($request->montant);

    // Utilise le numcommande fourni ou génère-le
    $numcommande = $request->input('numcommande') ?? $this->generateNumCommande();

    // Préparation des données pour le checkout
    $payload = [
        "marchandid" => env("SYCA_MERCHAND_ID"),
        "token" => $token,
        "telephone" => $request->telephone,
        "name" => $request->name,
        "pname" => $request->pname, // Remplace par prénom si disponible
        "montant" => $request->montant,
        "currency" => "XOF",
        "numcommande" => $numcommande,
        "pays" => "TG", // Tu peux rendre ce champ dynamique si besoin
        "operateurs" => $request->operateurs,
        // "otp" => $request->operateurs === "orange" ? $request->otp : null, // Décommente si utilisé
        "urlnotif" =>  url('/payementogo/status/'.$numcommande),
    ];

    // Configuration des headers
    $headers = [
        "Content-Type: application/json; charset=utf-8"
    ];

    // Envoi de la requête vers Sycapay
    $ch = curl_init("https://dev.sycapay.com/checkoutpay.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // À éviter en production
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur cURL: ' . $error
        ], 500);
    }

    $data = json_decode($response, true);

    // Récupère le transactionId (ou ref) dans la réponse Sycapay
    $transactionId = $data['transactionId'];

    // Création de la transaction Payementogo
    Payementogo::create([
        'ref' => $transactionId,
        'telephone' => $request->telephone,
        'name' => $request->name,
        'pname' => $request->pname,
        'montant' => $request->montant,
        'currency' => 'XOF',
        'numcommande' => $numcommande,
        //'otp' => $request->otp ?? null,
        'pays' => 'TG',
        'operateurs' => $request->operateurs,
        'status' => 'en cours',
       // 'raw_response' => $response,
    ]);

    return response()->json($data);
}

      /**
       * Vérifier le statut d'une transaction
       */
      public function getStatus($numcommande)
      {
          // Recherche de la transaction
          $transaction = Payementogo::where('numcommande', $numcommande)->first();
      
          if (!$transaction) {
              return response()->json(['error' => 'Transaction non trouvée'], 404);
          }
      
          $ref = $transaction->ref;
      
          $url = "https://dev.sycapay.com/GetStatus.php";
      
          $payload = [
              "ref" => $ref
          ];
      
          $ch = curl_init($url);

          curl_setopt_array($ch, [
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_SSL_VERIFYPEER => false, // Ne pas désactiver en production !
              CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
              CURLOPT_POST => true,
              CURLOPT_POSTFIELDS => json_encode($payload),
          ]);
      
          $response = curl_exec($ch);
          $error = curl_error($ch);
          curl_close($ch);

          // Gestion des erreurs cURL
          if ($error) {
              return response()->json([
                  'success' => false,
                  'message' => 'Erreur cURL : ' . $error
              ], 500);
          }

          $data = json_decode($response, true);

          if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
              return response()->json([
                  'success' => false,
                  'message' => 'Réponse invalide du serveur Sycapay.',
                  'raw_response' => $response
              ], 500);
          }

          // Mise à jour du statut en fonction de la réponse
          if (isset($data['code']) && $data['code'] == 0) {
              $transaction->status = 'succes';
          } else {
              $transaction->status = "echec";
          }
      
          $transaction->save();

          // Structure la réponse
          return response()->json([
              'success' => $data['code'] == 0,
              'code' => $data['code'],
              'message' => $data['message'] ?? null,
              'transaction' => [
                  'montant' => $data['montant'] ?? null,
                  'orderId' => $data['orderId'] ?? null,
                  'transactionID' => $data['transactionID'] ?? null,
                  'paiementId' => $data['paiementId'] ?? null,
                  'mobile' => $data['mobile'] ?? null,
                  'date' => $data['date'] ?? null,
                  'operator' => $data['operator'] ?? null,
              ]
          ]);
      }
      
  
      /**
       * Endpoint pour recevoir la notification SYCAPAY
       */
      public function notify(Request $request)
      {
          Log::info("Notification SYCAPAY reçue", $request->all());
  
          // Ici, tu peux sauvegarder la transaction dans la base de données si tu veux.
  
          return response()->json(['message' => 'Notification reçue']);
      }
  }
  
