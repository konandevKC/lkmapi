<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required']);
        $otp = rand(1000, 9999);
        OtpCode::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $otp, 'expires_at' => now()->addMinutes(5)]
        );
        // Envoi SMS ici (Twilio, etc.)
        return response()->json(['message' => 'OTP envoyé']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['phone' => 'required', 'code' => 'required']);
        $otp = OtpCode::where('phone', $request->phone)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();
        if (!$otp) {
            return response()->json(['message' => 'OTP invalide ou expiré'], 422);
        }
        $otp->delete();
        return response()->json(['message' => 'OTP validé']);
    }
} 