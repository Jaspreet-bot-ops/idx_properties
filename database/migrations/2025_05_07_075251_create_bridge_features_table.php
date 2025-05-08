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
        Schema::create('bridge_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('feature_category_id')->nullable();
            $table->foreign('feature_category_id')->references('id')->on('bridge_feature_categories')->onDelete('set null');
            $table->timestamps();
            
            // Add a unique constraint for name + category
            $table->unique(['name', 'feature_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_features');
    }
};
