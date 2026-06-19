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
        $this->authenticate();
        return Beneficiary::create($params);
    }

    /**
     * Initialize a transfer (payout).
     */
    public function initializeTransfer(array $params)
    {
        $this->authenticate();
        return Transfer::initialize($params);
    }
}
