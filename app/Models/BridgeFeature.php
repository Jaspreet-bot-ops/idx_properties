<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeFeature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'feature_category_id'];

    public function category()
    {
        return $this->belongsTo(BridgeFeatureCategory::class, 'feature_category_id');
    }

    public function properties()
    {
        return $this->belongsToMany(BridgeProperty::class, 'bridge_property_features', 'feature_id', 'property_id');
    }
}
