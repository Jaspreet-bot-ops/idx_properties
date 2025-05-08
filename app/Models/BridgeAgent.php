<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgeAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_key', 'full_name', 'email', 'direct_phone',
        'office_phone', 'state_license', 'mls_id', 'office_id'
    ];

    public function office()
    {
        return $this->belongsTo(BridgeOffice::class, 'office_id');
    }

    public function listProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'list_agent_id');
    }

    public function coListProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'co_list_agent_id');
    }

    public function buyerProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'buyer_agent_id');
    }

    public function coBuyerProperties()
    {
        return $this->hasMany(BridgeProperty::class, 'co_buyer_agent_id');
    }
}
