<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Pickup details
            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->string('pickup_terminal')->nullable();
            
            // Dropoff details
            $table->string('dropoff_address');
            $table->decimal('dropoff_lat', 10, 8);
            $table->decimal('dropoff_lng', 11, 8);
            $table->string('dropoff_terminal')->nullable();
            
            // Ride details
            $table->string('vehicle_type'); // car, motorcycle
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_duration_mins')->nullable();
            $table->decimal('base_fare', 8, 2);
            $table->decimal('distance_fare', 8, 2)->default(0);
            $table->decimal('time_fare', 8, 2)->default(0);
            $table->decimal('booking_fee', 8, 2)->default(0);
            $table->decimal('total_fare', 8, 2);
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'searching',
                'driver_assigned',
                'driver_arriving',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');
            
            // Timestamps
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('driver_assigned_at')->nullable();
            $table->timestamp('driver_arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['customer', 'driver', 'system'])->nullable();
            
            // Rating
            $table->integer('customer_rating')->nullable();
            $table->text('customer_review')->nullable();
            $table->integer('driver_rating')->nullable();
            $table->text('driver_review')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['customer_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('booking_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
