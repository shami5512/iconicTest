<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if the affiliate with the given email already exists
        $existingAffiliate = Affiliate::where('email', $email)->first();

        if ($existingAffiliate) {
            throw new AffiliateCreateException('Affiliate with this email already exists.');
        }

        // Create a new user for the affiliate
        $affiliateUser = User::create([
            'email' => $email,
            'type' => User::AFFILIATE_TYPE, // Assuming you have constants in the User model
        ]);

        // Create the affiliate record
        $affiliate = Affiliate::create([
            'user_id' => $affiliateUser->id,
            'merchant_id' => $merchant->id,
            'name' => $name,
            'email' => $email,
            'commission_rate' => $commissionRate,
        ]);

        // Send an email notification to the affiliate
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
