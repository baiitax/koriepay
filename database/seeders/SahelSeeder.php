<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class SahelSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123'); // Standard password for all test accounts

        // 1. SUPER ADMIN (God Mode - Based in HQ Niger)
        $admin = User::firstOrCreate(
            ['email' => 'admin@sahelpay.com'],
            [
                'name' => 'Sahel SuperAdmin',
                'phone_number' => '+22700000001',
                'password' => $password,
                'role' => 'superadmin',
                'country_code' => 'NER',
                'kyc_status' => 'verified',
            ]
        );
        // Admin gets both XOF and NGN wallets for testing FX
        $this->fundWallet($admin, 'XOF', 50000000, true);
        $this->fundWallet($admin, 'NGN', 20000000, false);

        // 2. TECHNICAL TEAM (System Doctor - Based in Nigeria)
        $tech = User::firstOrCreate(
            ['email' => 'tech@sahelpay.com'],
            [
                'name' => 'System Doctor',
                'phone_number' => '+23400000002',
                'password' => $password,
                'role' => 'technical',
                'country_code' => 'NGA',
                'kyc_status' => 'verified',
            ]
        );
        $this->fundWallet($tech, 'NGN', 500000, true);

        // 3. REGIONAL MANAGER (Niger)
        $manager = User::firstOrCreate(
            ['email' => 'manager@sahelpay.com'],
            [
                'name' => 'Regional Manager NER',
                'phone_number' => '+22700000003',
                'password' => $password,
                'role' => 'manager',
                'country_code' => 'NER',
                'kyc_status' => 'verified',
            ]
        );
        $this->fundWallet($manager, 'XOF', 5000000, true);

        // 4. INVESTOR (Cross-border watcher)
        $investor = User::firstOrCreate(
            ['email' => 'investor@sahelpay.com'],
            [
                'name' => 'Sahel Capital Partners',
                'phone_number' => '+23400000004',
                'password' => $password,
                'role' => 'investor',
                'country_code' => 'NGA',
                'kyc_status' => 'verified',
            ]
        );
        $this->fundWallet($investor, 'USD', 100000, true); // Investors might track in USD

        // 5. AGENT (The cash-in/cash-out operator reporting to Manager)
        $agent = User::firstOrCreate(
            ['email' => 'agent@sahelpay.com'],
            [
                'name' => 'Cashpoint Agent Niamey',
                'phone_number' => '+22700000005',
                'password' => $password,
                'role' => 'agent',
                'country_code' => 'NER',
                'manager_id' => $manager->id, // Linked to the manager
                'kyc_status' => 'verified',
            ]
        );
        $this->fundWallet($agent, 'XOF', 250000, true);

        // 6. CUSTOMER CARE (Support)
        $support = User::firstOrCreate(
            ['email' => 'support@sahelpay.com'],
            [
                'name' => 'Sahel Helpdesk',
                'phone_number' => '+23400000006',
                'password' => $password,
                'role' => 'support',
                'country_code' => 'NGA',
                'kyc_status' => 'verified',
            ]
        );

        // 7. REGULAR CUSTOMER (End User)
        $customer = User::firstOrCreate(
            ['email' => 'customer@sahelpay.com'],
            [
                'name' => 'Musa Customer',
                'phone_number' => '+22700000007',
                'password' => $password,
                'role' => 'customer',
                'country_code' => 'NER',
                'kyc_status' => 'verified',
            ]
        );
        $this->fundWallet($customer, 'XOF', 15000, true);
        
        $this->command->info('SahelPay Super-Seeder completed successfully!');
    }

    /**
     * Helper to safely create and fund wallets without duplicates
     */
    private function fundWallet($user, $currency, $amount, $isPrimary)
    {
        Wallet::updateOrCreate(
            ['user_id' => $user->id, 'currency_code' => $currency],
            ['balance' => $amount, 'is_primary' => $isPrimary]
        );
    }
}