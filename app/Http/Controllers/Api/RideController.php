<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\FareCalculationService;
use Illuminate\Http\Request;

class RideController extends Controller
{
    protected $fareService;

    public function __construct(FareCalculationService $fareService)
    {
        $this->fareService = $fareService;
    }

    /**
     * Get user's rides
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $rides = $user->isDriver() 
            ? $user->ridesAsDriver()->with('customer')->latest()->get()
            : $user->ridesAsCustomer()->with('driver')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $rides,
        ]);
    }

    /**
     * Create a new ride booking
     */
    public function store(Request $request)
    {
        $request->validate([
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'pickup_terminal' => 'nullable|string',
            'dropoff_address' => 'required|string',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
            'dropoff_terminal' => 'nullable|string',
            'vehicle_type' => 'required|in:car,motorcycle',
        ]);

        $user = $request->user();

        // Calculate fare
        $fareData = $this->fareService->calculateFare(
            $request->pickup_lat,
            $request->pickup_lng,
            $request->dropoff_lat,
            $request->dropoff_lng
        );

        $ride = Ride::create([
            'customer_id' => $user->id,
            'pickup_address' => $request->pickup_address,
            'pickup_lat' => $request->pickup_lat,
            'pickup_lng' => $request->pickup_lng,
            'pickup_terminal' => $request->pickup_terminal,
            'dropoff_address' => $request->dropoff_address,
            'dropoff_lat' => $request->dropoff_lat,
            'dropoff_lng' => $request->dropoff_lng,
            'dropoff_terminal' => $request->dropoff_terminal,
            'vehicle_type' => $request->vehicle_type,
            'distance_km' => $fareData['distance_km'],
            'estimated_duration_mins' => $fareData['estimated_duration_mins'],
            'base_fare' => $fareData['base_fare'],
            'distance_fare' => $fareData['distance_fare'],
            'time_fare' => $fareData['time_fare'],
            'booking_fee' => $fareData['booking_fee'],
            'total_fare' => $fareData['total_fare'],
            'status' => 'searching',
        ]);

        // TODO: Notify nearby drivers about new ride request

        return response()->json([
            'success' => true,
            'message' => 'Ride booked successfully',
            'data' => $ride,
        ], 201);
    }

    /**
     * Get ride details
     */
    public function show(Ride $ride)
    {
        $ride->load(['customer', 'driver', 'payment']);

        return response()->json([
            'success' => true,
            'data' => $ride,
        ]);
    }

    /**
     * Cancel a ride
     */
    public function cancel(Request $request, Ride $ride)
    {
        $user = $request->user();

        // Check authorization
        if ($ride->customer_id !== $user->id && $ride->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$ride->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Ride cannot be cancelled at this stage',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $cancelledBy = $ride->customer_id === $user->id ? 'customer' : 'driver';
        $ride->cancel($request->reason, $cancelledBy);

        return response()->json([
            'success' => true,
            'message' => 'Ride cancelled successfully',
            'data' => $ride,
        ]);
    }

    /**
     * Rate a completed ride
     */
    public function rate(Request $request, Ride $ride)
    {
        $user = $request->user();

        if (!$ride->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Can only rate completed rides',
            ], 400);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        if ($ride->customer_id === $user->id) {
            // Customer rating driver
            $ride->update([
                'driver_rating' => $request->rating,
                'driver_review' => $request->review,
            ]);

            // Update driver's overall rating
            $driver = $ride->driver;
            if ($driver) {
                $newTotalRatings = $driver->total_ratings + 1;
                $newRating = (($driver->rating * $driver->total_ratings) + $request->rating) / $newTotalRatings;
                $driver->update([
                    'rating' => round($newRating, 2),
                    'total_ratings' => $newTotalRatings,
                ]);
            }
        } elseif ($ride->driver_id === $user->id) {
            // Driver rating customer
            $ride->update([
                'customer_rating' => $request->rating,
                'customer_review' => $request->review,
            ]);

            // Update customer's rating
            $customer = $ride->customer;
            $newTotalRatings = $customer->total_ratings + 1;
            $newRating = (($customer->rating * $customer->total_ratings) + $request->rating) / $newTotalRatings;
            $customer->update([
                'rating' => round($newRating, 2),
                'total_ratings' => $newTotalRatings,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'data' => $ride,
        ]);
    }

    /**
     * Track ride in real-time
     */
    public function track(Ride $ride)
    {
        $ride->load(['driver.driver']);

        return response()->json([
            'success' => true,
            'data' => [
                'ride' => $ride,
                'driver_location' => [
                    'lat' => $ride->driver?->driver?->current_lat,
                    'lng' => $ride->driver?->driver?->current_lng,
                    'updated_at' => $ride->driver?->driver?->last_location_update,
                ],
            ],
        ]);
    }
}
