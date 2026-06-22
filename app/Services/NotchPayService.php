<?php

namespace App\Services;

use NotchPay\NotchPay;
use NotchPay\Payment;
use NotchPay\Beneficiary;
use NotchPay\Transfer;

class NotchPayService
{
    /**
     * Set the dual-header authentication keys on the Notch Pay SDK.
     */
    protected function authenticate()
    {
        $publicKey = config('services.notchpay.public_key');
        $secretKey = config('services.notchpay.secret_key');
        
        if ($publicKey) {
            NotchPay::$apiKey = $publicKey;
        }
        if ($secretKey) {
            NotchPay::$privateKey = $secretKey;
        }
    }

    /**
     * Initialize a payment.
     */
    public function initializePayment(array $params)
    {
        $this->authenticate();
        return Payment::initialize($params);
    }

    /**
     * Charge a payment directly (e.g. mobile money push).
     */
    public function chargePayment(string $reference, array $params)
    {
        $this->authenticate();
        return Payment::charge($reference, $params);
    }

    /**
     * Verify a payment status.
     */
    public function verifyPayment(string $reference)
    {
        $this->authenticate();
        return Payment::verify($reference);
    }

    /**
     * Create a transfer beneficiary.
     */
    public function createBeneficiary(array $params)
    {
        $publicKey = config('notchpay.public_key') ?? config('services.notchpay.public_key');
        $privateKey = config('notchpay.private_key') ?? config('services.notchpay.secret_key');
        $apiUrl = rtrim(config('notchpay.api_url', 'https://api.notchpay.co'), '/');

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $publicKey,
                    'X-Grant'       => $privateKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post("{$apiUrl}/beneficiaries", [
                    'name'           => $params['name'] ?? '',
                    'phone'          => $params['phone'] ?? '',
                    'account_number' => $params['account_number'] ?? ($params['phone'] ?? ''),
                    'email'          => $params['email'] ?? '',
                    'channel'        => $params['channel'] ?? 'cm.mobile',
                    'country'        => strtolower($params['country'] ?? 'CM'),
                ]);

            $data = $response->json();

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('NotchPay direct beneficiary creation failed', [
                    'status' => $response->status(),
                    'response' => $data
                ]);
                $errorMsg = $data['message'] ?? 'Erreur API';
                if (!empty($data['errors'])) {
                    $errorMsg .= ' : ' . json_encode($data['errors']);
                }
                throw new \Exception($errorMsg);
            }

            return (object)[
                'id' => $data['beneficiary']['id'] ?? null,
                'beneficiary' => (object)($data['beneficiary'] ?? [])
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('NotchPay beneficiary creation exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize a transfer (payout).
     */
    public function initializeTransfer(array $params)
    {
        $publicKey = config('notchpay.public_key') ?? config('services.notchpay.public_key');
        $privateKey = config('notchpay.private_key') ?? config('services.notchpay.secret_key');
        $apiUrl = rtrim(config('notchpay.api_url', 'https://api.notchpay.co'), '/');

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $publicKey,
                    'X-Grant'       => $privateKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post("{$apiUrl}/transfers", [
                    'amount'      => $params['amount'] ?? 0,
                    'currency'    => $params['currency'] ?? 'XAF',
                    'beneficiary' => $params['beneficiary'] ?? '',
                    'description' => $params['description'] ?? 'Transfer',
                    'reference'   => $params['reference'] ?? '',
                ]);

            $data = $response->json();

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('NotchPay direct transfer initialization failed', [
                    'status' => $response->status(),
                    'response' => $data
                ]);
                $errorMsg = $data['message'] ?? 'Erreur API';
                if (!empty($data['errors'])) {
                    $errorMsg .= ' : ' . json_encode($data['errors']);
                }
                throw new \Exception($errorMsg);
            }

            return (object)($data['transfer'] ?? []);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('NotchPay transfer exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
