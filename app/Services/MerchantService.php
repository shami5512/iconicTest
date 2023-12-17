<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => User::MERCHANT_TYPE, // Assuming you have constants in the User model
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['display_name'],
        ]);

        return $merchant;
    }

    /**
     * Update the merchant user.
     *
     * @param User $user
     * @param array{domain: string, display_name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $user->update([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->merchant()->update([
            'domain' => $data['domain'],
            'display_name' => $data['display_name'],
        ]);
    }

    /**
     * Find a merchant by their email.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->where('type', User::MERCHANT_TYPE)->first();

        return $user ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = Order::where('affiliate_id', $affiliate->id)->where('paid', false)->get();

        foreach ($unpaidOrders as $order) {
            // Dispatch a job to handle the payout for each order
            dispatch(new PayoutOrderJob($order));
        }
    }    
}
