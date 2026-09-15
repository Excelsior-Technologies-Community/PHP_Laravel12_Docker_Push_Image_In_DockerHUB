<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('version')->default('v1.0.0');
            $table->string('commit_id', 40)->nullable();
            $table->string('commit_message')->nullable();
            $table->string('branch')->default('main');
            $table->string('environment')->default('production');
            $table->enum('status', ['success', 'failed', 'rolled_back', 'in_progress'])->default('success');
            $table->unsignedInteger('build_time_seconds')->default(0);
            $table->string('deployed_by')->default('CI/CD Pipeline');
            $table->longText('deployment_logs')->nullable();
            $table->boolean('is_current_active')->default(false);
            $table->unsignedBigInteger('rollback_from_id')->nullable();
            $table->timestamp('deployed_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'deployed_at']);
            $table->index('is_current_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_logs');
    }
};
