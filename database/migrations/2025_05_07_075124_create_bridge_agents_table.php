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
        Schema::create('bridge_agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_key')->unique()->nullable();
            $table->string('full_name')->index();
            $table->string('email')->nullable();
            $table->string('direct_phone')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('state_license')->nullable();
            $table->string('mls_id')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            // Change this line to reference bridge_offices instead of offices
            $table->foreign('office_id')->references('id')->on('bridge_offices')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_agents');
    }
};
