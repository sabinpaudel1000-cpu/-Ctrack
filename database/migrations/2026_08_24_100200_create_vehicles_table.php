<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 32)->unique();
            $table->string('make');
            $table->string('model');
            $table->string('vehicle_type', 32);
            $table->unsignedSmallInteger('manufacture_year');
            $table->unsignedInteger('mileage')->default(0);
            $table->string('status', 32)->default('active');
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->date('last_maintenance_date')->nullable();
            $table->timestamps();

            $table->unique('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
