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
        Schema::create('bridge_properties', function (Blueprint $table) {
            $table->id();
            $table->string('listing_key')->unique()->index();
            $table->string('listing_id')->nullable()->index();
            $table->string('mls_status')->nullable()->index();
            $table->string('standard_status')->nullable()->index();
            $table->string('property_type')->nullable()->index();
            $table->string('property_sub_type')->nullable()->index();
            
            // Basic property information
            $table->string('street_number')->nullable();
            $table->integer('street_number_numeric')->nullable();
            $table->string('street_dir_prefix')->nullable();
            $table->string('street_name')->nullable();
            $table->string('street_suffix')->nullable();
            $table->string('street_dir_suffix')->nullable();
            $table->string('unit_number')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state_or_province')->nullable()->index();
            $table->string('postal_code')->nullable()->index();
            $table->string('postal_code_plus4')->nullable();
            $table->string('county_or_parish')->nullable()->index();
            $table->string('country')->nullable();
            $table->string('country_region')->nullable();
            $table->string('unparsed_address')->nullable();
            
            // Listing details
            $table->decimal('list_price', 12, 2)->nullable()->index();
            $table->decimal('original_list_price', 12, 2)->nullable();
            $table->decimal('close_price', 12, 2)->nullable();
            $table->integer('days_on_market')->nullable();
            $table->string('listing_contract_date')->nullable();
            $table->string('on_market_date')->nullable()->index();
            $table->string('off_market_date')->nullable();
            $table->string('pending_timestamp')->nullable();
            $table->string('close_date')->nullable()->index();
            $table->string('contract_status_change_date')->nullable()->index();
            $table->string('listing_agreement')->nullable();
            $table->string('contingency')->nullable();
            
            // Property specifications
            $table->integer('bedrooms_total')->nullable()->index();
            $table->decimal('bathrooms_total_decimal', 4, 2)->nullable()->index();
            $table->integer('bathrooms_full')->nullable();
            $table->integer('bathrooms_half')->nullable();
            $table->integer('bathrooms_total_integer')->nullable();
            $table->decimal('living_area', 10, 2)->nullable()->index();
            $table->string('living_area_units')->nullable();
            $table->decimal('lot_size_square_feet', 12, 2)->nullable();
            $table->decimal('lot_size_acres', 10, 4)->nullable();
            $table->string('lot_size_units')->nullable();
            $table->string('lot_size_dimensions')->nullable();
            $table->integer('year_built')->nullable()->index();
            $table->string('year_built_details')->nullable();
            $table->integer('stories_total')->nullable();
            
            // Parking information
            $table->boolean('garage_yn')->nullable();
            $table->boolean('attached_garage_yn')->nullable();
            $table->integer('garage_spaces')->nullable();
            $table->integer('carport_spaces')->nullable();
            $table->boolean('carport_yn')->nullable();
            $table->boolean('open_parking_yn')->nullable();
            $table->integer('covered_spaces')->nullable();
            $table->integer('parking_total')->nullable();
            
            // Pool/Spa information
            $table->boolean('pool_private_yn')->nullable()->index();
            $table->boolean('spa_yn')->nullable();
            
            // Financial information
            $table->decimal('tax_annual_amount', 12, 2)->nullable();
            $table->integer('tax_year')->nullable();
            $table->string('tax_lot')->nullable();
            $table->string('parcel_number')->nullable()->index();
            $table->decimal('association_fee', 10, 2)->nullable();
            $table->string('association_fee_frequency')->nullable();
            
            // Geographic coordinates
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Virtual tour
            $table->text('virtual_tour_url_unbranded')->nullable();
            
            // Public remarks
            $table->text('public_remarks')->nullable();
            $table->text('private_remarks')->nullable();
            $table->text('syndication_remarks')->nullable();
            
            // Timestamps from API
            $table->string('original_entry_timestamp')->nullable();
            $table->string('modification_timestamp')->nullable();
            $table->string('price_change_timestamp')->nullable();
            $table->string('status_change_timestamp')->nullable();
            $table->string('major_change_timestamp')->nullable();
            $table->string('photos_change_timestamp')->nullable();
            $table->string('bridge_modification_timestamp')->nullable();
            
            // Flags
            $table->boolean('new_construction_yn')->nullable();
            $table->string('furnished')->nullable();
            $table->boolean('waterfront_yn')->nullable()->index();
            $table->boolean('view_yn')->nullable();
            $table->boolean('horse_yn')->nullable();
            
            // Metadata
            $table->string('source_system_key')->nullable();
            $table->string('originating_system_key')->nullable();
            $table->string('originating_system_name')->nullable();
            $table->string('originating_system_id')->nullable();
            
            // Relationships (foreign keys will be added later)
            $table->unsignedBigInteger('list_agent_id')->nullable()->index();
            $table->unsignedBigInteger('co_list_agent_id')->nullable();
            $table->unsignedBigInteger('buyer_agent_id')->nullable();
            $table->unsignedBigInteger('co_buyer_agent_id')->nullable();
            $table->unsignedBigInteger('list_office_id')->nullable()->index();
            $table->unsignedBigInteger('co_list_office_id')->nullable();
            $table->unsignedBigInteger('buyer_office_id')->nullable();
            $table->unsignedBigInteger('co_buyer_office_id')->nullable();
            $table->unsignedBigInteger('elementary_school_id')->nullable();
            $table->unsignedBigInteger('middle_school_id')->nullable();
            $table->unsignedBigInteger('high_school_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_properties');
    }
};
