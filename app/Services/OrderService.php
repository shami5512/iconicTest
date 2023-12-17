<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // Check if the order with the given order_id already exists
        $existingOrder = Order::where('order_id', $data['order_id'])->first();

        if ($existingOrder) {
            // Ignore duplicates
            return;
        }

        // Find or create the merchant based on the provided domain
        $merchant = Merchant::firstOrCreate(['domain' => $data['merchant_domain']], ['display_name' => $data['merchant_domain']]);

        // Find or create the affiliate based on the customer_email
        $affiliate = $this->affiliateService->registerIfNotExists($merchant, $data['customer_email'], $data['customer_name']);

        // Calculate commission based on the subtotal_price and affiliate's commission rate
        $commission = $data['subtotal_price'] * $affiliate->commission_rate;

        // Create the order record
        Order::create([
            'order_id' => $data['order_id'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'subtotal' => $data['subtotal_price'],
            'commission_owed' => $commission,
            'payout_status' => Order::STATUS_UNPAID,
            'discount_code' => $data['discount_code'],
        ]);
    }
}
