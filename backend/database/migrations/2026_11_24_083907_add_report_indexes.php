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
        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_at');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('attendance_date');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn(Blueprint $table) => $table->dropIndex(['paid_at']));
        Schema::table('attendances', fn(Blueprint $table) => $table->dropIndex(['attendance_date']));
        Schema::table('memberships', fn(Blueprint $table) => $table->dropIndex(['status', 'end_date']));
    }
};
