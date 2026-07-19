<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('license_number')->unique();
            $table->string('vehicle_type'); // car, motorcycle, van
            $table->string('vehicle_make');
            $table->string('vehicle_model');
            $table->string('vehicle_year');
            $table->string('vehicle_color');
            $table->string('license_plate')->unique();
            $table->string('vehicle_photo')->nullable();
            $table->enum('status', ['available', 'busy', 'offline'])->default('offline');
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->integer('total_trips')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'current_lat', 'current_lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
