<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'media_key', 'media_url','mime_type','class_name','resource_name','media_category',
        'media_caption', 'short_description', 'order', 'is_primary'
    ];

    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
