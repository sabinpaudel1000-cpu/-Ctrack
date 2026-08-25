<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telematics_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('speed');
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->decimal('fuel_consumed_l', 8, 2)->default(0);
            $table->unsignedSmallInteger('engine_temperature');
            $table->boolean('harsh_acceleration')->default(false);
            $table->boolean('harsh_braking')->default(false);
            $table->boolean('speeding')->default(false);
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telematics_records');
    }
};
