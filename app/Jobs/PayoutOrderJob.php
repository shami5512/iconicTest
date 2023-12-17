<?php

namespace App\Jobs;

use App\Exceptions\PayoutException;
use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @param ApiService $apiService
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        try {
            // Use the API service to send a payout
            $apiService->sendPayout($this->order->affiliate, $this->order->commission_owed);

            // Update the order status to paid
            DB::transaction(function () {
                $this->order->update(['payout_status' => Order::STATUS_PAID]);
            });
        } catch (PayoutException $e) {
            // Log the exception
            Log::error('Payout failed for order ' . $this->order->order_id . ': ' . $e->getMessage());
        }
    }
}
