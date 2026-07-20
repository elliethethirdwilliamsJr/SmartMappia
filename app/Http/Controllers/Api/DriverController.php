<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    /**
     * Upload driver verification documents
     */
    public function uploadDocuments(Request $request)
    {
        $request->validate([
            'national_id' => 'required|image|max:5120', // 5MB max
            'driving_license' => 'required|image|max:5120',
            'vehicle_registration' => 'required|image|max:5120',
            'vehicle_insurance' => 'required|image|max:5120',
            'profile_photo' => 'required|image|max:5120',
            'vehicle_photo' => 'required|image|max:5120',
            'vehicle_type' => 'required|string',
            'vehicle_plate' => 'required|string',
        ]);

        $user = $request->user();

        // Check if user is a driver
        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can upload verification documents',
            ], 403);
        }

        // Store documents
        $nationalIdPath = $request->file('national_id')->store('driver_documents/national_ids', 'public');
        $drivingLicensePath = $request->file('driving_license')->store('driver_documents/driving_licenses', 'public');
        $vehicleRegistrationPath = $request->file('vehicle_registration')->store('driver_documents/vehicle_registrations', 'public');
        $vehicleInsurancePath = $request->file('vehicle_insurance')->store('driver_documents/vehicle_insurances', 'public');
        $profilePhotoPath = $request->file('profile_photo')->store('driver_documents/profile_photos', 'public');
        $vehiclePhotoPath = $request->file('vehicle_photo')->store('driver_documents/vehicle_photos', 'public');

        // Create or update driver record
        $driver = Driver::updateOrCreate(
            ['user_id' => $user->id],
            [
                'vehicle_type' => $request->vehicle_type,
                'license_plate' => $request->vehicle_plate,
                'national_id_photo' => $nationalIdPath,
                'driving_license_photo' => $drivingLicensePath,
                'vehicle_registration_photo' => $vehicleRegistrationPath,
                'vehicle_insurance_photo' => $vehicleInsurancePath,
                'profile_photo' => $profilePhotoPath,
                'vehicle_plate_photo' => $vehiclePhotoPath,
                'verification_status' => 'pending',
                'documents_submitted_at' => now(),
                'is_verified' => false,
                // Placeholder values for required fields
                'license_number' => $request->vehicle_plate, // Use plate as placeholder until approved
                'vehicle_make' => 'Pending',
                'vehicle_model' => 'Pending',
                'vehicle_year' => date('Y'),
                'vehicle_color' => 'Pending',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Documents uploaded successfully. Awaiting admin approval.',
            'data' => [
                'driver' => $driver,
                'verification_status' => $driver->verification_status,
            ],
        ], 201);
    }

    /**
     * Get driver verification status
     */
    public function getVerificationStatus(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can check verification status',
            ], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_submitted' => false,
                    'verification_status' => null,
                    'is_verified' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_submitted' => $driver->documents_submitted_at !== null,
                'verification_status' => $driver->verification_status,
                'is_verified' => $driver->is_verified,
                'rejection_reason' => $driver->rejection_reason,
                'submitted_at' => $driver->documents_submitted_at,
                'verified_at' => $driver->verified_at,
            ],
        ]);
    }

    /**
     * Get driver profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can access driver profile',
            ], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $driver,
        ]);
    }

    /**
     * Update driver location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can update location',
            ], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $driver->updateLocation($request->latitude, $request->longitude);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $driver,
        ]);
    }

    /**
     * Update driver status (available/busy/offline)
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:available,busy,offline',
        ]);

        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can update status',
            ], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        if (!$driver->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Driver must be verified before changing status',
            ], 403);
        }

        $driver->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $driver,
        ]);
    }
}
