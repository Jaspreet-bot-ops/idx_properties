<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyLeaseInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'lease_amount', 'lease_amount_frequency', 'lease_term',
        'lease_considered_yn', 'lease_assignable_yn', 'lease_renewal_option_yn',
        'existing_lease_type', 'land_lease_amount', 'land_lease_amount_frequency',
        'land_lease_yn', 'miamire_length_of_rental', 'miamire_for_lease_yn',
        'miamire_for_lease_mls_number', 'miamire_for_sale_yn', 'miamire_for_sale_mls_number',
        'miamire_move_in_dollars', 'miamire_total_move_in_dollars', 'miamire_pets_allowed_yn',
        'miamire_pet_fee', 'miamire_pet_fee_desc', 'miamire_application_fee',
        'miamire_rent_length_desc'
    ];

    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
