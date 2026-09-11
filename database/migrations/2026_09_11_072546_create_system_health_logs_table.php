<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_logs', function (Blueprint $table) {
            $table->id();

            $table->string('check_type');
            $table->string('status');
            $table->string('message');

            $table->json('details')->nullable();

            $table->timestamp('checked_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_logs');
    }
};