<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BridgePropertyFinancialDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'gross_income', 'gross_scheduled_income',
        'net_operating_income', 'total_actual_rent', 'operating_expense',
        'operating_expense_includes', 'insurance_expense', 'maintenance_expense',
        'manager_expense', 'new_taxes_expense', 'other_expense',
        'supplies_expense', 'trash_expense'
    ];

    public function property()
    {
        return $this->belongsTo(BridgeProperty::class, 'property_id');
    }
}
