<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmileIdService
{
    protected $baseUrl;
    protected $apiKey;
    protected $partnerId;

    public function __construct()
    {
        $this->baseUrl = config('services.smileid.url', 'https://api.smileidentity.com/v1');
        $this->apiKey = config('services.smileid.api_key');
        $this->partnerId = config('services.smileid.partner_id');
    }

    /**
     * TIER-1: Enhanced Document Verification
     */
    public function verifyIdentity($user, $idType, $idNumber, $selfieImage, $idCardImage)
    {
        $timestamp = now()->toIso8601String();
        
        $payload = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'job_id' => 'KYC-' . $user->id . '-' . time(),
            'user_id' => 'USER-' . $user->id,
            'job_type' => 6, // 6 = Enhanced Document Verification
            'country' => $user->country_code === 'NER' ? 'NE' : 'NG',
            'id_type' => $idType,
            'id_number' => $idNumber,
            'images' => [
                [
                    'image_type_id' => 0, // 0 = Selfie
                    'image' => base64_encode(file_get_contents($selfieImage->getRealPath())),
                ],
                [
                    'image_type_id' => 1, // 1 = ID Card Front
                    'image' => base64_encode(file_get_contents($idCardImage->getRealPath())),
                ]
            ],
            // Callback for asynchronous results
            'callback_url' => route('api.kyc.webhook'), 
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->post($this->baseUrl . '/upload', $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Smile ID API Crash: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Identity server timeout.'];
        }
    }
}