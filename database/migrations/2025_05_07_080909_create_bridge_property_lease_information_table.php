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
        Schema::create('bridge_property_lease_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('bridge_properties')->onDelete('cascade');
            
            $table->decimal('lease_amount', 12, 2)->nullable();
            $table->string('lease_amount_frequency')->nullable();
            $table->string('lease_term')->nullable();
            $table->boolean('lease_considered_yn')->nullable();
            $table->boolean('lease_assignable_yn')->nullable();
            $table->boolean('lease_renewal_option_yn')->nullable();
            $table->string('existing_lease_type')->nullable();
            $table->decimal('land_lease_amount', 12, 2)->nullable();
            $table->string('land_lease_amount_frequency')->nullable();
            $table->boolean('land_lease_yn')->nullable();
            $table->string('miamire_length_of_rental')->nullable();
            $table->boolean('miamire_for_lease_yn')->nullable();
            $table->string('miamire_for_lease_mls_number')->nullable();
            $table->boolean('miamire_for_sale_yn')->nullable();
            $table->string('miamire_for_sale_mls_number')->nullable();
            $table->decimal('miamire_move_in_dollars', 12, 2)->nullable();
            $table->decimal('miamire_total_move_in_dollars', 12, 2)->nullable();
            $table->boolean('miamire_pets_allowed_yn')->nullable();
            $table->string('miamire_pet_fee')->nullable();
            $table->string('miamire_pet_fee_desc')->nullable();
            $table->string('miamire_application_fee')->nullable();
            $table->string('miamire_rent_length_desc')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_property_lease_information');
    }
};
