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
        Schema::table('bridge_properties', function (Blueprint $table) {
            $table->text('parking_features')->nullable()->after('parking_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bridge_properties', function (Blueprint $table) {
            $table->dropColumn('parking_features');
        });
    }
};
