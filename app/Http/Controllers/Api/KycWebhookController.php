<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class KycWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        
        // Find user by the ID we sent in the job
        $userId = str_replace('USER-', '', $data['user_id']);
        $user = User::find($userId);

        if (!$user) return response()->json(['status' => 'ignored'], 404);

        // Logic: Check if Face Match and Document Verification are successful
        if ($data['ResultCode'] === '0812' || $data['ResultCode'] === '1012') {
            $user->update([
                'kyc_status' => 'verified',
                'kyc_notes' => 'Auto-verified via Smile ID'
            ]);
            
            // Trigger Tier-1 Benefit: Automatically provision Virtual Accounts
            // ProvisionVirtualAccountJob::dispatch($user);
            
        } else {
            $user->update(['kyc_status' => 'rejected']);
            // Notify user via Push/Email to retry
        }

        return response()->json(['status' => 'processed']);
    }
}