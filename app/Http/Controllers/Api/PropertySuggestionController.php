<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BridgeProperty;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PropertySuggestionController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = $request->input('q');

    //     if (empty($query) || strlen($query) < 1) {
    //         return response()->json([]);
    //     }

    //     // Search for properties that match the query in various fields
    //     $properties = Property::where(function ($q) use ($query) {
    //         $q->where('UnparsedAddress', 'like', "%{$query}%")
    //             ->orWhere('City', 'like', "%{$query}%")
    //             ->orWhere('StateOrProvince', 'like', "%{$query}%")
    //             ->orWhere('PostalCode', 'like', "%{$query}%");
    //     })
    //         ->select('id', 'UnparsedAddress', 'City', 'StateOrProvince', 'PostalCode')
    //         ->limit(5)
    //         ->get();

    //     return response()->json($properties);
    // }

    // public function index(Request $request)
    // {
    //     $query = $request->input('q');

    //     if (empty($query) || strlen($query) < 1) {
    //         return response()->json([]);
    //     }

    //     // Search for properties that match the query in various fields
    //     $properties = BridgeProperty::where(function ($q) use ($query) {
    //         $q->where('unparsed_address', 'like', "%{$query}%")
    //             ->orWhere('city', 'like', "%{$query}%")
    //             ->orWhere('state_or_province', 'like', "%{$query}%")
    //             ->orWhere('postal_code', 'like', "%{$query}%");
    //     })
    //         ->select('id', 'unparsed_address', 'city', 'state_or_province', 'postal_code')
    //         ->limit(5)
    //         ->get();

    //     return response()->json($properties);
    // }


    public function index(Request $request)
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 1) {
            return response()->json([]);
        }

        // Search for properties that match the query in various fields
        $properties = BridgeProperty::where(function ($q) use ($query) {
            $q->where('unparsed_address', 'like', "%{$query}%")
                ->orWhere('street_number', 'like', "%{$query}%")
                ->orWhere('street_name', 'like', "%{$query}%")
                ->orWhere('city', 'like', "%{$query}%")
                ->orWhere('state_or_province', 'like', "%{$query}%")
                ->orWhere('postal_code', 'like', "%{$query}%");
        })
            ->select('id', 'unparsed_address', 'street_number', 'street_name', 'city', 'state_or_province', 'postal_code')
            ->limit(10)
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'label' => "{$property->street_number} {$property->street_name}, {$property->city}, {$property->state_or_province} {$property->postal_code}",
                    'unparsed_address' => $property->unparsed_address,
                    'street_number' => $property->street_number,
                    'street_name' => $property->street_name,
                    'city' => $property->city,
                    'state_or_province' => $property->state_or_province,
                    'postal_code' => $property->postal_code,
                    'type' => 'property'
                ];
            });

        // Also search for unique cities
        $cities = BridgeProperty::where('city', 'like', "%{$query}%")
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city', 'state_or_province')
            ->distinct()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => null,
                    'label' => "{$item->city}, {$item->state_or_province}",
                    'city' => $item->city,
                    'state_or_province' => $item->state_or_province,
                    'type' => 'city'
                ];
            });

        // Search for states
        $states = BridgeProperty::where('state_or_province', 'like', "%{$query}%")
            ->whereNotNull('state_or_province')
            ->where('state_or_province', '!=', '')
            ->select('state_or_province')
            ->distinct()
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => null,
                    'label' => $item->state_or_province,
                    'state_or_province' => $item->state_or_province,
                    'type' => 'state'
                ];
            });

        // Search for postal codes
        $postalCodes = BridgeProperty::where('postal_code', 'like', "%{$query}%")
            ->whereNotNull('postal_code')
            ->where('postal_code', '!=', '')
            ->select('postal_code', 'state_or_province')
            ->distinct()
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => null,
                    'label' => "{$item->postal_code}, {$item->state_or_province}",
                    'postal_code' => $item->postal_code,
                    'state_or_province' => $item->state_or_province,
                    'type' => 'postal_code'
                ];
            });

        // Combine all results
        $results = $properties->concat($cities)->concat($states)->concat($postalCodes);

        return response()->json($results);
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
            }
        }

        // Apply search query if provided
        if ($query) {
            $propertyQuery->where(function ($q) use ($query) {
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
        $suggestions = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'listing_key' => $property->ListingKey,
                'address' => $property->UnparsedAddress,
                'city' => $property->City,
                'state' => $property->StateOrProvince,
                'postal_code' => $property->PostalCode,
                'full_address' => "{$property->UnparsedAddress}, {$property->City}, {$property->StateOrProvince}",
                'property_type' => $property->PropertyType,
                'status' => $property->StandardStatus,
                'price' => $property->ListPrice
            ];
        });

        return response()->json($suggestions);
    }

    // Format helper function
    function formatProperty($property)
    {
        return [
            'id' => $property->id,
            'listing_key' => $property->ListingKey,
            'address' => $property->UnparsedAddress,
            'city' => $property->City,
            'state' => $property->StateOrProvince,
            'postal_code' => $property->PostalCode,
            'full_address' => "{$property->UnparsedAddress}, {$property->City}, {$property->StateOrProvince}",
            'property_type' => $property->PropertyType,
            'status' => $property->StandardStatus,
            'price' => $property->ListPrice
        ];
    }

    public function search(Request $request)
    {
        // Validate search parameters
        $request->validate([
            'query' => 'nullable|string',
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = $request->input('query', '');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        // Define property types that are typically individual properties (not buildings)
        $individualPropertyTypes = [
            'Land',
            'SingleFamilyResidence',
            'Business',
            'BusinessOpportunity',
            'UnimprovedLand',
            'Special Purpose'
        ];

        // Base query with common conditions
        $baseQuery = Property::with(['details', 'media'])
            ->where('StandardStatus', 'Active');

        // Apply search query if provided
        if (!empty($query)) {
            $baseQuery->where(function ($q) use ($query) {
                $q->where('UnparsedAddress', 'like', "%{$query}%")
                    ->orWhere('StreetNumber', 'like', "%{$query}%")
                    ->orWhere('StreetName', 'like', "%{$query}%")
                    ->orWhere('City', 'like', "%{$query}%")
                    ->orWhere('StateOrProvince', 'like', "%{$query}%")
                    ->orWhere('PostalCode', 'like', "%{$query}%")
                    ->orWhere('PropertySubType', 'like', "%{$query}%")
                    ->orWhere('BuildingName', 'like', "%{$query}%");
            });
        }

        // Query for buildings - using a simpler approach to avoid SQL errors
        $buildingsQuery = clone $baseQuery;
        $buildingsQuery->select(
            'properties.*',
            DB::raw('COUNT(*) as unit_count'),
            DB::raw('MIN(ListPrice) as min_price'),
            DB::raw('MAX(ListPrice) as max_price')
        )
            ->whereNotNull('StreetNumber')
            ->whereNotNull('StreetName')
            ->whereNotIn('PropertySubType', $individualPropertyTypes)
            ->groupBy('StreetNumber', 'StreetName')
            ->havingRaw('COUNT(*) > 1'); // Buildings have multiple units with same address

        // Query for individual properties
        $propertiesQuery = clone $baseQuery;

        // Get all street addresses that have only one property
        $singlePropertyAddresses = DB::table('properties')
            ->select('StreetNumber', 'StreetName')
            ->whereNotNull('StreetNumber')
            ->whereNotNull('StreetName')
            ->groupBy('StreetNumber', 'StreetName')
            ->havingRaw('COUNT(*) = 1')
            ->get();

        // Extract the street numbers and names
        $singleAddressStreetNumbers = $singlePropertyAddresses->pluck('StreetNumber')->toArray();
        $singleAddressStreetNames = $singlePropertyAddresses->pluck('StreetName')->toArray();

        $propertiesQuery->where(function ($q) use ($individualPropertyTypes, $singleAddressStreetNumbers, $singleAddressStreetNames) {
            // Either it's an individual property type
            $q->whereIn('PropertySubType', $individualPropertyTypes);

            // Or it's a property at an address that has only one property
            if (!empty($singleAddressStreetNumbers) && !empty($singleAddressStreetNames)) {
                $q->orWhere(function ($subQ) use ($singleAddressStreetNumbers, $singleAddressStreetNames) {
                    $subQ->whereIn('StreetNumber', $singleAddressStreetNumbers)
                        ->whereIn('StreetName', $singleAddressStreetNames);
                });
            }
        });

        // Count totals - using simpler approach
        $totalBuildings = $buildingsQuery->getQuery()->getCountForPagination();
        $totalProperties = $propertiesQuery->count();

        // Split the limit between buildings and properties
        $buildingsLimit = ceil($limit / 2);
        $propertiesLimit = $limit - $buildingsLimit;

        // Get paginated results
        $buildings = $buildingsQuery->skip($offset)->take($buildingsLimit)->get();
        $properties = $propertiesQuery->skip($offset)->take($propertiesLimit)->get();

        // Format the response
        return response()->json([
            'buildings' => $buildings->map(function ($building) {
                // Add a formatted address for display
                $building->formatted_address = trim($building->StreetNumber . ' ' . $building->StreetName);
                return $building;
            }),
            'properties' => $properties,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total_buildings' => $totalBuildings,
                'total_properties' => $totalProperties,
                'total' => $totalBuildings + $totalProperties,
                'has_more' => ($offset + $limit) < ($totalBuildings + $totalProperties)
            ]
        ]);
    }

    private function getFullStateName($abbr)
    {
        $states = [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
            'PR' => 'Puerto Rico',
            'VI' => 'Virgin Islands',
            'GU' => 'Guam',
            'AS' => 'American Samoa',
            'MP' => 'Northern Mariana Islands',
        ];

        return $states[strtoupper($abbr)] ?? null;
    }


    // public function autocomplete(Request $request)
    // {
    //     // Get search parameters - support both 'q' and 'query' parameters
    //     $query = $request->input('q') ?? $request->input('query');
    //     $type = $request->input('type'); // 'buy' or 'rent'
    //     $limit = $request->input('limit', 15); // Default 15 suggestions total

    //     // Validate query parameter
    //     if (empty($query) || strlen($query) < 0) {
    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => []
    //             ]
    //         ]);
    //     }

    //     // Allocate limits for each category
    //     $addressLimit = ceil($limit * 0.4); // 40% for addresses
    //     $buildingLimit = ceil($limit * 0.3); // 30% for buildings
    //     $placeLimit = ceil($limit * 0.3); // 30% for places

    //     // Define property types that are typically individual properties
    //     $individualPropertyTypes = [
    //         'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
    //         'UnimprovedLand', 'Special Purpose'
    //     ];

    //     // Base query with common conditions
    //     $baseQuery = Property::query()->where('StandardStatus', 'Active');

    //     // Apply type filter if provided
    //     if ($type) {
    //         switch (strtolower($type)) {
    //             case 'buy':
    //                 // Properties for sale
    //                 $baseQuery->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;

    //             case 'rent':
    //                 // Properties for rent
    //                 $baseQuery->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //         }
    //     }

    //     // 1. ADDRESS SUGGESTIONS - Individual property addresses
    //     $addressQuery = clone $baseQuery;
    //     $addressSuggestions = $addressQuery->select(
    //         'id',
    //         'ListingKey',
    //         'StreetNumber',
    //         'StreetName',
    //         'UnitNumber',
    //         'City',
    //         'StateOrProvince',
    //         'PostalCode',
    //         'PropertySubType',
    //         'ListPrice'
    //     )
    //         ->where(function ($q) use ($query) {
    //             // Prioritize exact matches first
    //             $q->where('UnparsedAddress', 'like', "{$query}%")
    //                 ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "{$query}%")
    //                 // Then try partial matches
    //                 ->orWhere('UnparsedAddress', 'like', "%{$query}%")
    //                 ->orWhere('StreetNumber', 'like', "{$query}%")
    //                 ->orWhere('StreetName', 'like', "%{$query}%")
    //                 ->orWhere('City', 'like', "%{$query}%")
    //                 ->orWhere('StateOrProvince', 'like', "%{$query}%");
    //         })
    //         ->orderByRaw("CASE 
    //         WHEN UnparsedAddress LIKE '{$query}%' THEN 1
    //         WHEN CONCAT(StreetNumber, ' ', StreetName) LIKE '{$query}%' THEN 2
    //         ELSE 3
    //     END")
    //         ->limit($addressLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             $address = trim($item->StreetNumber . ' ' . $item->StreetName);
    //             if (!empty($item->UnitNumber)) {
    //                 $address .= ' #' . $item->UnitNumber;
    //             }

    //             return [
    //                 'type' => 'address',
    //                 'id' => $item->id,
    //                 'listing_key' => $item->ListingKey,
    //                 'address' => $address,
    //                 'city' => $item->City,
    //                 'state' => $item->StateOrProvince,
    //                 'postal_code' => $item->PostalCode,
    //                 'property_type' => $item->PropertySubType,
    //                 'price' => $item->ListPrice,
    //                 'display_text' => $address .
    //                     ($item->City ? ', ' . $item->City : '') .
    //                     ($item->StateOrProvince ? ', ' . $item->StateOrProvince : ''),
    //                 'action_url' => "/api/properties/{$item->id}"
    //             ];
    //         });

    //     // 2. BUILDING SUGGESTIONS - Multi-unit buildings
    //     $buildingQuery = DB::table('properties')
    //         ->select(
    //             'StreetNumber',
    //             'StreetName',
    //             'StreetDirPrefix',
    //             'City',
    //             'StateOrProvince',
    //             'PostalCode',
    //             'BuildingName',
    //             DB::raw('COUNT(*) as unit_count'),
    //             DB::raw('MIN(ListPrice) as min_price'),
    //             DB::raw('MAX(ListPrice) as max_price')
    //         )
    //         ->where('StandardStatus', 'Active')
    //         ->whereNotNull('StreetNumber')
    //         ->whereNotNull('StreetName')
    //         ->whereNotIn('PropertySubType', $individualPropertyTypes);

    //     // Apply type filter to buildings
    //     if ($type) {
    //         switch (strtolower($type)) {
    //             case 'buy':
    //                 $buildingQuery->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //             case 'rent':
    //                 $buildingQuery->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //         }
    //     }

    //     $buildingSuggestions = $buildingQuery->where(function ($q) use ($query) {
    //         // Prioritize exact matches first
    //         $q->where(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "{$query}%")
    //             ->orWhere('BuildingName', 'like', "{$query}%")
    //             // Then try partial matches
    //             ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$query}%")
    //             ->orWhere('BuildingName', 'like', "%{$query}%")
    //             ->orWhere('StreetNumber', 'like', "{$query}%")
    //             ->orWhere('StreetName', 'like', "%{$query}%")
    //             ->orWhere('City', 'like', "%{$query}%");
    //     })
    //         ->groupBy('StreetNumber', 'StreetName', 'StreetDirPrefix', 'City', 'StateOrProvince', 'PostalCode', 'BuildingName')
    //         ->havingRaw('COUNT(*) > 1')
    //         ->orderByRaw("CASE 
    //         WHEN CONCAT(StreetNumber, ' ', StreetName) LIKE '{$query}%' THEN 1
    //         WHEN BuildingName LIKE '{$query}%' THEN 2
    //         ELSE 3
    //     END")
    //         ->limit($buildingLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             $address = trim($item->StreetNumber . ' ' .
    //                 ($item->StreetDirPrefix ? $item->StreetDirPrefix . ' ' : '') .
    //                 $item->StreetName);
    //             $buildingName = !empty($item->BuildingName) ? $item->BuildingName : $address;

    //             return [
    //                 'type' => 'building',
    //                 'building_name' => $buildingName,
    //                 'street_number' => $item->StreetNumber,
    //                 'street_dir_prefix' => $item->StreetDirPrefix,
    //                 'street_name' => $item->StreetName,
    //                 'address' => trim($item->StreetNumber . ' ' . $item->StreetName),
    //                 'city' => $item->City,
    //                 'state' => $item->StateOrProvince,
    //                 'postal_code' => $item->PostalCode,
    //                 'unit_count' => $item->unit_count,
    //                 'min_price' => $item->min_price,
    //                 'max_price' => $item->max_price,
    //                 'display_text' => $buildingName .
    //                     ($item->City ? ', ' . $item->City : '') .
    //                     ($item->StateOrProvince ? ', ' . $item->StateOrProvince : '') .
    //                     ' (' . $item->unit_count . ' units)',
    //                 'action_url' => "/api/buildings?street_number={$item->StreetNumber}&street_name=" . urlencode($item->StreetName)
    //             ];
    //         });

    //     // 3. PLACE SUGGESTIONS - Cities and States
    //     // First get unique cities
    //     $citySuggestions = DB::table('properties')
    //         ->select('City', 'StateOrProvince')
    //         ->where('City', 'like', "%{$query}%")
    //         ->whereNotNull('City')
    //         ->where('City', '!=', '')
    //         ->where('City', '!=', ',') // Exclude invalid city names
    //         ->groupBy('City', 'StateOrProvince')
    //         ->orderByRaw("CASE WHEN City LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'city',
    //                 'name' => $item->City,
    //                 'state' => $item->StateOrProvince,
    //                 'display_text' => $item->City . ($item->StateOrProvince ? ', ' . $item->StateOrProvince : ''),
    //                 'action_url' => "/api/properties/search?city=" . urlencode($item->City)
    //             ];
    //         });

    //     $stateSuggestions = DB::table('properties')
    //         ->select('StateOrProvince')
    //         ->where('StateOrProvince', 'like', "%{$query}%")
    //         ->whereNotNull('StateOrProvince')
    //         ->where('StateOrProvince', '!=', '')
    //         ->groupBy('StateOrProvince')
    //         ->orderByRaw("CASE WHEN StateOrProvince LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             $fullStateName = $this->getFullStateName($item->StateOrProvince);
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'state',
    //                 'name' => $item->StateOrProvince,
    //                 'full_name' => $fullStateName,
    //                 'display_text' => $fullStateName,
    //                 'state' => $item->StateOrProvince,
    //                 'action_url' => "/api/properties/search?state=" . urlencode($item->StateOrProvince)
    //             ];
    //         });

    //     $postalCodeSuggestions = DB::table('properties')
    //         ->select('PostalCode', 'StateOrProvince')
    //         ->where('PostalCode', 'like', "%{$query}%")
    //         ->whereNotNull('PostalCode')
    //         ->where('PostalCode', '!=', '')
    //         ->groupBy('PostalCode', 'StateOrProvince')
    //         ->orderByRaw("CASE WHEN PostalCode LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'postal_code',  // Changed from 'state' to 'postal_code'
    //                 'name' => $item->PostalCode,
    //                 'display_text' => $item->StateOrProvince ? $item->StateOrProvince : null,
    //                 'action_url' => "/api/properties/search?postalCode=" . $item->PostalCode
    //             ];
    //         });

    //     // Combine city and state suggestions
    //     $placeSuggestions = $citySuggestions->concat($stateSuggestions)->concat($postalCodeSuggestions)->take($placeLimit);

    //     // Combine all suggestions
    //     $allSuggestions = collect([])
    //         ->concat($addressSuggestions)
    //         ->concat($buildingSuggestions)
    //         ->concat($placeSuggestions)
    //         ->sortBy(function ($item) use ($query) {
    //             // Sort by relevance - items that start with the query should come first
    //             $searchableText = $item['type'] === 'address' || $item['type'] === 'building'
    //                 ? $item['address']
    //                 : ($item['type'] === 'place' ? $item['name'] : '');

    //             if (stripos($searchableText, $query) === 0) {
    //                 return 0; // Highest priority if text starts with query
    //             } else if (stripos($searchableText, $query) !== false) {
    //                 return 1; // Medium priority if text contains query
    //             } else {
    //                 return 2; // Lowest priority for other matches
    //             }
    //         })
    //         ->values()
    //         ->take($limit);

    //     // Group suggestions by type
    //     $groupedSuggestions = [
    //         'addresses' => $allSuggestions->where('type', 'address')->values(),
    //         'buildings' => $allSuggestions->where('type', 'building')->values(),
    //         'places' => $allSuggestions->where('type', 'place')->values()
    //     ];

    //     return response()->json([
    //         'suggestions' => $groupedSuggestions
    //     ]);
    // }


    /**
     * Provides autocomplete suggestions for property searches.
     *
     * This method generates suggestions across three categories: addresses, buildings, and places
     * based on a user's search query. It supports filtering by property type (buy/rent) and 
     * provides a flexible search across various property attributes.
     *
     * @param Request $request The HTTP request containing search parameters
     * @return \Illuminate\Http\JsonResponse A JSON response with grouped suggestions
     */
    // public function autocomplete(Request $request)
    // {
    //     // dd($request->all());
    //     // dd("dfsfsdg");
    //     // Get search parameters - support both 'q' and 'query' parameters
    //     $query = $request->input('q') ?? $request->input('query');
    //     $type = $request->input('type'); // 'buy' or 'rent'
    //     $limit = $request->input('limit', 15); // Default 15 suggestions total


    //     // dd($request->all());
    //     // Validate query parameter
    //     if (empty($query) || strlen($query) < 0) {
    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => []
    //             ]
    //         ]);
    //     }

    //     // Allocate limits for each category
    //     $addressLimit = ceil($limit * 0.4); // 40% for addresses
    //     $buildingLimit = ceil($limit * 0.3); // 30% for buildings
    //     $placeLimit = ceil($limit * 0.3); // 30% for places

    //     // Define property types that are typically individual properties
    //     $individualPropertyTypes = [
    //         'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
    //         'UnimprovedLand', 'Special Purpose'
    //     ];

    //     // Base query without status filter
    //     $baseQuery = BridgeProperty::query();

    //     // Apply type filter if provided
    //     if ($type) {
    //         switch (strtolower($type)) {
    //             case 'buy':
    //                 // Properties for sale
    //                 $baseQuery->whereNotIn('property_type', ['ResidentialLease', 'CommercialLease']);
    //                 break;

    //             case 'rent':
    //                 // Properties for rent
    //                 $baseQuery->whereIn('property_type', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //         }
    //     }

    //     // 1. ADDRESS SUGGESTIONS - Individual property addresses
    //     $addressQuery = clone $baseQuery;
    //     $addressSuggestions = $addressQuery->select(
    //         'id',
    //         'listing_key',
    //         'street_number',
    //         'street_name',
    //         'unit_number',
    //         'city',
    //         'state_or_province',
    //         'postal_code',
    //         'property_sub_type',
    //         'list_price',
    //         'standard_status'
    //     )
    //         ->where(function ($q) use ($query) {
    //             // Prioritize exact matches first
    //             $q->where('unparsed_address', 'like', "{$query}%")
    //                 ->orWhere(DB::raw("CONCAT(street_number, ' ', street_name)"), 'like', "{$query}%")
    //                 // Then try partial matches
    //                 ->orWhere('unparsed_address', 'like', "%{$query}%")
    //                 ->orWhere('street_number', 'like', "{$query}%")
    //                 ->orWhere('street_name', 'like', "%{$query}%")
    //                 ->orWhere('city', 'like', "%{$query}%")
    //                 ->orWhere('state_or_province', 'like', "%{$query}%");
    //         })
    //         ->orderByRaw("CASE 
    //     WHEN unparsed_address LIKE '{$query}%' THEN 1
    //     WHEN CONCAT(street_number, ' ', street_name) LIKE '{$query}%' THEN 2
    //     ELSE 3
    //     END")
    //         ->limit($addressLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             $address = trim($item->street_number . ' ' . $item->street_name);
    //             if (!empty($item->unit_number)) {
    //                 $address .= ' #' . $item->unit_number;
    //             }

    //             return [
    //                 'type' => 'address',
    //                 'id' => $item->id,
    //                 'listing_key' => $item->listing_key,
    //                 'address' => $address,
    //                 'city' => $item->city,
    //                 'state' => $item->state_or_province,
    //                 'postal_code' => $item->postal_code,
    //                 'property_type' => $item->property_sub_type,
    //                 'price' => $item->list_price,
    //                 'status' => $item->standard_status,
    //                 'display_text' => $address .
    //                     ($item->city ? ', ' . $item->city : '') .
    //                     ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                 'action_url' => "/api/properties/{$item->id}"
    //             ];
    //         });

    //     $buildingNames = DB::table('bridge_property_details')
    //         ->select('property_id', 'building_name')
    //         ->whereNotNull('building_name')
    //         ->where('building_name', '!=', '')
    //         ->where('building_name', 'like', "%{$query}%")
    //         ->get()
    //         ->keyBy('property_id');

    //     // Get property IDs with building names
    //     $propertyIdsWithBuildingNames = $buildingNames->keys()->toArray();

    //     // Get properties with those IDs to get full property details
    //     $propertiesWithBuildingNames = [];
    //     if (!empty($propertyIdsWithBuildingNames)) {
    //         $propertiesWithBuildingNames = BridgeProperty::whereIn('id', $propertyIdsWithBuildingNames)
    //             ->select(
    //                 'id',
    //                 'street_number',
    //                 'street_name',
    //                 'street_dir_prefix',
    //                 'city',
    //                 'state_or_province',
    //                 'postal_code',
    //                 'property_sub_type'
    //             )
    //             ->get()
    //             ->keyBy('id');
    //     }


    //     $buildingQuery = DB::table('bridge_properties as bp')
    //         ->select(
    //             'bp.street_number',
    //             'bp.street_name',
    //             'bp.street_dir_prefix',
    //             'bp.city',
    //             'bp.state_or_province',
    //             'bp.postal_code',
    //             'bp.property_sub_type',
    //             DB::raw('MAX(bpd.building_name) as building_name'), // Use MAX to aggregate building_name
    //             DB::raw('COUNT(*) as unit_count'),
    //             DB::raw('MIN(bp.list_price) as min_price'),
    //             DB::raw('MAX(bp.list_price) as max_price')
    //         )
    //         ->leftJoin('bridge_property_details as bpd', 'bp.id', '=', 'bpd.property_id')
    //         ->whereNotNull('bp.street_number')
    //         ->whereNotNull('bp.street_name')
    //         ->whereNotIn('bp.property_sub_type', $individualPropertyTypes);

    //     // Apply type filter to buildings
    //     if ($type) {
    //         switch (strtolower($type)) {
    //             case 'buy':
    //                 // Exclude leases using NOT LIKE
    //                 $buildingQuery->where('bp.property_type', 'not like', '%Lease%');
    //                 break;
    //             case 'rent':
    //                 // Include only leases using LIKE
    //                 $buildingQuery->where('bp.property_type', 'like', '%Lease%');
    //                 break;
    //         }
    //     }

    //     // Continue with the rest of the query conditions
    //     $buildingQuery->where(function ($q) use ($query) {
    //         // Prioritize exact matches first
    //         $q->where(DB::raw("CONCAT(bp.street_number, ' ', bp.street_name)"), 'like', "{$query}%")
    //             ->orWhere('bpd.building_name', 'like', "{$query}%")
    //             // Then try partial matches
    //             ->orWhere(DB::raw("CONCAT(bp.street_number, ' ', bp.street_name)"), 'like', "%{$query}%")
    //             ->orWhere('bpd.building_name', 'like', "%{$query}%")
    //             ->orWhere('bp.street_number', 'like', "{$query}%")
    //             ->orWhere('bp.street_name', 'like', "%{$query}%")
    //             ->orWhere('bp.city', 'like', "%{$query}%");
    //     });

    //     $buildingSuggestions = $buildingQuery
    //         ->groupBy(
    //             'bp.street_number',
    //             'bp.street_name',
    //             'bp.street_dir_prefix',
    //             'bp.city',
    //             'bp.state_or_province',
    //             'bp.postal_code',
    //             'bp.property_sub_type'
    //         )
    //         ->havingRaw('COUNT(*) > 1')
    //         ->orderByRaw("CASE 
    //             WHEN CONCAT(bp.street_number, ' ', bp.street_name) LIKE '{$query}%' THEN 1
    //             WHEN MAX(bpd.building_name) LIKE '{$query}%' THEN 2
    //             ELSE 3
    //         END")
    //         ->limit($buildingLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             // Access properties as object properties, not array indices
    //             $address = trim($item->street_number . ' ' .
    //                 ($item->street_dir_prefix ? $item->street_dir_prefix . ' ' : '') .
    //                 $item->street_name);

    //             // Use the building_name from the query result if available
    //             $buildingName = !empty($item->building_name) ? $item->building_name : $address;

    //             return [
    //                 'type' => 'building',
    //                 'building_name' => $buildingName,
    //                 'street_number' => $item->street_number,
    //                 'street_dir_prefix' => $item->street_dir_prefix,
    //                 'street_name' => $item->street_name,
    //                 'address' => $address,
    //                 'city' => $item->city,
    //                 'state' => $item->state_or_province,
    //                 'postal_code' => $item->postal_code,
    //                 'property_sub_type' => $item->property_sub_type,
    //                 'unit_count' => $item->unit_count,
    //                 'min_price' => $item->min_price,
    //                 'max_price' => $item->max_price,
    //                 'display_text' => $buildingName .
    //                     ($item->city ? ', ' . $item->city : '') .
    //                     ($item->state_or_province ? ', ' . $item->state_or_province : '') .
    //                     ' (' . $item->unit_count . ' units)',
    //                 'action_url' => "/api/buildings?street_number={$item->street_number}&street_name=" . urlencode($item->street_name)
    //             ];
    //         });


    //     $citySuggestions = DB::table('bridge_properties')
    //         ->select('city', 'state_or_province')
    //         ->where('city', 'like', "%{$query}%")
    //         ->whereNotNull('city')
    //         ->where('city', '!=', '')
    //         ->where('city', '!=', ',') // Exclude invalid city names
    //         ->groupBy('city', 'state_or_province')
    //         ->orderByRaw("CASE WHEN city LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'city',
    //                 'name' => $item->city,
    //                 'state' => $item->state_or_province,
    //                 'display_text' => $item->city . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                 'action_url' => "/api/properties/search?city=" . urlencode($item->city)
    //             ];
    //         });

    //     $stateSuggestions = DB::table('bridge_properties')
    //         ->select('state_or_province')
    //         ->where('state_or_province', 'like', "%{$query}%")
    //         ->whereNotNull('state_or_province')
    //         ->where('state_or_province', '!=', '')
    //         ->groupBy('state_or_province')
    //         ->orderByRaw("CASE WHEN state_or_province LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             $fullStateName = $this->getFullStateName($item->state_or_province);
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'state',
    //                 'name' => $fullStateName,
    //                 'code' => $item->state_or_province,
    //                 'display_text' => $fullStateName,
    //                 'action_url' => "/api/properties/search?state=" . urlencode($item->state_or_province)
    //             ];
    //         });

    //     $postalCodeSuggestions = DB::table('bridge_properties')
    //         ->select('postal_code', 'state_or_province')
    //         ->where('postal_code', 'like', "%{$query}%")
    //         ->whereNotNull('postal_code')
    //         ->where('postal_code', '!=', '')
    //         ->groupBy('postal_code', 'state_or_province')
    //         ->orderByRaw("CASE WHEN postal_code LIKE '{$query}%' THEN 1 ELSE 2 END")
    //         ->limit($placeLimit)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'postal_code',
    //                 'name' => $item->postal_code,
    //                 'display_text' => $item->postal_code . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                 'action_url' => "/api/properties/search?postalCode=" . $item->postal_code
    //             ];
    //         });

    //     // Combine city and state suggestions
    //     $placeSuggestions = $citySuggestions->concat($stateSuggestions)->concat($postalCodeSuggestions)->take($placeLimit);

    //     // Combine all suggestions
    //     $allSuggestions = collect([])
    //         ->concat($addressSuggestions)
    //         ->concat($buildingSuggestions)
    //         ->concat($placeSuggestions)
    //         ->sortBy(function ($item) use ($query) {
    //             // Sort by relevance - items that start with the query should come first
    //             $searchableText = '';

    //             if ($item['type'] === 'address') {
    //                 $searchableText = $item['address'];
    //             } else if ($item['type'] === 'building') {
    //                 $searchableText = $item['building_name'] ?? $item['address'];
    //             } else if ($item['type'] === 'place') {
    //                 $searchableText = $item['name'];
    //             }

    //             if (stripos($searchableText, $query) === 0) {
    //                 return 0; // Highest priority if text starts with query
    //             } else if (stripos($searchableText, $query) !== false) {
    //                 return 1; // Medium priority if text contains query
    //             } else {
    //                 return 2; // Lowest priority for other matches
    //             }
    //         })
    //         ->values()
    //         ->take($limit);

    //     // Group suggestions by type
    //     $groupedSuggestions = [
    //         'addresses' => $allSuggestions->where('type', 'address')->values(),
    //         'buildings' => $allSuggestions->where('type', 'building')->values(),
    //         'places' => $allSuggestions->where('type', 'place')->values()
    //     ];

    //     return response()->json([
    //         'suggestions' => $groupedSuggestions
    //     ]);
    // }

    //     public function autocomplete(Request $request)
    // {
    //     // Get search parameters
    //     $query = $request->input('q') ?? $request->input('query');
    //     $type = $request->input('type'); // 'buy' or 'rent'
    //     $limit = $request->input('limit', 15); // Default 15 suggestions total

    //     // Early return for empty queries
    //     if (empty($query) || strlen($query) < 1) {
    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => []
    //             ]
    //         ]);
    //     }

    //     // Allocate limits for each category
    //     $addressLimit = ceil($limit * 0.4); // 40% for addresses
    //     $buildingLimit = ceil($limit * 0.3); // 30% for buildings
    //     $placeLimit = ceil($limit * 0.3); // 30% for places

    //     // Define property types that are typically individual properties
    //     $individualPropertyTypes = [
    //         'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
    //         'UnimprovedLand', 'Special Purpose'
    //     ];

    //     // Cache key based on request parameters
    //     $cacheKey = "autocomplete:{$query}:{$type}:{$limit}";

    //     // Try to get results from cache first (with 1 hour expiration)
    //     return Cache::remember($cacheKey, 3600, function() use (
    //         $query, $type, $limit, $addressLimit, $buildingLimit, $placeLimit, $individualPropertyTypes
    //     ) {
    //         // 1. ADDRESS SUGGESTIONS - Use indexed columns and limit the WHERE conditions
    //         $addressQuery = BridgeProperty::select(
    //             'id', 'listing_key', 'street_number', 'street_name', 'unit_number',
    //             'city', 'state_or_province', 'postal_code', 'property_sub_type',
    //             'list_price', 'standard_status'
    //         );

    //         // Apply type filter if provided
    //         if ($type) {
    //             if (strtolower($type) === 'buy') {
    //                 $addressQuery->where('property_type', 'not like', '%Lease%');
    //             } else if (strtolower($type) === 'rent') {
    //                 $addressQuery->where('property_type', 'like', '%Lease%');
    //             }
    //         }

    //         // Optimize the search conditions - focus on the most important fields first
    //         // Use LIKE with right wildcard for better index usage
    //         $addressQuery->where(function ($q) use ($query) {
    //             $q->where('unparsed_address', 'like', "{$query}%")
    //               ->orWhere(DB::raw("CONCAT(street_number, ' ', street_name)"), 'like', "{$query}%")
    //               ->orWhere('street_name', 'like', "{$query}%")
    //               ->orWhere('city', 'like', "{$query}%");
    //         });

    //         // Use simpler ordering that can leverage indexes
    //         $addressSuggestions = $addressQuery
    //             ->orderBy('unparsed_address')
    //             ->limit($addressLimit)
    //             ->get()
    //             ->map(function ($item) {
    //                 $address = trim($item->street_number . ' ' . $item->street_name);
    //                 if (!empty($item->unit_number)) {
    //                     $address .= ' #' . $item->unit_number;
    //                 }
    //                 return [
    //                     'type' => 'address',
    //                     'id' => $item->id,
    //                     'listing_key' => $item->listing_key,
    //                     'address' => $address,
    //                     'city' => $item->city,
    //                     'state' => $item->state_or_province,
    //                     'postal_code' => $item->postal_code,
    //                     'property_type' => $item->property_sub_type,
    //                     'price' => $item->list_price,
    //                     'status' => $item->standard_status,
    //                     'display_text' => $address .
    //                         ($item->city ? ', ' . $item->city : '') .
    //                         ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                     'action_url' => "/api/properties/{$item->id}"
    //                 ];
    //             });

    //         // 2. BUILDING SUGGESTIONS - Optimize the complex query
    //         $buildingQuery = DB::table('bridge_properties as bp')
    //             ->select(
    //                 'bp.street_number',
    //                 'bp.street_name',
    //                 'bp.street_dir_prefix',
    //                 'bp.city',
    //                 'bp.state_or_province',
    //                 'bp.postal_code',
    //                 'bp.property_sub_type',
    //                 DB::raw('MAX(bpd.building_name) as building_name'),
    //                 DB::raw('COUNT(*) as unit_count'),
    //                 DB::raw('MIN(bp.list_price) as min_price'),
    //                 DB::raw('MAX(bp.list_price) as max_price')
    //             )
    //             ->leftJoin('bridge_property_details as bpd', 'bp.id', '=', 'bpd.property_id')
    //             ->whereNotNull('bp.street_number')
    //             ->whereNotNull('bp.street_name')
    //             ->whereNotIn('bp.property_sub_type', $individualPropertyTypes);

    //         // Apply type filter to buildings
    //         if ($type) {
    //             if (strtolower($type) === 'buy') {
    //                 $buildingQuery->where('bp.property_type', 'not like', '%Lease%');
    //             } else if (strtolower($type) === 'rent') {
    //                 $buildingQuery->where('bp.property_type', 'like', '%Lease%');
    //             }
    //         }

    //         // Optimize the search conditions
    //         $buildingQuery->where(function ($q) use ($query) {
    //             $q->where(DB::raw("CONCAT(bp.street_number, ' ', bp.street_name)"), 'like', "{$query}%")
    //               ->orWhere('bpd.building_name', 'like', "{$query}%")
    //               ->orWhere('bp.street_name', 'like', "{$query}%")
    //               ->orWhere('bp.city', 'like', "{$query}%");
    //         });

    //         $buildingSuggestions = $buildingQuery
    //             ->groupBy(
    //                 'bp.street_number',
    //                 'bp.street_name',
    //                 'bp.street_dir_prefix',
    //                 'bp.city',
    //                 'bp.state_or_province',
    //                 'bp.postal_code',
    //                 'bp.property_sub_type'
    //             )
    //             ->havingRaw('COUNT(*) > 1')
    //             ->orderBy('bp.street_name')
    //             ->limit($buildingLimit)
    //             ->get()
    //             ->map(function ($item) {
    //                 $address = trim($item->street_number . ' ' .
    //                     ($item->street_dir_prefix ? $item->street_dir_prefix . ' ' : '') .
    //                     $item->street_name);
    //                 $buildingName = !empty($item->building_name) ? $item->building_name : $address;
    //                 return [
    //                     'type' => 'building',
    //                     'building_name' => $buildingName,
    //                     'street_number' => $item->street_number,
    //                     'street_dir_prefix' => $item->street_dir_prefix,
    //                     'street_name' => $item->street_name,
    //                     'address' => $address,
    //                     'city' => $item->city,
    //                     'state' => $item->state_or_province,
    //                     'postal_code' => $item->postal_code,
    //                     'property_sub_type' => $item->property_sub_type,
    //                     'unit_count' => $item->unit_count,
    //                     'display_text' => $buildingName .
    //                         ($item->city ? ', ' . $item->city : '') .
    //                         ($item->state_or_province ? ', ' . $item->state_or_province : '') .
    //                         ' (' . $item->unit_count . ' units)',
    //                     'action_url' => "/api/buildings?street_number={$item->street_number}&street_name=" . urlencode($item->street_name)
    //                 ];
    //             });

    //         // 3. PLACE SUGGESTIONS - Run these queries in parallel using Promise-like approach
    //         $placeQueries = [
    //             'city' => function() use ($query, $placeLimit) {
    //                 return DB::table('bridge_properties')
    //                     ->select('city', 'state_or_province')
    //                     ->where('city', 'like', "{$query}%")
    //                     ->whereNotNull('city')
    //                     ->where('city', '!=', '')
    //                     ->where('city', '!=', ',')
    //                     ->groupBy('city', 'state_or_province')
    //                     ->orderBy('city')
    //                     ->limit($placeLimit)
    //                     ->get();
    //             },
    //             'state' => function() use ($query, $placeLimit) {
    //                 return DB::table('bridge_properties')
    //                     ->select('state_or_province')
    //                     ->where('state_or_province', 'like', "{$query}%")
    //                     ->whereNotNull('state_or_province')
    //                     ->where('state_or_province', '!=', '')
    //                     ->groupBy('state_or_province')
    //                     ->orderBy('state_or_province')
    //                     ->limit($placeLimit)
    //                     ->get();
    //             },
    //             'postal' => function() use ($query, $placeLimit) {
    //                 return DB::table('bridge_properties')
    //                     ->select('postal_code', 'state_or_province')
    //                     ->where('postal_code', 'like', "{$query}%")
    //                     ->whereNotNull('postal_code')
    //                     ->where('postal_code', '!=', '')
    //                     ->groupBy('postal_code', 'state_or_province')
    //                     ->orderBy('postal_code')
    //                     ->limit($placeLimit)
    //                     ->get();
    //             },
    //         ];

    //         // Execute place queries
    //         $cityResults = $placeQueries['city']();
    //         $stateResults = $placeQueries['state']();
    //         $postalResults = $placeQueries['postal']();

    //         // Map city results
    //         $citySuggestions = $cityResults->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'city',
    //                 'name' => $item->city,
    //                 'state' => $item->state_or_province,
    //                 'display_text' => $item->city . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                 'action_url' => "/api/properties/search?city=" . urlencode($item->city)
    //             ];
    //         });

    //         // Map state results
    //         $stateSuggestions = $stateResults->map(function ($item) {
    //             $fullStateName = $this->getFullStateName($item->state_or_province);
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'state',
    //                 'name' => $fullStateName,
    //                 'code' => $item->state_or_province,
    //                 'display_text' => $fullStateName,
    //                 'action_url' => "/api/properties/search?state=" . urlencode($item->state_or_province)
    //             ];
    //         });

    //         // Map postal code results
    //         $postalCodeSuggestions = $postalResults->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'postal_code',
    //                 'name' => $item->postal_code,
    //                 'display_text' => $item->postal_code . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                 'action_url' => "/api/properties/search?postalCode=" . $item->postal_code
    //             ];
    //         });

    //         // Combine place suggestions
    //         $placeSuggestions = $citySuggestions->concat($stateSuggestions)->concat($postalCodeSuggestions)->take($placeLimit);

    //         // Group suggestions by type
    //         $groupedSuggestions = [
    //             'addresses' => $addressSuggestions->values(),
    //             'buildings' => $buildingSuggestions->values(),
    //             'places' => $placeSuggestions->values()
    //         ];

    //         return [
    //             'suggestions' => $groupedSuggestions
    //         ];
    //     });
    // }


    // public function autocomplete(Request $request)
    // {
    //     $query = $request->input('q') ?? $request->input('query');
    //     $type = $request->input('type');
    //     $limit = $request->input('limit', 15);

    //     if (empty($query) || strlen($query) < 1) {
    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => [],
    //             ]
    //         ]);
    //     }

    //     $addressLimit = ceil($limit * 0.4);
    //     $buildingLimit = ceil($limit * 0.3);
    //     $placeLimit = ceil($limit * 0.3);

    //     $individualPropertyTypes = [
    //         'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
    //         'UnimprovedLand', 'Special Purpose'
    //     ];

    //     $cacheKey = "autocomplete:{$query}:{$type}:{$limit}";

    //     return Cache::remember($cacheKey, 3600, function () use (
    //         $query, $type, $addressLimit, $buildingLimit, $placeLimit, $individualPropertyTypes
    //     ) {
    //         // Build base query for reuse
    //         $baseQuery = BridgeProperty::query();

    //        if ($type) {
    //             switch (strtolower($type)) {
    //                 case 'buy':
    //                     // Properties for sale
    //                     $baseQuery->whereIn('standard_status', ['Active', 'Active Under Contract', 'Pending'])
    //                             ->where('property_type', 'Residential');
    //                     break;
    //                 case 'rent':
    //                     // Properties for rent
    //                     $baseQuery->where('standard_status', 'Active')
    //                             ->where('property_type', 'ResidentialLease');
    //                     break;
    //             }
    //         }

    //         $baseQuery->where(function ($q) use ($query) {
    //             $q->where('unparsed_address', 'like', "{$query}%")
    //             ->orWhere(DB::raw("CONCAT(street_number, ' ', street_name)"), 'like', "{$query}%")
    //             ->orWhere('street_name', 'like', "{$query}%")
    //             ->orWhere('city', 'like', "{$query}%");
    //         });

    //         // Clone base query for addresses
    //         $addressSuggestions = (clone $baseQuery)
    //             ->select('id', 'listing_key', 'street_number', 'street_name', 'unit_number', 'city', 'state_or_province', 'postal_code', 'property_sub_type', 'list_price', 'standard_status')
    //             ->orderBy('unparsed_address')
    //             ->limit($addressLimit)
    //             ->get()
    //             ->map(function ($item) {
    //                 $address = trim($item->street_number . ' ' . $item->street_name);
    //                 if (!empty($item->unit_number)) {
    //                     $address .= ' #' . $item->unit_number;
    //                 }
    //                 return [
    //                     'type' => 'address',
    //                     'id' => $item->id,
    //                     'listing_key' => $item->listing_key,
    //                     'address' => $address,
    //                     'city' => $item->city,
    //                     'state' => $item->state_or_province,
    //                     'postal_code' => $item->postal_code,
    //                     'property_type' => $item->property_sub_type,
    //                     'price' => $item->list_price,
    //                     'status' => $item->standard_status,
    //                     'display_text' => $address .
    //                         ($item->city ? ', ' . $item->city : '') .
    //                         ($item->state_or_province ? ', ' . $item->state_or_province : ''),
    //                     'action_url' => "/api/properties/{$item->id}"
    //                 ];
    //             });

    //         // BUILDING suggestions
    //         $buildingSuggestions = DB::table('bridge_properties as bp')
    //             ->leftJoin('bridge_property_details as bpd', 'bp.id', '=', 'bpd.property_id')
    //             ->select(
    //                 'bp.street_number',
    //                 'bp.street_name',
    //                 'bp.street_dir_prefix',
    //                 'bp.city',
    //                 'bp.state_or_province',
    //                 'bp.postal_code',
    //                 'bp.property_sub_type',
    //                 DB::raw('MAX(bpd.building_name) as building_name'),
    //                 DB::raw('COUNT(*) as unit_count'),
    //                 DB::raw('MIN(bp.list_price) as min_price'),
    //                 DB::raw('MAX(bp.list_price) as max_price')
    //             )
    //             ->whereNotNull('bp.street_number')
    //             ->whereNotNull('bp.street_name')
    //             ->whereNotIn('bp.property_sub_type', $individualPropertyTypes)
    //             ->when($type, function ($q) use ($type) {
    //                 if (strtolower($type) === 'buy') {
    //                     $q->where('bp.property_type', 'not like', '%Lease%');
    //                 } elseif (strtolower($type) === 'rent') {
    //                     $q->where('bp.property_type', 'like', '%Lease%');
    //                 }
    //             })
    //             ->where(function ($q) use ($query) {
    //                 $q->where(DB::raw("CONCAT(bp.street_number, ' ', bp.street_name)"), 'like', "{$query}%")
    //                 ->orWhere('bpd.building_name', 'like', "{$query}%")
    //                 ->orWhere('bp.street_name', 'like', "{$query}%")
    //                 ->orWhere('bp.city', 'like', "{$query}%");
    //             })
    //             ->groupBy('bp.street_number', 'bp.street_name', 'bp.street_dir_prefix', 'bp.city', 'bp.state_or_province', 'bp.postal_code', 'bp.property_sub_type')
    //             ->havingRaw('COUNT(*) > 1')
    //             ->orderBy('bp.street_name')
    //             ->limit($buildingLimit)
    //             ->get()
    //             ->map(function ($item) {
    //                 $address = trim($item->street_number . ' ' .
    //                     ($item->street_dir_prefix ? $item->street_dir_prefix . ' ' : '') .
    //                     $item->street_name);
    //                 $buildingName = !empty($item->building_name) ? $item->building_name : $address;
    //                 return [
    //                     'type' => 'building',
    //                     'building_name' => $buildingName,
    //                     'street_number' => $item->street_number,
    //                     'street_dir_prefix' => $item->street_dir_prefix,
    //                     'street_name' => $item->street_name,
    //                     'address' => $address,
    //                     'city' => $item->city,
    //                     'state' => $item->state_or_province,
    //                     'postal_code' => $item->postal_code,
    //                     'property_sub_type' => $item->property_sub_type,
    //                     'unit_count' => $item->unit_count,
    //                     'display_text' => $buildingName .
    //                         ($item->city ? ', ' . $item->city : '') .
    //                         ($item->state_or_province ? ', ' . $item->state_or_province : '') .
    //                         ' (' . $item->unit_count . ' units)',
    //                     'action_url' => "/api/buildings?street_number={$item->street_number}&street_name=" . urlencode($item->street_name)
    //                 ];
    //             });

    //         // PLACE suggestions
    //         $places = collect();

    //         $cities = DB::table('bridge_properties')
    //             ->select('city', 'state_or_province')
    //             ->where('city', 'like', "{$query}%")
    //             ->whereNotNull('city')->where('city', '!=', '')
    //             ->groupBy('city', 'state_or_province')
    //             ->orderBy('city')
    //             ->limit($placeLimit)
    //             ->get();

    //         $states = DB::table('bridge_properties')
    //             ->select('state_or_province')
    //             ->where('state_or_province', 'like', "{$query}%")
    //             ->whereNotNull('state_or_province')->where('state_or_province', '!=', '')
    //             ->groupBy('state_or_province')
    //             ->orderBy('state_or_province')
    //             ->limit($placeLimit)
    //             ->get();

    //         $postals = DB::table('bridge_properties')
    //             ->select('postal_code', 'state_or_province')
    //             ->where('postal_code', 'like', "{$query}%")
    //             ->whereNotNull('postal_code')->where('postal_code', '!=', '')
    //             ->groupBy('postal_code', 'state_or_province')
    //             ->orderBy('postal_code')
    //             ->limit($placeLimit)
    //             ->get();

    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => $addressSuggestions,
    //                 'buildings' => $buildingSuggestions,
    //                 'places' => $cities->merge($states)->merge($postals),
    //             ]
    //         ]);
    //     });
    // }

    //     public function autocomplete(Request $request)
    //     {
    //         // Get search parameters - support both 'q' and 'query' parameters
    //         $query = $request->input('q') ?? $request->input('query');
    //         $type = $request->input('type'); // 'buy' or 'rent'
    //         $limit = $request->input('limit', 5); // Default 15 suggestions total

    //         // Validate query parameter
    //         if (empty($query) || strlen($query) < 1) {
    //             return response()->json([
    //                 'suggestions' => [
    //                     'addresses' => [],
    //                     'buildings' => [],
    //                     'places' => []
    //                 ]
    //             ]);
    //         }

    //         // Allocate limits for each category
    //         $addressLimit = ceil($limit * 0.4); // 40% for addresses
    //         $buildingLimit = ceil($limit * 0.3); // 30% for buildings
    //         $placeLimit = ceil($limit * 0.3); // 30% for places

    //         // Base API URL and access token
    //         $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
    //         $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';

    //         try {
    //             // 1. ADDRESS SUGGESTIONS - Individual property addresses
    //             $addressSuggestions = $this->fetchAddressSuggestions($baseUrl, $accessToken, $query, $type, $addressLimit);

    //             // 2. BUILDING SUGGESTIONS
    //             $buildingSuggestions = $this->fetchBuildingSuggestions($baseUrl, $accessToken, $query, $type, $buildingLimit);

    //             // 3. PLACE SUGGESTIONS (Cities, States, Postal Codes)
    //             $placeSuggestions = $this->fetchPlaceSuggestions($baseUrl, $accessToken, $query, $placeLimit);

    //             // Combine all suggestions
    //             $allSuggestions = collect([])
    //                 ->concat($addressSuggestions)
    //                 ->concat($buildingSuggestions)
    //                 ->concat($placeSuggestions)
    //                 ->sortBy(function ($item) use ($query) {
    //                     // Sort by relevance - items that start with the query should come first
    //                     $searchableText = '';
    //                     if ($item['type'] === 'address') {
    //                         $searchableText = $item['address'];
    //                     } else if ($item['type'] === 'building') {
    //                         $searchableText = $item['building_name'] ?? $item['address'];
    //                     } else if ($item['type'] === 'place') {
    //                         $searchableText = $item['name'];
    //                     }

    //                     if (stripos($searchableText, $query) === 0) {
    //                         return 0; // Highest priority if text starts with query
    //                     } else if (stripos($searchableText, $query) !== false) {
    //                         return 1; // Medium priority if text contains query
    //                     } else {
    //                         return 2; // Lowest priority for other matches
    //                     }
    //                 })
    //                 ->values()
    //                 ->take($limit);

    //             // Group suggestions by type
    //             $groupedSuggestions = [
    //                 'addresses' => $allSuggestions->where('type', 'address')->values(),
    //                 'buildings' => $allSuggestions->where('type', 'building')->values(),
    //                 'places' => $allSuggestions->where('type', 'place')->values()
    //             ];

    //             return response()->json(['suggestions' => $groupedSuggestions]);

    //         } catch (\Exception $e) {
    //             Log::error('Exception when fetching autocomplete suggestions from Bridge API', [
    //                 'exception' => $e->getMessage(),
    //                 'query' => $query
    //             ]);

    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'An error occurred while fetching suggestions',
    //                 'suggestions' => [
    //                     'addresses' => [],
    //                     'buildings' => [],
    //                     'places' => []
    //                 ]
    //             ], 500);
    //         }
    //     }

    //     private function fetchAddressSuggestions($baseUrl, $accessToken, $query, $type, $limit)
    //     {
    //         // Define property types that are typically individual properties
    //         $individualPropertyTypes = ['Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity', 'UnimprovedLand', 'Special Purpose'];

    //         $queryParams = [
    //             'access_token' => $accessToken,
    //             'limit' => $limit,
    //             'fields' => 'ListingId,ListingKey,StreetNumber,StreetName,UnitNumber,City,StateOrProvince,PostalCode,PropertySubType,ListPrice,StandardStatus,UnparsedAddress',
    //             'order' => 'ListPrice asc'
    //         ];

    //         // Add search conditions
    //         $queryParams['q'] = "UnparsedAddress eq '{$query}*' OR UnparsedAddress like '*{$query}*' OR StreetNumber eq '{$query}*' OR StreetName like '*{$query}*' OR City like '*{$query}*' OR StateOrProvince like '*{$query}*'";

    //         // Apply type filter if
    //         if ($type) {
    //             if (strtolower($type) === 'buy') {
    //                 $queryParams['PropertyType'] != 'Residential Lease';
    //                 // $queryParams['StandardStatus'] = 'Active,Pending,Active Under Contract';
    //             } elseif (strtolower($type) === 'rent') {
    //                 $queryParams['PropertyType'] = 'Residential Lease';
    //                 // $queryParams['StandardStatus'] = 'Active,Pending,Active Under Contract';
    //             }
    //         }

    //         // Exclude building-type properties
    //         // $queryParams['PropertySubType.ne'] = implode(',', $individualPropertyTypes);

    //         $response = Http::get($baseUrl, $queryParams);

    //         if (!$response->successful()) {
    //             Log::error('Bridge API request failed for address suggestions', [
    //                 'status' => $response->status(),
    //                 'response' => $response->json()
    //             ]);
    //             return collect([]);
    //         }

    //         $data = $response->json();
    //         $properties = $data['bundle'] ?? [];

    //         return collect($properties)->map(function ($item) {
    //             $address = trim($item['StreetNumber'] . ' ' . $item['StreetName']);
    //             if (!empty($item['UnitNumber'])) {
    //                 $address .= ' #' . $item['UnitNumber'];
    //             }

    //             return [
    //                 'type' => 'address',
    //                 'id' => $item['ListingId'],
    //                 'listing_key' => $item['ListingKey'],
    //                 'address' => $address,
    //                 'city' => $item['City'],
    //                 'state' => $item['StateOrProvince'],
    //                 'postal_code' => $item['PostalCode'],
    //                 'property_type' => $item['PropertySubType'],
    //                 'price' => $item['ListPrice'],
    //                 'status' => $item['StandardStatus'],
    //                 'display_text' => $address . 
    //                     ($item['City'] ? ', ' . $item['City'] : '') . 
    //                     ($item['StateOrProvince'] ? ', ' . $item['StateOrProvince'] : ''),
    //                 'action_url' => "/api/properties/{$item['ListingId']}"
    //             ];
    //         });
    //     }

    //     private function fetchBuildingSuggestions($baseUrl, $accessToken, $query, $type, $limit)
    //     {
    //         // For buildings, we need to make a more complex query to group properties by address
    //         $queryParams = [
    //             'access_token' => $accessToken,
    //             'limit' => max(1, $limit * 2), // Ensure limit is positive and get extra to filter
    //             'fields' => 'ListingId,StreetNumber,StreetName,City,StateOrProvince,PostalCode,PropertySubType,ListPrice,BuildingName',
    //             'groupBy' => 'StreetNumber,StreetName,City'
    //             // Remove the aggregations parameter
    //         ];

    //         // Add search conditions
    //         $queryParams['q'] = "BuildingName like '*{$query}*' OR StreetNumber eq '{$query}*' OR StreetName like '*{$query}*' OR City like '*{$query}*' OR CONCAT(StreetNumber,' ',StreetName) like '*{$query}*'";

    //         // Apply type filter if provided
    //         if ($type) {
    //             if (strtolower($type) === 'buy') {
    //                 $queryParams['PropertyType.ne'] = 'Residential Lease'; // Fixed assignment operator
    //             } elseif (strtolower($type) === 'rent') {
    //                 $queryParams['PropertyType'] = 'Residential Lease';
    //             }
    //         }

    //         $response = Http::get($baseUrl, $queryParams);

    //         if (!$response->successful()) {
    //             Log::error('Bridge API request failed for building suggestions', [
    //                 'status' => $response->status(),
    //                 'response' => $response->json()
    //             ]);
    //             return collect([]);
    //         }

    //         $data = $response->json();
    //         $buildings = $data['bundle'] ?? [];

    //         // Process the buildings - we'll need to manually calculate counts and prices
    //         $buildingGroups = collect($buildings)->groupBy(function($item) {
    //             return $item['StreetNumber'] . '-' . $item['StreetName'] . '-' . $item['City'];
    //         });

    //         // Only keep groups with multiple units (buildings)
    //         $buildingGroups = $buildingGroups->filter(function($group) {
    //             return $group->count() > 1;
    //         });

    //         // Map the building groups to the expected format
    //         return $buildingGroups->take($limit)->map(function($group, $key) {
    //             $firstItem = $group->first();

    //             $address = trim($firstItem['StreetNumber'] . ' ' . 
    //                 ($firstItem['StreetDirPrefix'] ? $firstItem['StreetDirPrefix'] . ' ' : '') . 
    //                 $firstItem['StreetName']);

    //             $buildingName = !empty($firstItem['BuildingName']) ? $firstItem['BuildingName'] : $address;

    //             // Calculate min and max prices
    //             $prices = $group->pluck('ListPrice')->filter()->values();
    //             $minPrice = $prices->min();
    //             $maxPrice = $prices->max();

    //             return [
    //                 'type' => 'building',
    //                 'building_name' => $buildingName,
    //                 'street_number' => $firstItem['StreetNumber'],
    //                 'street_dir_prefix' => $firstItem['StreetDirPrefix'],
    //                 'street_name' => $firstItem['StreetName'],
    //                 'address' => $address,
    //                 'city' => $firstItem['City'],
    //                 'state' => $firstItem['StateOrProvince'],
    //                 'postal_code' => $firstItem['PostalCode'],
    //                 'property_sub_type' => $firstItem['PropertySubType'],
    //                 'unit_count' => $group->count(),
    //                 'min_price' => $minPrice,
    //                 'max_price' => $maxPrice,
    //                 'display_text' => $buildingName . 
    //                     ($firstItem['City'] ? ', ' . $firstItem['City'] : '') . 
    //                     ($firstItem['StateOrProvince'] ? ', ' . $firstItem['StateOrProvince'] : '') . 
    //                     ' (' . $group->count() . ' units)',
    //                 'action_url' => "/api/buildings?street_number={$firstItem['StreetNumber']}&street_name=" . urlencode($firstItem['StreetName'])
    //             ];
    //         })->values();
    //     }


    //     private function fetchPlaceSuggestions($baseUrl, $accessToken, $query, $limit)
    //     {
    //         // We'll make three separate requests for cities, states, and postal codes
    //         $cityLimit = ceil($limit / 3);
    //         $stateLimit = ceil($limit / 3);
    //         $postalCodeLimit = $limit - $cityLimit - $stateLimit;

    //         // 1. City suggestions
    //         $citySuggestions = $this->fetchCitySuggestions($baseUrl, $accessToken, $query, $cityLimit);

    //         // 2. State suggestions
    //         $stateSuggestions = $this->fetchStateSuggestions($baseUrl, $accessToken, $query, $stateLimit);

    //         // 3. Postal code suggestions
    //         $postalCodeSuggestions = $this->fetchPostalCodeSuggestions($baseUrl, $accessToken, $query, $postalCodeLimit);

    //         // Combine all place suggestions
    //         return $citySuggestions->concat($stateSuggestions)->concat($postalCodeSuggestions);
    //     }


    //     private function fetchCitySuggestions($baseUrl, $accessToken, $query, $limit)
    //     {
    //         $queryParams = [
    //             'access_token' => $accessToken,
    //             'limit' => $limit,
    //             'fields' => 'City,StateOrProvince',
    //             'groupBy' => 'City,StateOrProvince',
    //             'q' => "City like '*{$query}*'"
    //         ];

    //         $response = Http::get($baseUrl, $queryParams);

    //         if (!$response->successful()) {
    //             Log::error('Bridge API request failed for city suggestions', [
    //                 'status' => $response->status(),
    //                 'response' => $response->json()
    //             ]);
    //             return collect([]);
    //         }

    //         $data = $response->json();
    //         $cities = $data['bundle'] ?? [];

    //         return collect($cities)->map(function ($item) {
    //             return [
    //                 'type' => 'place',
    //                 'place_type' => 'city',
    //                 'name' => $item['City'],
    //                 'state' => $item['StateOrProvince'],
    //                 'display_text' => $item['City'] . ($item['StateOrProvince'] ? ', ' . $item['StateOrProvince'] : ''),
    //                 'action_url' => "/api/properties/search?city=" . urlencode($item['City'])
    //             ];
    //         });
    //     }

    // private function fetchStateSuggestions($baseUrl, $accessToken, $query, $limit)
    // {
    //     $queryParams = [
    //         'access_token' => $accessToken,
    //         'limit' => $limit,
    //         'fields' => 'StateOrProvince',
    //         'groupBy' => 'StateOrProvince',
    //         'q' => "StateOrProvince like '*{$query}*'"
    //     ];

    //     $response = Http::get($baseUrl, $queryParams);

    //     if (!$response->successful()) {
    //         Log::error('Bridge API request failed for state suggestions', [
    //             'status' => $response->status(),
    //             'response' => $response->json()
    //         ]);
    //         return collect([]);
    //     }

    //     $data = $response->json();
    //     $states = $data['bundle'] ?? [];

    //     return collect($states)->map(function ($item) {
    //         $fullStateName = $this->getFullStateName($item['StateOrProvince']);
    //         return [
    //             'type' => 'place',
    //             'place_type' => 'state',
    //             'name' => $fullStateName,
    //             'code' => $item['StateOrProvince'],
    //             'display_text' => $fullStateName,
    //             'action_url' => "/api/properties/search?state=" . urlencode($item['StateOrProvince'])
    //         ];
    //     });
    // }

    // private function fetchPostalCodeSuggestions($baseUrl, $accessToken, $query, $limit)
    // {
    //     $queryParams = [
    //         'access_token' => $accessToken,
    //         'limit' => $limit,
    //         'fields' => 'PostalCode,StateOrProvince',
    //         'groupBy' => 'PostalCode,StateOrProvince',
    //         'q' => "PostalCode like '*{$query}*'"
    //     ];

    //     $response = Http::get($baseUrl, $queryParams);

    //     if (!$response->successful()) {
    //         Log::error('Bridge API request failed for postal code suggestions', [
    //             'status' => $response->status(),
    //             'response' => $response->json()
    //         ]);
    //         return collect([]);
    //     }

    //     $data = $response->json();
    //     $postalCodes = $data['bundle'] ?? [];

    //     return collect($postalCodes)->map(function ($item) {
    //         return [
    //             'type' => 'place',
    //             'place_type' => 'postal_code',
    //             'name' => $item['PostalCode'],
    //             'display_text' => $item['PostalCode'] . ($item['StateOrProvince'] ? ', ' . $item['StateOrProvince'] : ''),
    //             'action_url' => "/api/properties/search?postalCode=" . $item['PostalCode']
    //         ];
    //     });
    // }




    // public function autocomplete(Request $request)
    // {

    //     // Get search parameters
    //     $query = $request->input('q') ?? $request->input('query');
    //     $type = $request->input('type'); // 'buy' or 'rent'
    //     $limit = $request->input('limit', 10); // Default 10 suggestions total

    //     // Early return for empty queries
    //     if (empty($query) || strlen($query) < 1) {
    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => []
    //             ]
    //         ]);
    //     }

    //     // Bridge API configuration
    //     $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
    //     $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';

    //     try {
    //         // Make a single unified search request to get all types of results
    //         $params = [
    //             'access_token' => $accessToken,
    //             'limit' => $limit * 3, // Get enough results to cover all categories
    //             'fields' => 'ListingId,ListingKey,UnparsedAddress,StreetNumber,StreetName,StreetDirPrefix,UnitNumber,City,StateOrProvince,PostalCode,PropertySubType,PropertyType,ListPrice,StandardStatus,BuildingName',
    //             // Prioritize BuildingName in the search query
    //             'q' => "BuildingName like '*{$query}*' OR UnparsedAddress like '*{$query}*' OR StreetNumber like '*{$query}*' OR StreetName like '*{$query}*' OR City like '*{$query}*' OR StateOrProvince like '*{$query}*' OR PostalCode like '*{$query}*'"
    //         ];

    //         // Apply type filter if specified
    //         // Apply type filter if specified
    //         if ($type) {
    //             // dd($request->input('type'));
    //             if ($type === 'buy') {
    //                 $params['PropertyType'] = 'Residential';
    //                 $params['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
    //             } elseif ($type === 'rent') {
    //                 $params['PropertyType'] = 'Residential Lease';
    //                 $params['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
    //                 // $queryParams['PropertySubType'] = 'Rental';
    //             }
    //         }


    //         // Make the API request - DISABLE SSL VERIFICATION
    //         $response = Http::withOptions([
    //             'verify' => false, // Disable SSL verification
    //         ])->get($baseUrl, $params);

    //         if (!$response->successful()) {
    //             throw new \Exception('Failed to fetch suggestions: ' . $response->body());
    //         }

    //         $data = $response->json();
    //         dd($data);
    //         $properties = $data['bundle'] ?? [];

    //         // Add a debug log to see if the API is returning the building you're looking for
    //         \Log::info('Bridge API response for building search', [
    //             'query' => $query,
    //             'building_count' => count($properties),
    //             'buildings_with_name' => collect($properties)->filter(function ($p) use ($query) {
    //                 return !empty($p['BuildingName']) && stripos($p['BuildingName'], $query) !== false;
    //             })->pluck('BuildingName')->toArray()
    //         ]);

    //         // Process the results into different categories
    //         $addressSuggestions = [];
    //         $buildingGroups = [];
    //         $cities = [];
    //         $states = [];
    //         $postalCodes = [];

    //         // First pass: collect unique cities, states, postal codes
    //         foreach ($properties as $property) {
    //             // Add cities
    //             if (!empty($property['City']) && stripos($property['City'], $query) !== false) {
    //                 $cityKey = strtolower($property['City'] . '-' . ($property['StateOrProvince'] ?? ''));
    //                 if (!isset($cities[$cityKey])) {
    //                     $cities[$cityKey] = [
    //                         'type' => 'place',
    //                         'place_type' => 'city',
    //                         'name' => $property['City'],
    //                         'state' => $property['StateOrProvince'] ?? '',
    //                         'display_text' => $property['City'] . (isset($property['StateOrProvince']) ? ', ' . $property['StateOrProvince'] : ''),
    //                         'action_url' => "/properties/search?city=" . urlencode($property['City'])
    //                     ];
    //                 }
    //             }

    //             // Add states
    //             if (!empty($property['StateOrProvince']) && stripos($property['StateOrProvince'], $query) !== false) {
    //                 $stateKey = strtolower($property['StateOrProvince']);
    //                 if (!isset($states[$stateKey])) {
    //                     $fullStateName = $this->getFullStateName($property['StateOrProvince']);
    //                     $states[$stateKey] = [
    //                         'type' => 'place',
    //                         'place_type' => 'state',
    //                         'name' => $fullStateName ?? $property['StateOrProvince'],
    //                         'code' => $property['StateOrProvince'],
    //                         'display_text' => $fullStateName ?? $property['StateOrProvince'],
    //                         'action_url' => "/properties/search?state=" . urlencode($property['StateOrProvince'])
    //                     ];
    //                 }
    //             }

    //             // Add postal codes
    //             if (!empty($property['PostalCode']) && stripos($property['PostalCode'], $query) !== false) {
    //                 $postalKey = strtolower($property['PostalCode'] . '-' . ($property['StateOrProvince'] ?? ''));
    //                 if (!isset($postalCodes[$postalKey])) {
    //                     $postalCodes[$postalKey] = [
    //                         'type' => 'place',
    //                         'place_type' => 'postal_code',
    //                         'name' => $property['PostalCode'],
    //                         'display_text' => $property['PostalCode'] . (isset($property['StateOrProvince']) ? ', ' . $property['StateOrProvince'] : ''),
    //                         'action_url' => "/properties/search?postalCode=" . $property['PostalCode']
    //                     ];
    //                 }
    //             }

    //             // Group properties by address to identify buildings
    //             if (!empty($property['StreetNumber']) && !empty($property['StreetName'])) {
    //                 $addressKey = $property['StreetNumber'] . '-' . $property['StreetName'];

    //                 if (!isset($buildingGroups[$addressKey])) {
    //                     $buildingGroups[$addressKey] = [
    //                         'properties' => [],
    //                         'building_name' => $property['BuildingName'] ?? '',
    //                         'street_number' => $property['StreetNumber'],
    //                         'street_name' => $property['StreetName'],
    //                         'street_dir_prefix' => $property['StreetDirPrefix'] ?? '',
    //                         'city' => $property['City'] ?? '',
    //                         'state' => $property['StateOrProvince'] ?? '',
    //                         'postal_code' => $property['PostalCode'] ?? '',
    //                         'property_sub_type' => $property['PropertySubType'] ?? '',
    //                         'is_condo' => (isset($property['PropertySubType']) && $property['PropertySubType'] === 'Condominium'),
    //                         'matches_query' => false, // Flag to track if this building matches the query
    //                         'prices' => []
    //                     ];
    //                 }

    //                 $buildingGroups[$addressKey]['properties'][] = $property;

    //                 // Check if this property's building name matches the query
    //                 if (!empty($property['BuildingName']) && stripos($property['BuildingName'], $query) !== false) {
    //                     $buildingGroups[$addressKey]['matches_query'] = true;
    //                     $buildingGroups[$addressKey]['building_name'] = $property['BuildingName'];
    //                 }

    //                 // Check if this property is a Condominium
    //                 if (isset($property['PropertySubType']) && $property['PropertySubType'] === 'Condominium') {
    //                     $buildingGroups[$addressKey]['is_condo'] = true;
    //                     $buildingGroups[$addressKey]['property_sub_type'] = 'Condominium';
    //                 }

    //                 if (!empty($property['BuildingName'])) {
    //                     $buildingGroups[$addressKey]['building_name'] = $property['BuildingName'];
    //                 }

    //                 if (isset($property['ListPrice']) && $property['ListPrice'] > 0) {
    //                     $buildingGroups[$addressKey]['prices'][] = $property['ListPrice'];
    //                 }
    //             }

    //             // Add individual properties as address suggestions
    //             if (
    //                 !empty($property['UnparsedAddress']) &&
    //                 (
    //                     stripos($property['UnparsedAddress'], $query) !== false ||
    //                     (!empty($property['StreetNumber']) && stripos($property['StreetNumber'], $query) !== false) ||
    //                     (!empty($property['StreetName']) && stripos($property['StreetName'], $query) !== false)
    //                 )
    //             ) {
    //                 $address = $property['UnparsedAddress'] ?? '';
    //                 if (empty($address) && isset($property['StreetNumber'], $property['StreetName'])) {
    //                     $address = $property['StreetNumber'] . ' ' . $property['StreetName'];
    //                     if (!empty($property['UnitNumber'])) {
    //                         $address .= ' #' . $property['UnitNumber'];
    //                     }
    //                 }

    //                 $addressSuggestions[] = [
    //                     'type' => 'address',
    //                     'id' => $property['ListingId'] ?? null,
    //                     'listing_key' => $property['ListingKey'] ?? null,
    //                     'address' => $address,
    //                     'city' => $property['City'] ?? '',
    //                     'state' => $property['StateOrProvince'] ?? '',
    //                     'postal_code' => $property['PostalCode'] ?? '',
    //                     'property_type' => $property['PropertySubType'] ?? '',
    //                     'price' => $property['ListPrice'] ?? null,
    //                     'status' => $property['StandardStatus'] ?? '',
    //                     'display_text' => $address .
    //                         (isset($property['City']) ? ', ' . $property['City'] : '') .
    //                         (isset($property['StateOrProvince']) ? ', ' . $property['StateOrProvince'] : ''),
    //                     'action_url' => "/properties/" . ($property['ListingId'] ?? '')
    //                 ];
    //             }
    //         }

    //         // Process building groups to create building suggestions
    //         $buildingSuggestions = [];
    //         foreach ($buildingGroups as $key => $group) {
    //             // Only include as a building if:
    //             // 1. It's a Condominium, AND
    //             // 2. It has a building name OR multiple properties
    //             if ($group['is_condo'] && (!empty($group['building_name']) || count($group['properties']) > 1)) {
    //                 $address = trim($group['street_number'] . ' ' .
    //                     ($group['street_dir_prefix'] ? $group['street_dir_prefix'] . ' ' : '') .
    //                     $group['street_name']);

    //                 $buildingName = !empty($group['building_name']) ? $group['building_name'] : $address;

    //                 // Check if this building matches the search query
    //                 $nameMatches = !empty($buildingName) && stripos($buildingName, $query) !== false;
    //                 $addressMatches = stripos($address, $query) !== false || stripos($group['street_name'], $query) !== false;

    //                 // Only include if the building name or address matches the query
    //                 if ($nameMatches || $addressMatches) {
    //                     $prices = $group['prices'];
    //                     $minPrice = !empty($prices) ? min($prices) : null;
    //                     $maxPrice = !empty($prices) ? max($prices) : null;
    //                     $unitCount = count($group['properties']);

    //                     $buildingSuggestions[] = [
    //                         'type' => 'building',
    //                         'building_name' => $buildingName,
    //                         'street_number' => $group['street_number'],
    //                         'street_dir_prefix' => $group['street_dir_prefix'],
    //                         'street_name' => $group['street_name'],
    //                         'address' => $address,
    //                         'city' => $group['city'],
    //                         'state' => $group['state'],
    //                         'postal_code' => $group['postal_code'],
    //                         'property_sub_type' => 'Condominium',
    //                         'unit_count' => $unitCount,
    //                         'min_price' => $minPrice,
    //                         'max_price' => $maxPrice,
    //                         'display_text' => $buildingName .
    //                             ($group['city'] ? ', ' . $group['city'] : '') .
    //                             ($group['state'] ? ', ' . $group['state'] : '') .
    //                             ' (' . $unitCount . ' units)',
    //                         'action_url' => "/buildings?street_number={$group['street_number']}&street_name=" . urlencode($group['street_name'])
    //                     ];
    //                 }
    //             }
    //         }

    //         // Sort building suggestions to prioritize building name matches
    //         usort($buildingSuggestions, function ($a, $b) use ($query) {
    //             // Check if building names match the query exactly
    //             $aExactMatch = !empty($a['building_name']) && strtolower($a['building_name']) === strtolower($query);
    //             $bExactMatch = !empty($b['building_name']) && strtolower($b['building_name']) === strtolower($query);

    //             // Exact matches come first
    //             if ($aExactMatch && !$bExactMatch) return -1;
    //             if (!$aExactMatch && $bExactMatch) return 1;

    //             // Check if building names start with the query
    //             $aStartMatch = !empty($a['building_name']) && stripos($a['building_name'], $query) === 0;
    //             $bStartMatch = !empty($b['building_name']) && stripos($b['building_name'], $query) === 0;

    //             // Matches at the start of building name come next
    //             if ($aStartMatch && !$bStartMatch) return -1;
    //             if (!$aStartMatch && $bStartMatch) return 1;

    //             // Then partial building name matches
    //             $aPartialMatch = !empty($a['building_name']) && stripos($a['building_name'], $query) !== false;
    //             $bPartialMatch = !empty($b['building_name']) && stripos($b['building_name'], $query) !== false;

    //             if ($aPartialMatch && !$bPartialMatch) return -1;
    //             if (!$aPartialMatch && $bPartialMatch) return 1;

    //             // If both match equally, maintain original order
    //             return 0;
    //         });

    //         // Combine all place suggestions
    //         $placeSuggestions = array_merge(
    //             array_values($cities),
    //             array_values($states),
    //             array_values($postalCodes)
    //         );

    //         // Limit each category
    //         $addressLimit = ceil($limit * 0.4);
    //         $buildingLimit = ceil($limit * 0.3);
    //         $placeLimit = $limit - $addressLimit - $buildingLimit;

    //         $addressSuggestions = array_slice($addressSuggestions, 0, $addressLimit);
    //         $buildingSuggestions = array_slice($buildingSuggestions, 0, $buildingLimit);
    //         $placeSuggestions = array_slice($placeSuggestions, 0, $placeLimit);

    //         return response()->json([
    //             'suggestions' => [
    //                 'addresses' => $addressSuggestions,
    //                 'buildings' => $buildingSuggestions,
    //                 'places' => $placeSuggestions
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Bridge API autocomplete error', [
    //             'message' => $e->getMessage(),
    //             'query' => $query
    //         ]);

    //         return response()->json([
    //             'error' => 'Failed to fetch suggestions',
    //             'message' => $e->getMessage(),
    //             'suggestions' => [
    //                 'addresses' => [],
    //                 'buildings' => [],
    //                 'places' => []
    //             ]
    //         ], 500);
    //     }
    // }

public function autocomplete(Request $request)
{
    $query = $request->input('q') ?? $request->input('query');
    $type = $request->input('type');
    $limit = $request->input('limit', 5);

    if (empty($query) || strlen($query) < 1) {
        return response()->json([
            'suggestions' => [
                'addresses' => [],
                'buildings' => [],
                'places' => [],
            ]
        ]);
    }

    $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
    $directCityUrl = 'https://api.bridgedataoutput.com/api/v2/OData/miamire/Properties';
    $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';
    $fields = 'ListingId,ListingKey,UnparsedAddress,StreetNumber,StreetName,StreetDirPrefix,UnitNumber,City,StateOrProvince,PostalCode,PropertySubType,PropertyType,ListPrice,StandardStatus,BuildingName,BedroomsTotal,BathroomsTotalDecimal,LivingArea,Media';

    // Prepare parameters
    $sharedParams = [
        'access_token' => $accessToken,
        'fields' => $fields,
        'limit' => $limit,
    ];

    if ($type === 'buy') {
        $sharedParams['PropertyType'] = 'Residential';
        $sharedParams['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
    } elseif ($type === 'rent') {
        $sharedParams['PropertyType'] = 'Residential Lease';
        $sharedParams['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
    }

    // Start concurrent requests
    $responses = Http::pool(fn ($pool) => [
        'building' => $pool->as('building')->withOptions(['verify' => false])->get($baseUrl, array_merge($sharedParams, ['BuildingName' => "*{$query}*", 'PropertySubType' => 'Condominium'])),
        'address' => $pool->as('address')->withOptions(['verify' => false])->get($baseUrl, array_merge($sharedParams, ['UnparsedAddress' => "*{$query}*"])),
        'city'    => $pool->as('city')->withOptions(['verify' => false])->get($directCityUrl, [
            'access_token' => $accessToken,
            '$filter' => "contains(City,'{$query}')",
            '$select' => 'City,StateOrProvince',
            '$top' => 50,
            '$orderby' => 'City',
        ]),
        'state'   => $pool->as('state')->withOptions(['verify' => false])->get($baseUrl, [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'StateOrProvince',
            'StateOrProvince' => "*{$query}*",
            'groupBy' => 'StateOrProvince'
        ]),
        'postal'  => $pool->as('postal')->withOptions(['verify' => false])->get($baseUrl, [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'PostalCode,StateOrProvince',
            'PostalCode' => "*{$query}*",
            'groupBy' => 'PostalCode,StateOrProvince'
        ])
    ]);

    $buildingProperties = $responses['building']->successful() ? $responses['building']->json('bundle', []) : [];
    $addressProperties  = $responses['address']->successful() ? $responses['address']->json('bundle', []) : [];
    $cityResults        = $responses['city']->successful() ? $responses['city']->json('value', []) : [];
    $stateProperties    = $responses['state']->successful() ? $responses['state']->json('bundle', []) : [];
    $postalProperties   = $responses['postal']->successful() ? $responses['postal']->json('bundle', []) : [];

    // Helper for image
    $extractImage = function ($media) {
        foreach ($media ?? [] as $item) {
            if (($item['MediaCategory'] ?? '') === 'Image' && !empty($item['MediaURL'])) {
                return $item['MediaURL'];
            }
        }
        return null;
    };

    // BUILDING SUGGESTIONS
    $buildingSuggestions = [];
    foreach ($buildingProperties as $property) {
        $name = $property['BuildingName'] ?? '';
        if (!$name) continue;

        $key = strtolower($name . '-' . ($property['StreetNumber'] ?? '') . '-' . ($property['StreetName'] ?? ''));
        $image = $extractImage($property['Media'] ?? []);

        if (!isset($buildingSuggestions[$key])) {
            $address = trim(($property['StreetNumber'] ?? '') . ' ' . ($property['StreetDirPrefix'] ?? '') . ' ' . ($property['StreetName'] ?? ''));
            $buildingSuggestions[$key] = [
                'type' => 'building',
                'building_name' => $name,
                'address' => $address,
                'city' => $property['City'] ?? '',
                'state' => $property['StateOrProvince'] ?? '',
                'postal_code' => $property['PostalCode'] ?? '',
                'image_url' => $image,
                'prices' => [$property['ListPrice'] ?? 0],
                'min_price' => $property['ListPrice'] ?? 0,
                'max_price' => $property['ListPrice'] ?? 0,
                'display_text' => "{$name}, {$property['City']}, {$property['StateOrProvince']}",
                'action_url' => "/buildings?street_number={$property['StreetNumber']}&street_name=" . urlencode($property['StreetName'] ?? '')
            ];
        } else {
            $price = $property['ListPrice'] ?? 0;
            $suggestion = &$buildingSuggestions[$key];
            $suggestion['prices'][] = $price;
            if ($price > 0) {
                $suggestion['min_price'] = min($suggestion['min_price'], $price);
                $suggestion['max_price'] = max($suggestion['max_price'], $price);
            }
        }
    }

    // ADDRESS SUGGESTIONS
    $addressSuggestions = collect($addressProperties)->map(function ($property) use ($extractImage) {
        return [
            'type' => 'address',
            'address' => $property['UnparsedAddress'],
            'property_id'=> $property['ListingId'],
            'listing_id'=> $property['ListingId'],
            'city' => $property['City'] ?? '',
            'state' => $property['StateOrProvince'] ?? '',
            'postal_code' => $property['PostalCode'] ?? '',
            'price' => $property['ListPrice'] ?? null,
            'image_url' => $extractImage($property['Media'] ?? []),
            'display_text' => $property['UnparsedAddress'],
            'action_url' => "/properties/search?address=" . urlencode($property['UnparsedAddress']),
        ];
    })->filter()->values();

    // PLACE SUGGESTIONS
    $seenCities = [];
    $placeSuggestions = [];

    foreach ($cityResults as $city) {
        if (!empty($city['City']) && !in_array($city['City'], $seenCities)) {
            $seenCities[] = $city['City'];
            $placeSuggestions[] = [
                'type' => 'place',
                'place_type' => 'city',
                'name' => $city['City'],
                'state' => $city['StateOrProvince'] ?? '',
                'display_text' => "{$city['City']}, {$city['StateOrProvince']}",
                'action_url' => "/properties/search?city=" . urlencode($city['City']),
            ];
        }
    }

    foreach ($stateProperties as $state) {
        if (!empty($state['StateOrProvince'])) {
            $placeSuggestions[] = [
                'type' => 'place',
                'place_type' => 'state',
                'name' => $state['StateOrProvince'],
                'display_text' => $state['StateOrProvince'],
                'action_url' => "/properties/search?state=" . urlencode($state['StateOrProvince']),
            ];
        }
    }

    foreach ($postalProperties as $postal) {
        if (!empty($postal['PostalCode'])) {
            $placeSuggestions[] = [
                'type' => 'place',
                'place_type' => 'postal',
                'name' => $postal['PostalCode'],
                'state' => $postal['StateOrProvince'] ?? '',
                'display_text' => "{$postal['PostalCode']}, {$postal['StateOrProvince']}",
                'action_url' => "/properties/search?postal_code=" . urlencode($postal['PostalCode']),
            ];
        }
    }

    return response()->json([
        'suggestions' => [
            'addresses' => array_values($addressSuggestions->take($limit)->toArray()),
            'buildings' => array_values($buildingSuggestions),
            'places'    => array_slice($placeSuggestions, 0, $limit),
        ]
    ]);
}





    private function fetchAddressSuggestionsFromAPI($baseUrl, $accessToken, $query, $type, $limit)
    {
        // Build query parameters
        $params = [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'ListingId,ListingKey,UnparsedAddress,StreetNumber,StreetName,UnitNumber,City,StateOrProvince,PostalCode,PropertySubType,ListPrice,StandardStatus',
        ];

        // Add search query
        $params['q'] = "UnparsedAddress like '*{$query}*' OR StreetNumber eq '{$query}*' OR StreetName like '*{$query}*'";

        // Add property type filter if specified
        if ($type) {
            if (strtolower($type) === 'buy') {
                $params['PropertyType.ne'] = 'Residential Lease';
            } else if (strtolower($type) === 'rent') {
                $params['PropertyType'] = 'Residential Lease';
            }
        }

        // Make the API request - DISABLE SSL VERIFICATION
        // $response = Http::withOptions([
        //     'verify' => false, // Disable SSL verification
        // ])->get($baseUrl, $params);
            $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl, $params);

        // Log the response for debugging
        \Log::info('Bridge API address response', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500) // Log first 500 chars to avoid huge logs
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch address suggestions: ' . $response->body());
        }

        $data = $response->json();
        $properties = $data['bundle'] ?? [];

        return collect($properties)->map(function ($item) {
            $address = $item['UnparsedAddress'] ?? '';
            if (empty($address) && isset($item['StreetNumber'], $item['StreetName'])) {
                $address = $item['StreetNumber'] . ' ' . $item['StreetName'];
                if (!empty($item['UnitNumber'])) {
                    $address .= ' #' . $item['UnitNumber'];
                }
            }

            return [
                'type' => 'address',
                'id' => $item['ListingId'] ?? null,
                'listing_key' => $item['ListingKey'] ?? null,
                'address' => $address,
                'city' => $item['City'] ?? '',
                'state' => $item['StateOrProvince'] ?? '',
                'postal_code' => $item['PostalCode'] ?? '',
                'property_type' => $item['PropertySubType'] ?? '',
                'price' => $item['ListPrice'] ?? null,
                'status' => $item['StandardStatus'] ?? '',
                'display_text' => $address .
                    (isset($item['City']) ? ', ' . $item['City'] : '') .
                    (isset($item['StateOrProvince']) ? ', ' . $item['StateOrProvince'] : ''),
                'action_url' => "/properties/" . ($item['ListingId'] ?? '')
            ];
        })->values();
    }

    private function fetchBuildingSuggestionsFromAPI($baseUrl, $accessToken, $query, $type, $limit)
    {
        // Define property types that are typically individual properties
        $individualPropertyTypes = [
            'Land',
            'SingleFamilyResidence',
            'Business',
            'BusinessOpportunity',
            'UnimprovedLand',
            'Special Purpose'
        ];

        // Build query parameters for buildings
        $params = [
            'access_token' => $accessToken,
            'limit' => max(50, $limit * 3), // Get more results to ensure we can find buildings
            'fields' => 'ListingId,ListingKey,UnparsedAddress,StreetNumber,StreetName,StreetDirPrefix,UnitNumber,City,StateOrProvince,PostalCode,PropertySubType,PropertyType,ListPrice,BuildingName',
        ];

        // Add search query for buildings
        $params['q'] = "BuildingName like '*{$query}*' OR CONCAT(StreetNumber,' ',StreetName) like '*{$query}*' OR StreetName like '*{$query}*' OR City like '*{$query}*'";

        // Add property type filter if specified
        if ($type) {
            if (strtolower($type) === 'buy') {
                $params['PropertyType.ne'] = 'Residential Lease';
            } else if (strtolower($type) === 'rent') {
                $params['PropertyType'] = 'Residential Lease';
            }
        }

        // Make the API request - DISABLE SSL VERIFICATION
        $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl, $params);

        if (!$response->successful()) {
            \Log::warning('Failed to fetch building suggestions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return collect([]);
        }

        $data = $response->json();
        $properties = $data['bundle'] ?? [];

        // Group properties by address to identify buildings (multiple units at same address)
        $buildingGroups = collect($properties)->groupBy(function ($item) {
            // Create a key based on street number and name
            $streetNumber = $item['StreetNumber'] ?? '';
            $streetName = $item['StreetName'] ?? '';

            if (empty($streetNumber) || empty($streetName)) {
                return null; // Skip items without proper address
            }

            return $streetNumber . '-' . $streetName;
        })->filter(function ($group, $key) {
            return $key !== 'null' && count($group) > 1; // Only keep groups with multiple properties
        });

        // Format building groups into building suggestions
        $buildingSuggestions = $buildingGroups->map(function ($group, $key) {
            $firstProperty = $group->first();

            // Get building name if available, otherwise use address
            $buildingName = '';
            foreach ($group as $property) {
                if (!empty($property['BuildingName'])) {
                    $buildingName = $property['BuildingName'];
                    break;
                }
            }

            $streetNumber = $firstProperty['StreetNumber'] ?? '';
            $streetName = $firstProperty['StreetName'] ?? '';
            $streetDirPrefix = $firstProperty['StreetDirPrefix'] ?? '';

            $address = trim($streetNumber . ' ' .
                ($streetDirPrefix ? $streetDirPrefix . ' ' : '') .
                $streetName);

            if (empty($buildingName)) {
                $buildingName = $address;
            }

            // Calculate min and max prices
            $prices = $group->pluck('ListPrice')->filter()->values();
            $minPrice = $prices->min();
            $maxPrice = $prices->max();

            return [
                'type' => 'building',
                'building_name' => $buildingName,
                'street_number' => $streetNumber,
                'street_dir_prefix' => $streetDirPrefix,
                'street_name' => $streetName,
                'address' => $address,
                'city' => $firstProperty['City'] ?? '',
                'state' => $firstProperty['StateOrProvince'] ?? '',
                'postal_code' => $firstProperty['PostalCode'] ?? '',
                'property_sub_type' => $firstProperty['PropertySubType'] ?? '',
                'unit_count' => count($group),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'display_text' => $buildingName .
                    (isset($firstProperty['City']) ? ', ' . $firstProperty['City'] : '') .
                    (isset($firstProperty['StateOrProvince']) ? ', ' . $firstProperty['StateOrProvince'] : '') .
                    ' (' . count($group) . ' units)',
                'action_url' => "/buildings?street_number={$streetNumber}&street_name=" . urlencode($streetName)
            ];
        })->values()->take($limit);

        return $buildingSuggestions;
    }

    private function fetchPlaceSuggestionsFromAPI($baseUrl, $accessToken, $query, $limit)
    {
        // We'll make separate requests for cities, states, and postal codes
        $cityLimit = ceil($limit * 0.5);
        $stateLimit = ceil($limit * 0.25);
        $postalLimit = $limit - $cityLimit - $stateLimit;

        // 1. CITY SUGGESTIONS
        $citySuggestions = $this->fetchCitiesFromAPI($baseUrl, $accessToken, $query, $cityLimit);

        // 2. STATE SUGGESTIONS
        $stateSuggestions = $this->fetchStatesFromAPI($baseUrl, $accessToken, $query, $stateLimit);

        // 3. POSTAL CODE SUGGESTIONS
        $postalSuggestions = $this->fetchPostalCodesFromAPI($baseUrl, $accessToken, $query, $postalLimit);

        // Combine all place suggestions
        return $citySuggestions->concat($stateSuggestions)->concat($postalSuggestions)->values();
    }

    private function fetchCitiesFromAPI($baseUrl, $accessToken, $query, $limit)
    {
        $params = [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'City,StateOrProvince',
            'groupBy' => 'City,StateOrProvince',
            'q' => "City like '*{$query}*'"
        ];

        $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl, $params);

        if (!$response->successful()) {
            \Log::warning('Failed to fetch city suggestions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return collect([]);
        }

        $data = $response->json();
        $cities = $data['bundle'] ?? [];

        return collect($cities)->map(function ($item) {
            if (empty($item['City'])) return null;

            return [
                'type' => 'place',
                'place_type' => 'city',
                'name' => $item['City'],
                'state' => $item['StateOrProvince'] ?? '',
                'display_text' => $item['City'] . (isset($item['StateOrProvince']) ? ', ' . $item['StateOrProvince'] : ''),
                'action_url' => "/properties/search?city=" . urlencode($item['City'])
            ];
        })->filter()->values();
    }

    private function fetchStatesFromAPI($baseUrl, $accessToken, $query, $limit)
    {
        $params = [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'StateOrProvince',
            'groupBy' => 'StateOrProvince',
            'q' => "StateOrProvince like '*{$query}*'"
        ];

        $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl, $params);

        if (!$response->successful()) {
            \Log::warning('Failed to fetch state suggestions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return collect([]);
        }

        $data = $response->json();
        $states = $data['bundle'] ?? [];

        return collect($states)->map(function ($item) {
            if (empty($item['StateOrProvince'])) return null;

            $fullStateName = $this->getFullStateName($item['StateOrProvince']);

            return [
                'type' => 'place',
                'place_type' => 'state',
                'name' => $fullStateName ?? $item['StateOrProvince'],
                'code' => $item['StateOrProvince'],
                'display_text' => $fullStateName ?? $item['StateOrProvince'],
                'action_url' => "/properties/search?state=" . urlencode($item['StateOrProvince'])
            ];
        })->filter()->values();
    }

    private function fetchPostalCodesFromAPI($baseUrl, $accessToken, $query, $limit)
    {
        $params = [
            'access_token' => $accessToken,
            'limit' => $limit,
            'fields' => 'PostalCode,StateOrProvince',
            'groupBy' => 'PostalCode,StateOrProvince',
            'q' => "PostalCode like '*{$query}*'"
        ];

        $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl, $params);

        if (!$response->successful()) {
            \Log::warning('Failed to fetch postal code suggestions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return collect([]);
        }

        $data = $response->json();
        $postalCodes = $data['bundle'] ?? [];

        return collect($postalCodes)->map(function ($item) {
            if (empty($item['PostalCode'])) return null;

            return [
                'type' => 'place',
                'place_type' => 'postal_code',
                'name' => $item['PostalCode'],
                'display_text' => $item['PostalCode'] . (isset($item['StateOrProvince']) ? ', ' . $item['StateOrProvince'] : ''),
                'action_url' => "/properties/search?postalCode=" . $item['PostalCode']
            ];
        })->filter()->values();
    }



    // Async version of building suggestions
    // private function fetchBuildingSuggestionsAsync($baseUrl, $accessToken, $query, $type, $limit)
    // {
    //     // First, try to find buildings directly by searching for BuildingName
    //     $buildingQueryParams = [
    //         'access_token' => $accessToken,
    //         'limit' => 200, // Get more results to ensure we find buildings with names
    //         'fields' => 'ListingId,BuildingName,StreetNumber,StreetName,StreetDirPrefix,City,StateOrProvince,PostalCode,PropertySubType,PropertyType,ListPrice',
    //         // Prioritize BuildingName in the search query
    //         'q' => "BuildingName like '*{$query}*' OR CONCAT(StreetNumber,' ',StreetName) like '*{$query}*'"
    //     ];

    //     // Apply type filter if provided
    //     if ($type) {
    //         if (strtolower($type) === 'buy') {
    //             $buildingQueryParams['PropertyType.ne'] = 'Residential Lease';
    //         } elseif (strtolower($type) === 'rent') {
    //             $buildingQueryParams['PropertyType'] = 'Residential Lease';
    //         }
    //     }

    //     // Make the API request
    //     $response = Http::get($baseUrl, $buildingQueryParams);

    //     if (!$response->successful()) {
    //         Log::error('Bridge API request failed for building suggestions', [
    //             'status' => $response->status(),
    //             'response' => $response->json()
    //         ]);
    //         return collect([]);
    //     }

    //     $data = $response->json();
    //     $properties = $data['bundle'] ?? [];

    //     // Group properties by building name first
    //     $buildingGroups = [];

    //     // First pass: group by BuildingName when available
    //     foreach ($properties as $property) {
    //         if (!empty($property['BuildingName'])) {
    //             $buildingName = $property['BuildingName'];

    //             if (!isset($buildingGroups[$buildingName])) {
    //                 $buildingGroups[$buildingName] = [
    //                     'properties' => [],
    //                     'building_name' => $buildingName,
    //                     'address' => trim($property['StreetNumber'] . ' ' . $property['StreetName']),
    //                     'city' => $property['City'],
    //                     'state' => $property['StateOrProvince'],
    //                     'postal_code' => $property['PostalCode'] ?? '',
    //                     'street_number' => $property['StreetNumber'],
    //                     'street_name' => $property['StreetName'],
    //                     'street_dir_prefix' => $property['StreetDirPrefix'] ?? '',
    //                     'prices' => []
    //                 ];
    //             }

    //             $buildingGroups[$buildingName]['properties'][] = $property;

    //             if (isset($property['ListPrice']) && $property['ListPrice'] > 0) {
    //                 $buildingGroups[$buildingName]['prices'][] = $property['ListPrice'];
    //             }
    //         }
    //     }

    //     // Second pass: group by address for properties without BuildingName
    //     $addressGroups = [];

    //     foreach ($properties as $property) {
    //         // Skip if missing required fields
    //         if (!isset($property['StreetNumber']) || !isset($property['StreetName'])) {
    //             continue;
    //         }

    //         // Skip if this property was already grouped by BuildingName
    //         if (!empty($property['BuildingName']) && isset($buildingGroups[$property['BuildingName']])) {
    //             continue;
    //         }

    //         $addressKey = $property['StreetNumber'] . '-' . $property['StreetName'] . '-' . $property['City'];

    //         if (!isset($addressGroups[$addressKey])) {
    //             $addressGroups[$addressKey] = [
    //                 'properties' => [],
    //                 'building_name' => '', // Will be filled if any property has a BuildingName
    //                 'address' => trim($property['StreetNumber'] . ' ' . $property['StreetName']),
    //                 'city' => $property['City'],
    //                 'state' => $property['StateOrProvince'],
    //                 'postal_code' => $property['PostalCode'] ?? '',
    //                 'street_number' => $property['StreetNumber'],
    //                 'street_name' => $property['StreetName'],
    //                 'street_dir_prefix' => $property['StreetDirPrefix'] ?? '',
    //                 'prices' => []
    //             ];
    //         }

    //         // If this property has a BuildingName, use it for the whole address group
    //         if (!empty($property['BuildingName']) && empty($addressGroups[$addressKey]['building_name'])) {
    //             $addressGroups[$addressKey]['building_name'] = $property['BuildingName'];
    //         }

    //         $addressGroups[$addressKey]['properties'][] = $property;

    //         if (isset($property['ListPrice']) && $property['ListPrice'] > 0) {
    //             $addressGroups[$addressKey]['prices'][] = $property['ListPrice'];
    //         }
    //     }

    //     // Filter address groups to only include those with multiple properties (actual buildings)
    //     $addressGroups = array_filter($addressGroups, function($group) {
    //         return count($group['properties']) > 1;
    //     });

    //     // Combine the two sets of buildings
    //     $allBuildings = array_values($buildingGroups);

    //     // Add address-based buildings that have a building name
    //     foreach ($addressGroups as $group) {
    //         if (!empty($group['building_name'])) {
    //             $allBuildings[] = $group;
    //         }
    //     }

    //     // If we don't have enough results, add address-based buildings without names
    //     if (count($allBuildings) < $limit) {
    //         foreach ($addressGroups as $group) {
    //             if (empty($group['building_name'])) {
    //                 // For buildings without a name, try to create a descriptive name
    //                 $propertyTypes = array_unique(array_map(function($prop) {
    //                     return $prop['PropertySubType'] ?? '';
    //                 }, $group['properties']));

    //                 $commonType = !empty($propertyTypes) ? reset($propertyTypes) : '';

    //                 if (!empty($commonType)) {
    //                     // Create a name like "400 Main St Condominiums"
    //                     $group['building_name'] = $group['address'] . ' ' . $commonType;
    //                 } else {
    //                     // Just use the address
    //                     $group['building_name'] = $group['address'];
    //                 }

    //                 $allBuildings[] = $group;

    //                 if (count($allBuildings) >= $limit) {
    //                     break;
    //                 }
    //             }
    //         }
    //     }

    //     // Sort buildings by relevance to the query
    //     usort($allBuildings, function($a, $b) use ($query) {
    //         // Buildings with names that match the query come first
    //         $aNameMatch = stripos($a['building_name'], $query) !== false;
    //         $bNameMatch = stripos($b['building_name'], $query) !== false;

    //         if ($aNameMatch && !$bNameMatch) return -1;
    //         if (!$aNameMatch && $bNameMatch) return 1;

    //         // Then buildings with addresses that match the query
    //         $aAddressMatch = stripos($a['address'], $query) !== false;
    //         $bAddressMatch = stripos($b['address'], $query) !== false;

    //         if ($aAddressMatch && !$bAddressMatch) return -1;
    //         if (!$aAddressMatch && $bAddressMatch) return 1;

    //         // Then sort by number of units (more units first)
    //         return count($b['properties']) - count($a['properties']);
    //     });

    //     // Format the results
    //     $results = collect(array_slice($allBuildings, 0, $limit))->map(function($building) {
    //         $prices = $building['prices'];
    //         $minPrice = !empty($prices) ? min($prices) : null;
    //         $maxPrice = !empty($prices) ? max($prices) : null;

    //         return [
    //             'type' => 'building',
    //             'building_name' => $building['building_name'],
    //             'address' => $building['address'],
    //             'city' => $building['city'],
    //             'state' => $building['state'],
    //             'postal_code' => $building['postal_code'],
    //             'street_number' => $building['street_number'],
    //             'street_name' => $building['street_name'],
    //             'street_dir_prefix' => $building['street_dir_prefix'],
    //             'unit_count' => count($building['properties']),
    //             'min_price' => $minPrice,
    //             'max_price' => $maxPrice,
    //             'display_text' => $building['building_name'] . 
    //                 ($building['city'] ? ', ' . $building['city'] : '') . 
    //                 ($building['state'] ? ', ' . $building['state'] : '') . 
    //                 ' (' . count($building['properties']) . ' units)',
    //             'action_url' => "/api/buildings?street_number={$building['street_number']}&street_name=" . urlencode($building['street_name'])
    //         ];
    //     });

    //     return $results;
    // }

    // Async version of place suggestions
    // private function fetchPlaceSuggestionsAsync($baseUrl, $accessToken, $query, $limit)
    // {
    //     return function() use ($baseUrl, $accessToken, $query, $limit) {
    //         // Combine places into a single API call if possible
    //         $queryParams = [
    //             'access_token' => $accessToken,
    //             'limit' => $limit,
    //             'fields' => 'City,StateOrProvince,PostalCode',
    //             'groupBy' => 'City,StateOrProvince,PostalCode',
    //             'q' => "City like '{$query}*' OR StateOrProvince like '{$query}*' OR PostalCode like '{$query}*'"
    //         ];

    //         $response = Http::get($baseUrl, $queryParams);

    //         if (!$response->successful()) {
    //             Log::error('Bridge API request failed for place suggestions', [
    //                 'status' => $response->status(),
    //                 'response' => $response->json()
    //             ]);
    //             return [];
    //         }

    //         $data = $response->json();
    //         $places = $data['bundle'] ?? [];

    //         $results = [];
    //         $cityCount = 0;
    //         $stateCount = 0;
    //         $postalCount = 0;
    //         $cityLimit = ceil($limit / 3);
    //         $stateLimit = ceil($limit / 3);
    //         $postalLimit = $limit - $cityLimit - $stateLimit;

    //         // Process places by type
    //         foreach ($places as $place) {
    //             // Check if this is primarily a city match
    //             if (!empty($place['City']) && stripos($place['City'], $query) !== false && $cityCount < $cityLimit) {
    //                 $results[] = [
    //                     'type' => 'place',
    //                     'place_type' => 'city',
    //                     'name' => $place['City'],
    //                     'state' => $place['StateOrProvince'],
    //                     'display_text' => $place['City'] . ', ' . $place['StateOrProvince'],
    //                     'action_url' => "/api/properties/search?city=" . urlencode($place['City'])
    //                 ];
    //                 $cityCount++;
    //             }
    //             // Check if this is primarily a state match
    //             else if (!empty($place['StateOrProvince']) && stripos($place['StateOrProvince'], $query) !== false && $stateCount < $stateLimit) {
    //                 $fullStateName = $this->getFullStateName($place['StateOrProvince']);
    //                 $results[] = [
    //                     'type' => 'place',
    //                     'place_type' => 'state',
    //                     'name' => $fullStateName,
    //                     'code' => $place['StateOrProvince'],
    //                     'display_text' => $fullStateName,
    //                     'action_url' => "/api/properties/search?state=" . urlencode($place['StateOrProvince'])
    //                 ];
    //                 $stateCount++;
    //             }
    //             // Check if this is primarily a postal code match
    //             else if (!empty($place['PostalCode']) && stripos($place['PostalCode'], $query) !== false && $postalCount < $postalLimit) {
    //                 $results[] = [
    //                     'type' => 'place',
    //                     'place_type' => 'postal_code',
    //                     'name' => $place['PostalCode'],
    //                     'display_text' => $place['PostalCode'] . ', ' . $place['StateOrProvince'],
    //                     'action_url' => "/api/properties/search?postalCode=" . $place['PostalCode']
    //                 ];
    //                 $postalCount++;
    //             }
    //         }

    //         return $results;
    //     };
    // }


}
