<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coaching_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->string('goal_type', 30);               // bulking, cutting, recomposition, endurance
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('initial_weight_kg', 5, 2);
            $table->decimal('target_weight_kg', 5, 2)->nullable();
            $table->string('activity_level', 20);          // sedentary ... very_active
            $table->text('medical_notes')->nullable();     // riwayat cedera dll
            $table->json('preferred_days')->nullable();
            $table->string('status', 20)->default('pending'); // pending, accepted, rejected, cancelled
            $table->text('reject_reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'status']);
            $table->index(['member_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_requests');
    }
};
