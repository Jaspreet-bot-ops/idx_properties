<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_key', 'listing_id', 'mls_status', 'standard_status', 'property_type', 'property_sub_type',

        // Basic property information
        'street_number', 'street_number_numeric', 'street_dir_prefix', 'street_name', 'street_suffix',
        'street_dir_suffix', 'unit_number', 'city', 'state_or_province', 'postal_code', 'postal_code_plus4',
        'county_or_parish', 'country', 'country_region', 'unparsed_address',

        // Listing details
        'list_price', 'original_list_price', 'close_price', 'days_on_market', 'listing_contract_date',
        'on_market_date', 'off_market_date', 'pending_timestamp', 'close_date', 'contract_status_change_date', 'listing_agreement',
        'contingency',

        // Property specifications
        'bedrooms_total', 'bathrooms_total_decimal', 'bathrooms_full', 'bathrooms_half', 'bathrooms_total_integer',
        'living_area', 'living_area_units', 'lot_size_square_feet', 'lot_size_acres', 'lot_size_units',
        'lot_size_dimensions', 'year_built', 'year_built_details', 'stories_total',

        // Parking information
        'garage_yn', 'attached_garage_yn', 'garage_spaces', 'carport_spaces', 'carport_yn',
        'open_parking_yn', 'covered_spaces', 'parking_total',

        // Pool/Spa information
        'pool_private_yn', 'spa_yn',

        // Financial information
        'tax_annual_amount', 'tax_year', 'tax_lot', 'parcel_number', 'association_fee', 'association_fee_frequency',

        // Geographic coordinates
        'latitude', 'longitude',

        // Virtual tour
        'virtual_tour_url_unbranded',

        // Public remarks
        'public_remarks', 'private_remarks', 'syndication_remarks',

        // Timestamps from API
        'original_entry_timestamp', 'modification_timestamp', 'price_change_timestamp',
        'status_change_timestamp', 'major_change_timestamp', 'photos_change_timestamp',
        'bridge_modification_timestamp',

        // Flags
        'new_construction_yn', 'furnished', 'waterfront_yn', 'view_yn', 'horse_yn',

        // Metadata
        'source_system_key', 'originating_system_key', 'originating_system_name', 'originating_system_id',

        // Relationships (foreign keys)
        'list_agent_id', 'co_list_agent_id', 'buyer_agent_id', 'co_buyer_agent_id',
        'list_office_id', 'co_list_office_id', 'buyer_office_id', 'co_buyer_office_id',
        'elementary_school_id', 'middle_school_id', 'high_school_id'
    ];

    // Relationships
    public function details()
    {
        return $this->hasOne(BridgePropertyDetail::class, 'property_id');
    }

    public function media()
    {
        return $this->hasMany(BridgePropertyMedia::class, 'property_id');
    }

    public function features()
    {
        return $this->belongsToMany(BridgeFeature::class, 'bridge_property_features', 'property_id', 'feature_id');
    }

    public function booleanFeatures()
    {
        return $this->hasMany(BridgePropertyBooleanFeature::class, 'property_id');
    }

    public function taxInformation()
    {
        return $this->hasOne(BridgePropertyTaxInformation::class, 'property_id');
    }

    public function financialDetails()
    {
        return $this->hasOne(PropertyFinancialDetail::class, 'property_id');
    }

    public function leaseInformation()
    {
        return $this->hasOne(BridgePropertyLeaseInformation::class, 'property_id');
    }

    public function listAgent()
    {
        return $this->belongsTo(BridgeAgent::class, 'list_agent_id');
    }

    public function coListAgent()
    {
        return $this->belongsTo(BridgeAgent::class, 'co_list_agent_id');
    }

    public function buyerAgent()
    {
        return $this->belongsTo(BridgeAgent::class, 'buyer_agent_id');
    }

    public function coBuyerAgent()
    {
        return $this->belongsTo(BridgeAgent::class, 'co_buyer_agent_id');
    }

    public function listOffice()
    {
        return $this->belongsTo(BridgeOffice::class, 'list_office_id');
    }

    public function coListOffice()
    {
        return $this->belongsTo(BridgeOffice::class, 'co_list_office_id');
    }

    public function buyerOffice()
    {
        return $this->belongsTo(BridgeOffice::class, 'buyer_office_id');
    }

    public function coBuyerOffice()
    {
        return $this->belongsTo(BridgeOffice::class, 'co_buyer_office_id');
    }

    public function schools()
    {
        return $this->hasOne(PropertySchool::class, 'property_id');
    }

    public function elementarySchool()
    {
        return $this->belongsTo(BridgeSchool::class, 'elementary_school_id');
    }

    public function middleSchool()
    {
        return $this->belongsTo(BridgeSchool::class, 'middle_school_id');
    }

    public function highSchool()
    {
        return $this->belongsTo(BridgeSchool::class, 'high_school_id');
    }
}
