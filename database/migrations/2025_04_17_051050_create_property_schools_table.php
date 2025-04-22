<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('ElementarySchool')->nullable();
            $table->string('MiddleOrJuniorSchool')->nullable();
            $table->string('HighSchool')->nullable();
            $table->string('ElementarySchoolDistrict')->nullable();
            $table->string('MiddleOrJuniorSchoolDistrict')->nullable();
            $table->string('HighSchoolDistrict')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_schools');
    }
};
