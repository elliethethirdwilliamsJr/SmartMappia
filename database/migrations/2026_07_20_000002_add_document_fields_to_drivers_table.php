<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // Document paths
            $table->string('national_id_photo')->nullable()->after('vehicle_photo');
            $table->string('driving_license_photo')->nullable()->after('national_id_photo');
            $table->string('vehicle_registration_photo')->nullable()->after('driving_license_photo');
            $table->string('vehicle_insurance_photo')->nullable()->after('vehicle_registration_photo');
            $table->string('profile_photo')->nullable()->after('vehicle_insurance_photo');
            $table->string('vehicle_plate_photo')->nullable()->after('profile_photo');
            
            // Verification status for each document
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_verified');
            $table->text('rejection_reason')->nullable()->after('verification_status');
            $table->timestamp('documents_submitted_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'national_id_photo',
                'driving_license_photo',
                'vehicle_registration_photo',
                'vehicle_insurance_photo',
                'profile_photo',
                'vehicle_plate_photo',
                'verification_status',
                'rejection_reason',
                'documents_submitted_at',
            ]);
        });
    }
};
