<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'barangay_clearance',
                'certificate_of_residency',
                'certificate_of_indigency',
                'other',
            ]);
            $table->text('description')->nullable();
            $table->enum('status', ['submitted', 'reviewing', 'ready_for_pickup', 'completed'])->default('submitted');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
