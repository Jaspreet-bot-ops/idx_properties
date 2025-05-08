<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_key', 'name', 'phone', 'fax',
        'email', 'website_url', 'mls_id'
    ];

    public function agents()
    {
        return $this->hasMany(BridgeAgent::class, 'office_id');
    }

    public function listProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'list_office_id');
    }

    public function coListProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'co_list_office_id');
    }

    public function buyerProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'buyer_office_id');
    }

    public function coBuyerProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'co_buyer_office_id');
    }
}
