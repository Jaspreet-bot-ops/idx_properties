<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyBooleanFeature extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'feature_name', 'value'];

    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
