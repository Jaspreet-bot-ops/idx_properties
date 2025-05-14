<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BridgeProperty;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
            'UnimprovedLand', 'Special Purpose'
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

    public function autocomplete(Request $request)
{
    // Get search parameters
    $query = $request->input('q') ?? $request->input('query');
    $type = $request->input('type'); // 'buy' or 'rent'
    $limit = $request->input('limit', 15); // Default 15 suggestions total
    
    // Early return for empty queries
    if (empty($query) || strlen($query) < 1) {
        return response()->json([
            'suggestions' => [
                'addresses' => [],
                'buildings' => [],
                'places' => []
            ]
        ]);
    }
    
    // Allocate limits for each category
    $addressLimit = ceil($limit * 0.4); // 40% for addresses
    $buildingLimit = ceil($limit * 0.3); // 30% for buildings
    $placeLimit = ceil($limit * 0.3); // 30% for places
    
    // Define property types that are typically individual properties
    $individualPropertyTypes = [
        'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
        'UnimprovedLand', 'Special Purpose'
    ];
    
    // Cache key based on request parameters
    $cacheKey = "autocomplete:{$query}:{$type}:{$limit}";
    
    // Try to get results from cache first (with 1 hour expiration)
    return Cache::remember($cacheKey, 3600, function() use (
        $query, $type, $limit, $addressLimit, $buildingLimit, $placeLimit, $individualPropertyTypes
    ) {
        // 1. ADDRESS SUGGESTIONS - Use indexed columns and limit the WHERE conditions
        $addressQuery = BridgeProperty::select(
            'id', 'listing_key', 'street_number', 'street_name', 'unit_number',
            'city', 'state_or_province', 'postal_code', 'property_sub_type',
            'list_price', 'standard_status'
        );
        
        // Apply type filter if provided
        if ($type) {
            if (strtolower($type) === 'buy') {
                $addressQuery->where('property_type', 'not like', '%Lease%');
            } else if (strtolower($type) === 'rent') {
                $addressQuery->where('property_type', 'like', '%Lease%');
            }
        }
        
        // Optimize the search conditions - focus on the most important fields first
        // Use LIKE with right wildcard for better index usage
        $addressQuery->where(function ($q) use ($query) {
            $q->where('unparsed_address', 'like', "{$query}%")
              ->orWhere(DB::raw("CONCAT(street_number, ' ', street_name)"), 'like', "{$query}%")
              ->orWhere('street_name', 'like', "{$query}%")
              ->orWhere('city', 'like', "{$query}%");
        });
        
        // Use simpler ordering that can leverage indexes
        $addressSuggestions = $addressQuery
            ->orderBy('unparsed_address')
            ->limit($addressLimit)
            ->get()
            ->map(function ($item) {
                $address = trim($item->street_number . ' ' . $item->street_name);
                if (!empty($item->unit_number)) {
                    $address .= ' #' . $item->unit_number;
                }
                return [
                    'type' => 'address',
                    'id' => $item->id,
                    'listing_key' => $item->listing_key,
                    'address' => $address,
                    'city' => $item->city,
                    'state' => $item->state_or_province,
                    'postal_code' => $item->postal_code,
                    'property_type' => $item->property_sub_type,
                    'price' => $item->list_price,
                    'status' => $item->standard_status,
                    'display_text' => $address .
                        ($item->city ? ', ' . $item->city : '') .
                        ($item->state_or_province ? ', ' . $item->state_or_province : ''),
                    'action_url' => "/api/properties/{$item->id}"
                ];
            });
        
        // 2. BUILDING SUGGESTIONS - Optimize the complex query
        $buildingQuery = DB::table('bridge_properties as bp')
            ->select(
                'bp.street_number',
                'bp.street_name',
                'bp.street_dir_prefix',
                'bp.city',
                'bp.state_or_province',
                'bp.postal_code',
                'bp.property_sub_type',
                DB::raw('MAX(bpd.building_name) as building_name'),
                DB::raw('COUNT(*) as unit_count'),
                DB::raw('MIN(bp.list_price) as min_price'),
                DB::raw('MAX(bp.list_price) as max_price')
            )
            ->leftJoin('bridge_property_details as bpd', 'bp.id', '=', 'bpd.property_id')
            ->whereNotNull('bp.street_number')
            ->whereNotNull('bp.street_name')
            ->whereNotIn('bp.property_sub_type', $individualPropertyTypes);
        
        // Apply type filter to buildings
        if ($type) {
            if (strtolower($type) === 'buy') {
                $buildingQuery->where('bp.property_type', 'not like', '%Lease%');
            } else if (strtolower($type) === 'rent') {
                $buildingQuery->where('bp.property_type', 'like', '%Lease%');
            }
        }
        
        // Optimize the search conditions
        $buildingQuery->where(function ($q) use ($query) {
            $q->where(DB::raw("CONCAT(bp.street_number, ' ', bp.street_name)"), 'like', "{$query}%")
              ->orWhere('bpd.building_name', 'like', "{$query}%")
              ->orWhere('bp.street_name', 'like', "{$query}%")
              ->orWhere('bp.city', 'like', "{$query}%");
        });
        
        $buildingSuggestions = $buildingQuery
            ->groupBy(
                'bp.street_number',
                'bp.street_name',
                'bp.street_dir_prefix',
                'bp.city',
                'bp.state_or_province',
                'bp.postal_code',
                'bp.property_sub_type'
            )
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('bp.street_name')
            ->limit($buildingLimit)
            ->get()
            ->map(function ($item) {
                $address = trim($item->street_number . ' ' .
                    ($item->street_dir_prefix ? $item->street_dir_prefix . ' ' : '') .
                    $item->street_name);
                $buildingName = !empty($item->building_name) ? $item->building_name : $address;
                return [
                    'type' => 'building',
                    'building_name' => $buildingName,
                    'street_number' => $item->street_number,
                    'street_dir_prefix' => $item->street_dir_prefix,
                    'street_name' => $item->street_name,
                    'address' => $address,
                    'city' => $item->city,
                    'state' => $item->state_or_province,
                    'postal_code' => $item->postal_code,
                    'property_sub_type' => $item->property_sub_type,
                    'unit_count' => $item->unit_count,
                    'min_price' => $item->min_price,
                    'max_price' => $item->max_price,
                    'display_text' => $buildingName .
                        ($item->city ? ', ' . $item->city : '') .
                        ($item->state_or_province ? ', ' . $item->state_or_province : '') .
                        ' (' . $item->unit_count . ' units)',
                    'action_url' => "/api/buildings?street_number={$item->street_number}&street_name=" . urlencode($item->street_name)
                ];
            });
        
        // 3. PLACE SUGGESTIONS - Run these queries in parallel using Promise-like approach
        $placeQueries = [
            'city' => function() use ($query, $placeLimit) {
                return DB::table('bridge_properties')
                    ->select('city', 'state_or_province')
                    ->where('city', 'like', "{$query}%")
                    ->whereNotNull('city')
                    ->where('city', '!=', '')
                    ->where('city', '!=', ',')
                    ->groupBy('city', 'state_or_province')
                    ->orderBy('city')
                    ->limit($placeLimit)
                    ->get();
            },
            'state' => function() use ($query, $placeLimit) {
                return DB::table('bridge_properties')
                    ->select('state_or_province')
                    ->where('state_or_province', 'like', "{$query}%")
                    ->whereNotNull('state_or_province')
                    ->where('state_or_province', '!=', '')
                    ->groupBy('state_or_province')
                    ->orderBy('state_or_province')
                    ->limit($placeLimit)
                    ->get();
            },
            'postal' => function() use ($query, $placeLimit) {
                return DB::table('bridge_properties')
                    ->select('postal_code', 'state_or_province')
                    ->where('postal_code', 'like', "{$query}%")
                    ->whereNotNull('postal_code')
                    ->where('postal_code', '!=', '')
                    ->groupBy('postal_code', 'state_or_province')
                    ->orderBy('postal_code')
                    ->limit($placeLimit)
                    ->get();
            },
        ];
        
        // Execute place queries
        $cityResults = $placeQueries['city']();
        $stateResults = $placeQueries['state']();
        $postalResults = $placeQueries['postal']();
        
        // Map city results
        $citySuggestions = $cityResults->map(function ($item) {
            return [
                'type' => 'place',
                'place_type' => 'city',
                'name' => $item->city,
                'state' => $item->state_or_province,
                'display_text' => $item->city . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
                'action_url' => "/api/properties/search?city=" . urlencode($item->city)
            ];
        });
        
        // Map state results
        $stateSuggestions = $stateResults->map(function ($item) {
            $fullStateName = $this->getFullStateName($item->state_or_province);
            return [
                'type' => 'place',
                'place_type' => 'state',
                'name' => $fullStateName,
                'code' => $item->state_or_province,
                'display_text' => $fullStateName,
                'action_url' => "/api/properties/search?state=" . urlencode($item->state_or_province)
            ];
        });
        
        // Map postal code results
        $postalCodeSuggestions = $postalResults->map(function ($item) {
            return [
                'type' => 'place',
                'place_type' => 'postal_code',
                'name' => $item->postal_code,
                'display_text' => $item->postal_code . ($item->state_or_province ? ', ' . $item->state_or_province : ''),
                'action_url' => "/api/properties/search?postalCode=" . $item->postal_code
            ];
        });
        
        // Combine place suggestions
        $placeSuggestions = $citySuggestions->concat($stateSuggestions)->concat($postalCodeSuggestions)->take($placeLimit);
        
        // Group suggestions by type
        $groupedSuggestions = [
            'addresses' => $addressSuggestions->values(),
            'buildings' => $buildingSuggestions->values(),
            'places' => $placeSuggestions->values()
        ];
        
        return [
            'suggestions' => $groupedSuggestions
        ];
    });
}

}
