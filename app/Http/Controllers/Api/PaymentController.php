<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Ride;
use App\Services\FareCalculationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $fareService;

    public function __construct(FareCalculationService $fareService)
    {
        $this->fareService = $fareService;
    }

    /**
     * Calculate fare estimate
     */
    public function calculateFare(Request $request)
    {
        $request->validate([
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
        ]);

        $fareData = $this->fareService->calculateFare(
            $request->pickup_lat,
            $request->pickup_lng,
            $request->dropoff_lat,
            $request->dropoff_lng
        );

        return response()->json([
            'success' => true,
            'data' => $fareData,
        ]);
    }

    /**
     * Process payment for a ride
     */
    public function process(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:rides,id',
            'payment_method' => 'required|in:cash,card,wallet,apple_pay,google_pay',
        ]);

        $user = $request->user();
        $ride = Ride::findOrFail($request->ride_id);

        // Check if ride belongs to user
        if ($ride->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if payment already exists
        if ($ride->payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already processed',
            ], 400);
        }

        $payment = Payment::create([
            'ride_id' => $ride->id,
            'user_id' => $user->id,
            'payment_method' => $request->payment_method,
            'amount' => $ride->total_fare,
            'status' => 'pending',
        ]);

        // Process payment based on method
        if ($request->payment_method === 'cash') {
            // Cash payment - mark as completed
            $payment->markAsCompleted();
        } else {
            // Card/Digital payment - integrate with payment gateway
            // TODO: Integrate with Stripe, PayPal, etc.
            $payment->markAsCompleted('GATEWAY_REF_' . now()->timestamp);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment->load('ride'),
        ]);
    }

    /**
     * Get payment history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $payments = $user->payments()
            ->with('ride')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['ride', 'user']);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }
}
