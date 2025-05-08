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
        Schema::create('bridge_property_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('feature_id');
            
            // Change this line to reference bridge_features instead of features
            $table->foreign('property_id')->references('id')->on('bridge_properties')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('bridge_features')->onDelete('cascade');
            
            // Add a unique constraint to prevent duplicate entries
            $table->unique(['property_id', 'feature_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_property_features');
    }
};
