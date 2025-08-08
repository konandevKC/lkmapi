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
* paiment loyer locataire
 */
    public function paiementLoyer(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        $proprio = Proprietaire::where('email', $user->email)->first();
        if (!$proprio) {
            return response()->json(['error' => 'Aucun propriétaire trouvé pour cet utilisateur'], 404);
        } 
        // Configuration
$config = [
    'supported_channels' => ['OMCI', 'WAVECI', 'DJAMO', 'CARD'],
    'merchant_id' => "MI_0G1OTAQQAN",
    'aggregated_merchant_id' => "am-1j54gkvb820we",
    'urls' => [
        'notification' => "https://leprixdenossourires.com/webhook-malia.php",
        'return' => "https://leprixdenossourires.com/retour-paiement.php",
        'error' => "https://leprixdenossourires.com/erreur-paiement.html",
        'success' => "https://leprixdenossourires.com/merci-commande.html"
    ]
];

// Récupération et vérification du canal
$channel = strtoupper(trim($_POST['channel'] ?? ''));

if (!in_array($channel, $config['supported_channels'])) {
    jsonResponse('error', 'Canal de paiement non supporté', null, ['supported_channels' => $config['supported_channels']]);
}

// Préparation des données
$order = $_SESSION['precommande'];
$reference = 'CMD_' . strtoupper(bin2hex(random_bytes(4)));

// Sécurité : vérification des champs attendus dans $_SESSION['precommande']
if (!isset($order['totalFCFA'], $order['lastName'], $order['firstName'], $order['countryCode'], $order['phoneNumber'], $order['email'])) {
    jsonResponse('error', 'Données de commande incomplètes');
}

$apiData = [
    "montant" => $order['totalFCFA'],
    "reference" => $reference,
    "description" => "Commande - Le Prix de Nos Sourires",
    "channel" => $channel,
    "merchant_id" => $config['merchant_id'],
    "aggregated_merchant_id" => $config['aggregated_merchant_id'],
    "customer_name" => $order['lastName'],
    "customer_surname" => $order['firstName'],
    "customer_phone_number" => $order['countryCode'] . $order['phoneNumber'],
    "customer_email" => $order['email'],
    "notification_url" => $config['urls']['notification'],
    "return_url" => $config['urls']['return'],
    "error_url" => $config['urls']['error'],
    "success_url" => $config['urls']['success']
];

// Sauvegarde tentative paiement
$_SESSION['pending_payment'] = [
    'reference' => $reference,
    'amount' => $order['totalFCFA'],
    'channel' => $channel,
    'timestamp' => time()
];

// Envoi via cURL
$urlpro = "https://malia-pay.com/api/v1/OnlinePaymentService/add_payer";
$url = "https://sandbox.malia-pay.com/add_payer";
$jsonData = json_encode($apiData);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $jsonData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]
]);

$response = curl_exec($curl);

if ($response === false) {
    $error = curl_error($curl);
    curl_close($curl);
    file_put_contents("log_curl_error.txt", $error);
    jsonResponse('error', 'Erreur de communication avec le serveur de paiement.', null, ['curl_error' => $error]);
}

$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

file_put_contents("log_api_response.txt", "HTTP $http_code\n$response");

$apiResponse = json_decode($response, true);  
        // Ici, creer une logique pour le paiement du loyer avec curl
        // Par exemple, vous pouvez appeler un service de paiement externe ou mettre à jour le solde du propriétaire
        // Pour l'instant, on va juste simuler un paiement réussi   
        // Vous pouvez ajouter une logique pour vérifier le montant du loyer, le solde du propriétaire, etc.
        // Assurez-vous de gérer les erreurs et les exceptions potentielles
        // Par exemple, si le paiement échoue, vous pouvez retourner une réponse d'erreur
        // Si le paiement est réussi, vous pouvez mettre à jour le solde du propriétaire ou effectuer d'autres actions nécessaires
        // Pour l'instant, on va juste retourner une réponse de succès
        // Vous pouvez également enregistrer le paiement dans une base de données ou effectuer d'autres actions


        // Logique pour le paiement du loyer
        return response()->json(['message' => 'Paiement du loyer effectué avec succès']);
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
            return response()->json(['error' => 'Aucun  propriétaire trouvé pour ce code'.$proprio], 404);
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
