<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get airport terminals
     */
    public function terminals()
    {
        // For now, return hardcoded terminals
        // In production, fetch from database
        $terminals = [
            [
                'id' => 1,
                'name' => 'Terminal 1',
                'code' => 'T1',
                'airport' => 'King Khalid International Airport',
                'latitude' => 24.9577,
                'longitude' => 46.6988,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Terminal 2',
                'code' => 'T2',
                'airport' => 'King Khalid International Airport',
                'latitude' => 24.9587,
                'longitude' => 46.6998,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Terminal 3',
                'code' => 'T3',
                'airport' => 'King Khalid International Airport',
                'latitude' => 24.9597,
                'longitude' => 46.7008,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Terminal 4',
                'code' => 'T4',
                'airport' => 'King Khalid International Airport',
                'latitude' => 24.9607,
                'longitude' => 46.7018,
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'Terminal 5',
                'code' => 'T5',
                'airport' => 'King Khalid International Airport',
                'latitude' => 24.9617,
                'longitude' => 46.7028,
                'is_active' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $terminals,
        ]);
    }

    /**
     * Get city districts
     */
    public function districts()
    {
        // For now, return hardcoded districts
        // In production, fetch from database
        $districts = [
            [
                'id' => 1,
                'name' => 'Olaya District',
                'city' => 'Riyadh',
                'latitude' => 24.6977,
                'longitude' => 46.6858,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Al Malaz',
                'city' => 'Riyadh',
                'latitude' => 24.6877,
                'longitude' => 46.7258,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Al Naseem',
                'city' => 'Riyadh',
                'latitude' => 24.7377,
                'longitude' => 46.6758,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Diplomatic Quarter',
                'city' => 'Riyadh',
                'latitude' => 24.6577,
                'longitude' => 46.6158,
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'King Abdullah Financial District',
                'city' => 'Riyadh',
                'latitude' => 24.7677,
                'longitude' => 46.6458,
                'is_active' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }
}
