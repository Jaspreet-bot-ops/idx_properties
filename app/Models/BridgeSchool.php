<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeSchool extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'district', 'city', 'state'];

    public function elementaryProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'elementary_school_id');
    }

    public function middleProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'middle_school_id');
    }

    public function highProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'high_school_id');
    }
}
