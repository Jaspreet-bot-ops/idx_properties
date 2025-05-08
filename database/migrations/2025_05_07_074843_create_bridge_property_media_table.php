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
        Schema::create('bridge_property_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('bridge_properties')->onDelete('cascade');
            
            $table->string('media_key')->nullable();
            $table->string('media_url');
            $table->string('resource_record_key')->nullable();
            $table->string('resource_name')->nullable();
            $table->string('class_name')->nullable();
            $table->string('media_category')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('media_object_id')->nullable();
            $table->text('short_description')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('local_path')->nullable(); // For locally stored copies
            
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_property_media');
    }
};
