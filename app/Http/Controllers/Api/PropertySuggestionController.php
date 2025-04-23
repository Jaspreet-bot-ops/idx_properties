<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertySuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }
        
        // Search for properties that match the query in various fields
        $properties = Property::where(function($q) use ($query) {
                $q->where('UnparsedAddress', 'like', "%{$query}%")
                  ->orWhere('City', 'like', "%{$query}%")
                  ->orWhere('StateOrProvince', 'like', "%{$query}%")
                  ->orWhere('PostalCode', 'like', "%{$query}%");
            })
            ->select('id', 'UnparsedAddress', 'City', 'StateOrProvince', 'PostalCode')
            ->limit(5)
            ->get();
            
        return response()->json($properties);
    }
}
