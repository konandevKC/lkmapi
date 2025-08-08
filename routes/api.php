<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\Api\PayementogoController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/user/update', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/user/delete', [AuthController::class, 'deleteAccount'])->middleware('auth:sanctum');

Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//Route::get('/test', function() { return 'ok'; });

Route::middleware('auth:sanctum')->group(function () {
    //delete locataire connecté
   // Route::delete('/locataire/delete', [\App\Http\Controllers\Api\LocataireController::class, 'destroy']);
    
    Route::post('/user/updatemontant', [AuthController::class, 'updateMontantLoyer']);
    Route::post('/kyc/submit', [KycController::class, 'submit']);
    Route::get('/kyc/status', [KycController::class, 'status']);
    Route::post('/kyc/validate/{userId}', [KycController::class, 'validateKyc']);
    Route::get('/proprietaire/me', [\App\Http\Controllers\Api\ProprietaireController::class, 'getCurrent']);
    Route::get('/proprietaire/wallet', [\App\Http\Controllers\Api\ProprietaireController::class, 'wallet']);

    Route::get('/proprietaire/by-code/{code}', [\App\Http\Controllers\Api\ProprietaireController::class, 'byCode']);
    Route::post('/locataire/link-proprietaire', [\App\Http\Controllers\Api\LocataireController::class, 'linkToProprietaire']);
    Route::get('/locataire/me', [\App\Http\Controllers\Api\LocataireController::class, 'me']);
    Route::get('locataire/proprietaire', [\App\Http\Controllers\Api\LocataireController::class, 'getProprietaire']);

    // Paiements
    Route::apiResource('paiements', \App\Http\Controllers\Api\PaiementController::class);
});

Route::post('/payementogo/token', [PayementogoController::class, 'testToken']);
Route::post('/payementogo/checkout', [PayementogoController::class, 'checkout']);
Route::get('/payementogo/status/{ref}', [PayementogoController::class, 'getStatus']);
Route::post('/sycapay/notify', [PayementogoController::class, 'notify']); 