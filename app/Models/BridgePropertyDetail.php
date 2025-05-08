<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'building_name',
        'builder_model',
        'buisness_name',
        'buisness_type',
        'subdivision_name',
        'building_area_total',
        'building_area_units',
        'building_area_source',
        'common_walls',
        'directions',
        'direction_faces',
        'property_condition',
        'zoning',
        'tax_legal_description',
        'current_financing',
        'possession',
        'showing_instructions',
        'showing_contact_type',
        'availability_date',
        'development_status',
        'ownership_type',
        'special_listing_conditions',
        'listing_terms',
        'listing_service',
        'sign_on_property_yn',
        'association_yn',
        'disclosures',
        'home_warranty_yn',
        
        // MIAMIRE specific fields
        'miamire_adjusted_area_sf',
        'miamire_lp_amt_sq_ft',
        'miamire_ratio_current_price_by_sqft',
        'miamire_area',
        'miamire_style',
        'miamire_internet_remarks',
        'miamire_pool_yn',
        'miamire_pool_dimensions',
        'miamire_membership_purch_rqd_yn',
        'miamire_special_assessment_yn',
        'miamire_type_of_association',
        'miamire_type_of_governing_bodies',
        'miamire_restrictions',
        'miamire_subdivision_information',
        'miamire_buyer_country_of_residence',
        'miamire_seller_contributions_yn',
        'miamire_seller_contributions_amt',
        
        // Additional MIAMIRE fields
        'miamire_application_fee',
        'miamire_approval_information',
        'miamire_attribution_contact',
        'miamire_buy_state',
        'miamire_for_lease_mls_number',
        'miamire_for_lease_yn',
        'miamire_for_sale_mls_number',
        'miamire_for_sale_yn',
        'miamire_global_city',
        'miamire_guest_house_description',
        'miamire_length_of_rental',
        'miamire_maintenance_includes',
        'miamire_maximum_leasable_sqft',
        'miamire_move_in_dollars',
        'miamire_ok_to_advertise_list',
        'miamire_pet_fee',
        'miamire_pet_fee_desc',
        'miamire_pets_allowed_yn',
        'miamire_rent_length_desc',
        'miamire_showing_time_flag',
        'miamire_temp_off_market_date',
        'miamire_total_move_in_dollars',
        'miamire_type_of_business'
    ];

    /**
     * Cast certain attributes to their native types
     */
    protected $casts = [
        'building_area_total' => 'float',
        'miamire_adjusted_area_sf' => 'float',
        'miamire_lp_amt_sq_ft' => 'float',
        'miamire_ratio_current_price_by_sqft' => 'float',
        'miamire_seller_contributions_amt' => 'float',
        'sign_on_property_yn' => 'boolean',
        'association_yn' => 'boolean',
        'home_warranty_yn' => 'boolean',
        'miamire_pool_yn' => 'boolean',
        'miamire_membership_purch_rqd_yn' => 'boolean',
        'miamire_special_assessment_yn' => 'boolean',
        'miamire_seller_contributions_yn' => 'boolean',
        // Additional boolean casts
        'miamire_for_lease_yn' => 'boolean',
        'miamire_for_sale_yn' => 'boolean',
        'miamire_ok_to_advertise_list' => 'boolean',
        'miamire_pets_allowed_yn' => 'boolean',
        'miamire_showing_time_flag' => 'boolean',
        // Additional float casts
        'miamire_application_fee' => 'float',
        'miamire_maximum_leasable_sqft' => 'float',
        'miamire_move_in_dollars' => 'float',
        'miamire_pet_fee' => 'float',
        'miamire_total_move_in_dollars' => 'float',
        // Date casts
        'miamire_temp_off_market_date' => 'date'
    ];

    /**
     * Get the property that owns the details.
     */
    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
