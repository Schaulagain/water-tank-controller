<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->float('roof')->nullable();
            $table->float('underground')->nullable();
            $table->boolean('pump');
            $table->timestamp('recorded_at')->index();
        });

        Schema::create('pump_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('start_reason')->nullable();
            $table->string('stop_reason')->nullable();
            $table->string('mode');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pump_runs');
        Schema::dropIfExists('readings');
    }
};
