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
        Schema::create('bridge_property_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('bridge_properties')->onDelete('cascade');
            
            // Additional property details
            $table->string('building_name')->nullable();
            $table->string('builder_model')->nullable();
            $table->string('buisness_name')->nullable();
            $table->string('buisness_type')->nullable();
            $table->decimal('building_area_total', 10, 2)->nullable();
            $table->string('building_area_units')->nullable();
            $table->string('building_area_source')->nullable();
            $table->string('common_walls')->nullable();
            $table->string('subdivision_name')->nullable()->index();
            $table->string('directions')->nullable();
            $table->string('direction_faces')->nullable();
            $table->string('property_condition')->nullable();
            $table->string('zoning')->nullable();
            $table->text('tax_legal_description')->nullable();
            $table->string('current_financing')->nullable();
            $table->string('possession')->nullable();
            $table->string('showing_instructions')->nullable();
            $table->string('showing_contact_type')->nullable();
            $table->string('availability_date')->nullable();
            $table->string('development_status')->nullable();
            $table->string('ownership_type')->nullable();
            $table->string('special_listing_conditions')->nullable();
            $table->string('listing_terms')->nullable();
            $table->string('listing_service')->nullable();
            $table->boolean('sign_on_property_yn')->nullable();
            $table->boolean('association_yn')->nullable();
            $table->string('disclosures')->nullable();
            $table->boolean('home_warranty_yn')->nullable();
            
            // MIAMIRE specific fields
            $table->decimal('miamire_adjusted_area_sf', 10, 2)->nullable();
            $table->decimal('miamire_lp_amt_sq_ft', 10, 2)->nullable();
            $table->decimal('miamire_ratio_current_price_by_sqft', 10, 2)->nullable();
            $table->string('miamire_area')->nullable();
            $table->string('miamire_style')->nullable();
            $table->string('miamire_internet_remarks')->nullable();
            $table->boolean('miamire_pool_yn')->nullable();
            $table->string('miamire_pool_dimensions')->nullable();
            $table->boolean('miamire_membership_purch_rqd_yn')->nullable();
            $table->boolean('miamire_special_assessment_yn')->nullable();
            $table->string('miamire_type_of_association')->nullable();
            $table->string('miamire_type_of_governing_bodies')->nullable();
            $table->string('miamire_restrictions')->nullable();
            $table->text('miamire_subdivision_information')->nullable();
            $table->string('miamire_buyer_country_of_residence')->nullable();
            $table->boolean('miamire_seller_contributions_yn')->nullable();
            $table->decimal('miamire_seller_contributions_amt', 10, 2)->nullable();
            $table->decimal('miamire_application_fee', 10, 2)->nullable();
            $table->text('miamire_approval_information')->nullable();
            $table->string('miamire_attribution_contact')->nullable();
            $table->string('miamire_buy_state')->nullable();
            $table->string('miamire_for_lease_mls_number')->nullable();
            $table->boolean('miamire_for_lease_yn')->nullable();
            $table->string('miamire_for_sale_mls_number')->nullable();
            $table->boolean('miamire_for_sale_yn')->nullable();
            $table->string('miamire_global_city')->nullable();
            $table->text('miamire_guest_house_description')->nullable();
            $table->string('miamire_length_of_rental')->nullable();
            $table->text('miamire_maintenance_includes')->nullable();
            $table->decimal('miamire_maximum_leasable_sqft', 10, 2)->nullable();
            $table->decimal('miamire_move_in_dollars', 10, 2)->nullable();
            $table->boolean('miamire_ok_to_advertise_list')->nullable();
            $table->decimal('miamire_pet_fee', 10, 2)->nullable();
            $table->text('miamire_pet_fee_desc')->nullable();
            $table->boolean('miamire_pets_allowed_yn')->nullable();
            $table->text('miamire_rent_length_desc')->nullable();
            $table->boolean('miamire_showing_time_flag')->nullable();
            $table->date('miamire_temp_off_market_date')->nullable();
            $table->decimal('miamire_total_move_in_dollars', 10, 2)->nullable();
            $table->string('miamire_type_of_business')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_property_details');
    }
};
