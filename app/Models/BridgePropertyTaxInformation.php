<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyTaxInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'tax_exemptions', 'public_survey_township',
        'public_survey_range', 'public_survey_section'
    ];

    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
