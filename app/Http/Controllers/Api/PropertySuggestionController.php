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

    public function suggestion(Request $request)
    {
        // Get the search query
        $query = $request->input('q');
        
        // Get the property type filter (buy, rent, mortgage)
        $propertyType = $request->input('type');
        
        // Start building the query
        $propertyQuery = Property::query();
        
        // Apply type filter if provided
        if ($propertyType) {
            switch (strtolower($propertyType)) {
                case 'buy':
                    // Properties for sale
                    $propertyQuery->where('StandardStatus', 'Active');
                    break;
                    
                case 'rent':
                    // Properties for rent
                    $propertyQuery->where('StandardStatus', 'Active')
                        ->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                    
                // case 'mortgage':
                //     // Properties eligible for mortgage
                //     // You might need to adjust this based on your specific criteria for mortgage properties
                //     $propertyQuery->where('StandardStatus', 'Active')
                //                   ->whereIn('PropertyType', ['Residential', 'Condo', 'SingleFamilyResidence', 'Townhouse'])
                //                   ->whereHas('financialDetails', function($q) {
                //                       $q->where('FinancingProposed', 'Conventional');
                //                   });
                //     break;
            }
        }
        
        // Apply search query if provided
        if ($query) {
            $propertyQuery->where(function($q) use ($query) {
                $q->where('UnparsedAddress', 'like', "%{$query}%")
                  ->orWhere('City', 'like', "%{$query}%")
                  ->orWhere('StateOrProvince', 'like', "%{$query}%")
                  ->orWhere('PostalCode', 'like', "%{$query}%");
            });
        }
        
        // Limit the results and select only the necessary fields
        $properties = $propertyQuery->select([
            'id',
            'ListingKey',
            'UnparsedAddress',
            'City',
            'StateOrProvince',
            'PostalCode',
            'PropertyType',
            'StandardStatus',
            'ListPrice'
        ])
        ->limit(10)
        ->get();
        
        // Format the response
        $suggestions = $properties->map(function($property) {
            return [
                'id' => $property->id,
                'listing_key' => $property->ListingKey,
                'address' => $property->UnparsedAddress,
                'city' => $property->City,
                'state' => $property->StateOrProvince,
                'postal_code' => $property->PostalCode,
                'full_address' => "{$property->UnparsedAddress}, {$property->City}, {$property->StateOrProvince} {$property->PostalCode}",
                'property_type' => $property->PropertyType,
                'status' => $property->StandardStatus,
                'price' => $property->ListPrice
            ];
        });
        
        return response()->json($suggestions);
    }
}
