<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Get nearby available drivers
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'sometimes|numeric|min:1|max:50',
        ]);

        $radius = $request->radius ?? 10; // Default 10km

        // Simple distance calculation query
        // For production, consider using PostGIS for better performance
        $drivers = Driver::select('drivers.*')
            ->selectRaw('
                (6371 * acos(
                    cos(radians(?)) * cos(radians(current_lat)) *
                    cos(radians(current_lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(current_lat))
                )) AS distance
            ', [$request->lat, $request->lng, $request->lat])
            ->where('status', 'available')
            ->where('is_verified', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->with('user')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    /**
     * Register as driver
     */
    public function register(Request $request)
    {
        $user = $request->user();

        if ($user->driver) {
            return response()->json([
                'success' => false,
                'message' => 'Already registered as driver',
            ], 400);
        }

        $request->validate([
            'license_number' => 'required|string|unique:drivers',
            'vehicle_type' => 'required|in:car,motorcycle,van',
            'vehicle_make' => 'required|string',
            'vehicle_model' => 'required|string',
            'vehicle_year' => 'required|string',
            'vehicle_color' => 'required|string',
            'license_plate' => 'required|string|unique:drivers',
            'vehicle_photo' => 'sometimes|image|max:2048',
        ]);

        $driverData = $request->only([
            'license_number',
            'vehicle_type',
            'vehicle_make',
            'vehicle_model',
            'vehicle_year',
            'vehicle_color',
            'license_plate',
        ]);

        $driverData['user_id'] = $user->id;

        if ($request->hasFile('vehicle_photo')) {
            $path = $request->file('vehicle_photo')->store('vehicles', 'public');
            $driverData['vehicle_photo'] = $path;
        }

        $driver = Driver::create($driverData);

        // Update user role
        $user->update(['role' => 'driver']);

        return response()->json([
            'success' => true,
            'message' => 'Driver registration submitted. Awaiting verification.',
            'data' => $driver->load('user'),
        ], 201);
    }

    /**
     * Update driver status (available/offline)
     */
    public function updateStatus(Request $request)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Not registered as driver',
            ], 400);
        }

        $request->validate([
            'status' => 'required|in:available,offline',
        ]);

        $driver->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $driver,
        ]);
    }

    /**
     * Update driver location
     */
    public function updateLocation(Request $request)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Not registered as driver',
            ], 400);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $driver->updateLocation($request->lat, $request->lng);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
        ]);
    }

    /**
     * Get driver earnings
     */
    public function earnings(Request $request)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Not registered as driver',
            ], 400);
        }

        $rides = $user->ridesAsDriver()
            ->where('status', 'completed')
            ->get();

        $totalEarnings = $rides->sum('total_fare');
        $todayEarnings = $rides->where('completed_at', '>=', now()->startOfDay())->sum('total_fare');
        $weekEarnings = $rides->where('completed_at', '>=', now()->startOfWeek())->sum('total_fare');
        $monthEarnings = $rides->where('completed_at', '>=', now()->startOfMonth())->sum('total_fare');

        return response()->json([
            'success' => true,
            'data' => [
                'total_earnings' => round($totalEarnings, 2),
                'today_earnings' => round($todayEarnings, 2),
                'week_earnings' => round($weekEarnings, 2),
                'month_earnings' => round($monthEarnings, 2),
                'total_trips' => $driver->total_trips,
                'average_per_trip' => $driver->total_trips > 0 ? round($totalEarnings / $driver->total_trips, 2) : 0,
            ],
        ]);
    }

    /**
     * Get driver trips
     */
    public function trips(Request $request)
    {
        $user = $request->user();
        
        $trips = $user->ridesAsDriver()
            ->with('customer')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Accept ride request
     */
    public function acceptRide(Request $request, Ride $ride)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver || !$driver->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not available',
            ], 400);
        }

        if ($ride->driver_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ride already accepted by another driver',
            ], 400);
        }

        $ride->assignDriver($user->id);
        $driver->setBusy();

        return response()->json([
            'success' => true,
            'message' => 'Ride accepted successfully',
            'data' => $ride->load('customer'),
        ]);
    }

    /**
     * Arrive at pickup location
     */
    public function arrive(Ride $ride)
    {
        $ride->markDriverArrived();

        return response()->json([
            'success' => true,
            'message' => 'Arrival confirmed',
            'data' => $ride,
        ]);
    }

    /**
     * Start the ride
     */
    public function startRide(Ride $ride)
    {
        $ride->start();

        return response()->json([
            'success' => true,
            'message' => 'Ride started',
            'data' => $ride,
        ]);
    }

    /**
     * Complete the ride
     */
    public function completeRide(Ride $ride)
    {
        $ride->complete();
        
        // Update driver status and earnings
        $driver = $ride->driver->driver;
        $driver->setAvailable();
        $driver->increment('total_trips');
        $driver->increment('total_earnings', $ride->total_fare);

        return response()->json([
            'success' => true,
            'message' => 'Ride completed',
            'data' => $ride,
        ]);
    }
}
