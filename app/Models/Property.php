<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // Relationships
    public function details()
    {
        return $this->hasOne(PropertyDetail::class);
    }

    public function amenities()
    {
        return $this->hasOne(PropertyAmenity::class);
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class);
    }

    public function schools()
    {
        return $this->hasOne(PropertySchool::class);
    }

    public function financialDetails()
    {
        return $this->hasOne(PropertyFinancialDetail::class);
    }
}
