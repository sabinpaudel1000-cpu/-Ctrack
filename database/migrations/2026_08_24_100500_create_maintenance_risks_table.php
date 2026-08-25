<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 1);
            $table->string('level', 16);
            $table->json('factors_json');
            $table->string('algorithm_version', 64);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['vehicle_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_risks');
    }
};
