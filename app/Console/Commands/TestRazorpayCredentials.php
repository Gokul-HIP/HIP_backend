<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class TestRazorpayCredentials extends Command
{
    protected $signature = 'razorpay:test-credentials';
    protected $description = 'Test if Razorpay credentials are valid and API is reachable';

    public function handle()
    {
        $this->info('Testing Razorpay Credentials...');
        $this->info('═══════════════════════════════════════');

        // Check if credentials exist
        $keyId = env('RAZORPAY_KEY_ID') ?? env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_KEY_SECRET') ?? env('RAZORPAY_SECRET');

        if (!$keyId) {
            $this->error('❌ RAZORPAY_KEY_ID (or RAZORPAY_KEY) not found in .env');
            return 1;
        }

        if (!$keySecret) {
            $this->error('❌ RAZORPAY_KEY_SECRET (or RAZORPAY_SECRET) not found in .env');
            return 1;
        }

        $this->info('✓ Credentials found in .env');
        $this->line("  Key ID: " . substr($keyId, 0, 10) . "...");
        $this->line("  Key Secret: " . substr($keySecret, 0, 10) . "...");

        // Test API initialization
        $this->info('');
        $this->info('Testing API initialization...');
        
        try {
            $api = new Api($keyId, $keySecret);
            $this->info('✓ Razorpay API initialized successfully');
        } catch (\Exception $e) {
            $this->error('❌ Failed to initialize Razorpay API');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        // Test API connectivity
        $this->info('');
        $this->info('Testing API connectivity with test order...');
        
        try {
            $order = $api->order->create([
                'receipt' => 'test_' . time(),
                'amount' => 10000, // 100 INR in paise
                'currency' => 'INR',
            ]);
            
            $this->info('✓ API is reachable and working!');
            $this->info('✓ Test order created successfully!');
            $this->line("  Order ID: " . $order->id);
            $this->line("  Amount: " . ($order->amount / 100) . " INR");
            $this->line("  Status: " . ($order->status ?? 'N/A'));
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create test order');
            $this->error('Error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Authentication') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
                $this->error('');
                $this->error('This indicates your API credentials are invalid or not authorized.');
                $this->error('Please verify:');
                $this->error('  1. You have the correct Razorpay API keys');
                $this->error('  2. The keys have not expired');
                $this->error('  3. The account has API access enabled');
                $this->error('  4. You are using the correct environment (test vs production)');
            }
            
            return 1;
        }
    }
}
