<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        try {
            // Validate the request parameters
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);

            // Parse dates from the request
            $fromDate = Carbon::parse($request->input('from'));
            $toDate = Carbon::parse($request->input('to'));

            // Get the merchant orders within the date range
            $merchant = Merchant::where('user_id', auth()->id())->first();
            $orders = $this->merchantService->getOrdersInDateRange($merchant, $fromDate, $toDate);

            // Calculate order statistics
            $orderCount = count($orders);
            $commissionOwed = $this->merchantService->calculateCommissionOwed($orders);
            $revenue = $this->merchantService->calculateRevenue($orders);

            // Return the response
            return response()->json([
                'count' => $orderCount,
                'commission_owed' => $commissionOwed,
                'revenue' => $revenue,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception
            \Log::error('Order statistics error: ' . $e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
