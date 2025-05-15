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
        Schema::table('bridge_property_details', function (Blueprint $table) {
            $table->text('rooms_description')->nullable();
            $table->text('bedroom_description')->nullable();
            $table->text('master_bathroom_description')->nullable();
            $table->text('master_bath_features')->nullable();
            $table->text('dining_description')->nullable();
            $table->integer('rooms_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bridge_property_details', function (Blueprint $table) {
            $table->dropColumn([
                'rooms_description',
                'bedroom_description',
                'master_bathroom_description',
                'master_bath_features',
                'dining_description',
                'rooms_total'
            ]);
        });
    }
};
