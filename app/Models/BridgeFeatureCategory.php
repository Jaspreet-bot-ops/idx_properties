<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeFeatureCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function features()
    {
        return $this->hasMany(BridgeFeature::class, 'feature_category_id');
    }
}
