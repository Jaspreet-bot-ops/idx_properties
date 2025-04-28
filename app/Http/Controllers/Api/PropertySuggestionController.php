<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertySuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Search for properties that match the query in various fields
        $properties = Property::where(function ($q) use ($query) {
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

    // public function suggestion(Request $request)
    // {
    //     // $query = $request->input('q');
    //     // $propertyType = $request->input('type');

    //     // $propertyQuery = Property::query();

    //     // // Filter by type
    //     // if ($propertyType) {
    //     //     switch (strtolower($propertyType)) {
    //     //         case 'buy':
    //     //             $propertyQuery->where('StandardStatus', 'Active');
    //     //             break;
    //     //         case 'rent':
    //     //             $propertyQuery->where('StandardStatus', 'Active')
    //     //                 ->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //     //             break;
    //     //     }
    //     // }

    //     // // Search filters
    //     // if ($query) {
    //     //     $propertyQuery->where(function ($q) use ($query) {
    //     //         $q->where('UnparsedAddress', 'like', "%{$query}%")
    //     //             ->orWhere('City', 'like', "%{$query}%")
    //     //             ->orWhere('StateOrProvince', 'like', "%{$query}%")
    //     //             ->orWhere('PostalCode', 'like', "%{$query}%");
    //     //     });
    //     // }

    //     // // Fetch properties
    //     // $properties = $propertyQuery->select([
    //     //     'id',
    //     //     'ListingKey',
    //     //     'UnparsedAddress',
    //     //     'City',
    //     //     'StateOrProvince',
    //     //     'PostalCode',
    //     //     'PropertyType',
    //     //     'PropertySubType',
    //     //     'StandardStatus',
    //     //     'ListPrice'
    //     // ])
    //     //     ->limit(10) // increased to allow more variety
    //     //     ->get();

    //     // $buildings = [];
    //     // $individualProperties = [];

    //     // foreach ($properties as $property) {
    //     //     // Extract base address (street number + name only)
    //     //     preg_match('/^\d+\s+[^\d]+/', $property->UnparsedAddress, $matches);
    //     //     $baseAddress = $matches[0] ?? $property->UnparsedAddress;

    //     //     // If it's "Land", treat it as individual property
    //     //     if (strtolower($property->PropertySubType) === 'land') {
    //     //         $individualProperties[] = $this->formatProperty($property);
    //     //     } else {
    //     //         $buildings[$baseAddress][] = $this->formatProperty($property);
    //     //     }
    //     // }

    //     // // Flatten building groups (optional: you could just return baseAddress and count)
    //     // $groupedBuildings = array_values($buildings);

    //     // return response()->json([
    //     //     'buildings' => $groupedBuildings,
    //     //     'properties' => $individualProperties,
    //     // ]);

    //     $query = strtolower(trim($request->input('q')));
    //     $propertyType = $request->input('type'); // 'buy' or 'rent'

    //     $baseQuery = Property::query();

    //     if ($propertyType === 'buy') {
    //         $baseQuery->where('StandardStatus', 'Active');
    //     } elseif ($propertyType === 'rent') {
    //         $baseQuery->where('StandardStatus', 'Active')
    //             ->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //     }

    //     // Filter for search term in street and city fields
    //     $filtered = (clone $baseQuery)
    //         ->where(function ($q) use ($query) {
    //             $q->whereRaw('LOWER(UnparsedAddress) LIKE ?', ["%{$query}%"])
    //                 ->orWhereRaw("LOWER(CONCAT(StreetNumber, ' ', StreetName)) LIKE ?", ["%{$query}%"]);
    //         });

    //     // Get individual unit-level properties
    //     $properties = (clone $filtered)
    //         ->select('id', 'UnparsedAddress', 'City', 'StateOrProvince', 'PostalCode')
    //         ->orderBy('UnparsedAddress')
    //         ->limit(50)
    //         ->get();

    //     // Get unique buildings using StreetNumber + StreetName
    //     $buildings = (clone $filtered)
    //         ->selectRaw("MIN(id) as id, CONCAT(StreetNumber, ' ', StreetName) as StreetAddress, City, StateOrProvince, PostalCode")
    //         ->groupBy('StreetAddress', 'City', 'StateOrProvince', 'PostalCode')
    //         ->limit(50) // limit building results if needed
    //         ->get();

    //     return response()->json([
    //         'properties' => $properties,
    //         'buildings' => $buildings,
    //     ]);
    // }

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

    /**
     * Get address autocomplete suggestions
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function autocomplete(Request $request)
    // {
    //     // Validate request
    //     $request->validate([
    //         'query' => 'required|string|min:2',
    //         'limit' => 'nullable|integer|min:1|max:20',
    //     ]);

    //     $query = $request->input('query');
    //     $limit = $request->input('limit', 10);

    //     // Define property types that are typically individual properties
    //     $individualPropertyTypes = [
    //         'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
    //         'UnimprovedLand', 'Special Purpose'
    //     ];

    //     // Get building suggestions - only select needed columns
    //     $buildingSuggestions = DB::table('properties')
    //         ->select(
    //             'StreetNumber',
    //             'StreetName',
    //             'City',
    //             'StateOrProvince',
    //             'PostalCode',
    //             DB::raw('COUNT(*) as unit_count'),
    //             DB::raw('MIN(ListPrice) as min_price'),
    //             DB::raw('MAX(ListPrice) as max_price')
    //         )
    //         ->where('StandardStatus', 'Active')
    //         ->whereNotNull('StreetNumber')
    //         ->whereNotNull('StreetName')
    //         ->whereNotIn('PropertySubType', $individualPropertyTypes)
    //         ->where(function ($q) use ($query) {
    //             $q->where('UnparsedAddress', 'like', "%{$query}%")
    //                 ->orWhere('StreetNumber', 'like', "%{$query}%")
    //                 ->orWhere('StreetName', 'like', "%{$query}%")
    //                 ->orWhere('City', 'like', "%{$query}%")
    //                 ->orWhere('StateOrProvince', 'like', "%{$query}%")
    //                 ->orWhere('PostalCode', 'like', "%{$query}%")
    //                 ->orWhere('BuildingName', 'like', "%{$query}%");
    //         })
    //         ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode')
    //         ->havingRaw('COUNT(*) > 1')
    //         ->limit(ceil($limit / 2))
    //         ->get();

    //     // Format building suggestions
    //     $formattedBuildingSuggestions = $buildingSuggestions->map(function ($item) {
    //         return [
    //             'type' => 'building',
    //             'address' => trim($item->StreetNumber . ' ' . $item->StreetName),
    //             'city' => $item->City,
    //             'state' => $item->StateOrProvince,
    //             'postal_code' => $item->PostalCode,
    //             'unit_count' => $item->unit_count,
    //             'min_price' => $item->min_price,
    //             'max_price' => $item->max_price,
    //             'display_text' => trim($item->StreetNumber . ' ' . $item->StreetName) .
    //                 ($item->City ? ', ' . $item->City : '') .
    //                 ($item->StateOrProvince ? ', ' . $item->StateOrProvince : '') .
    //                 ' (' . $item->unit_count . ' units)'
    //         ];
    //     });

    //     // Get individual property suggestions
    //     $propertySuggestions = Property::select(
    //         'id',
    //         'ListingKey',
    //         'StreetNumber',
    //         'StreetName',
    //         'City',
    //         'StateOrProvince',
    //         'PostalCode',
    //         'PropertySubType',
    //         'ListPrice'
    //     )
    //         ->where('StandardStatus', 'Active')
    //         ->where(function ($q) use ($individualPropertyTypes) {
    //             $q->whereIn('PropertySubType', $individualPropertyTypes)
    //                 ->orWhereRaw("(StreetNumber, StreetName) IN (
    //                   SELECT StreetNumber, StreetName 
    //                   FROM (
    //                       SELECT StreetNumber, StreetName, COUNT(*) as count
    //                       FROM properties 
    //                       WHERE StreetNumber IS NOT NULL AND StreetName IS NOT NULL
    //                       GROUP BY StreetNumber, StreetName
    //                       HAVING count = 1
    //                   ) as single_address_properties
    //               )");
    //         })
    //         ->where(function ($q) use ($query) {
    //             $q->where('UnparsedAddress', 'like', "%{$query}%")
    //                 ->orWhere('StreetNumber', 'like', "%{$query}%")
    //                 ->orWhere('StreetName', 'like', "%{$query}%")
    //                 ->orWhere('City', 'like', "%{$query}%")
    //                 ->orWhere('StateOrProvince', 'like', "%{$query}%")
    //                 ->orWhere('PostalCode', 'like', "%{$query}%");
    //         })
    //         ->limit(floor($limit / 2))
    //         ->get();

    //     // Format property suggestions
    //     $formattedPropertySuggestions = $propertySuggestions->map(function ($item) {
    //         return [
    //             'type' => 'property',
    //             'id' => $item->id,
    //             'listing_key' => $item->ListingKey,
    //             'address' => trim($item->StreetNumber . ' ' . $item->StreetName),
    //             'city' => $item->City,
    //             'state' => $item->StateOrProvince,
    //             'postal_code' => $item->PostalCode,
    //             'property_type' => $item->PropertySubType,
    //             'price' => $item->ListPrice,
    //             'display_text' => trim($item->StreetNumber . ' ' . $item->StreetName) .
    //                 ($item->City ? ', ' . $item->City : '') .
    //                 ($item->StateOrProvince ? ', ' . $item->StateOrProvince : '') .
    //                 ' (' . $item->PropertySubType . ')'
    //         ];
    //     });

    //     // Combine and return results
    //     $suggestions = collect(array_merge($formattedBuildingSuggestions->toArray(), $formattedPropertySuggestions->toArray()))
    //         ->sortBy(function ($item) use ($query) {
    //             // Sort by relevance - items that start with the query should come first
    //             $address = $item['address'];
    //             if (stripos($address, $query) === 0) {
    //                 return 0; // Highest priority if address starts with query
    //             } else if (stripos($address, $query) !== false) {
    //                 return 1; // Medium priority if address contains query
    //             } else {
    //                 return 2; // Lowest priority for other matches
    //             }
    //         })
    //         ->values()
    //         ->take($limit);

    //     return response()->json([
    //         'suggestions' => $suggestions
    //     ]);
    // }


    /**
     * Get address autocomplete suggestions categorized by type
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        // Validate request
        $request->validate([
            'query' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('query');
        $limit = $request->input('limit', 15); // Default 15 suggestions total

        // Allocate limits for each category
        $addressLimit = ceil($limit * 0.4); // 40% for addresses
        $buildingLimit = ceil($limit * 0.3); // 30% for buildings
        $placeLimit = ceil($limit * 0.3); // 30% for places

        // Define property types that are typically individual properties
        $individualPropertyTypes = [
            'Land', 'SingleFamilyResidence', 'Business', 'BusinessOpportunity',
            'UnimprovedLand', 'Special Purpose'
        ];

        // 1. ADDRESS SUGGESTIONS - Individual property addresses
        $addressSuggestions = Property::select(
            'id',
            'ListingKey',
            'StreetNumber',
            'StreetName',
            'UnitNumber',
            'City',
            'StateOrProvince',
            'PostalCode',
            'PropertySubType',
            'ListPrice'
        )
            ->where('StandardStatus', 'Active')
            ->where(function ($q) use ($query) {
                $q->where('UnparsedAddress', 'like', "%{$query}%")
                    ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$query}%")
                    ->orWhere('StreetNumber', 'like', "{$query}%")
                    ->orWhere('StreetName', 'like', "%{$query}%");
            })
            ->limit($addressLimit)
            ->get()
            ->map(function ($item) {
                $address = trim($item->StreetNumber . ' ' . $item->StreetName);
                if (!empty($item->UnitNumber)) {
                    $address .= ' #' . $item->UnitNumber;
                }

                return [
                    'type' => 'address',
                    'id' => $item->id,
                    'listing_key' => $item->ListingKey,
                    'address' => $address,
                    'city' => $item->City,
                    'state' => $item->StateOrProvince,
                    'postal_code' => $item->PostalCode,
                    'property_type' => $item->PropertySubType,
                    'price' => $item->ListPrice,
                    'display_text' => $address .
                        ($item->City ? ', ' . $item->City : '') .
                        ($item->StateOrProvince ? ', ' . $item->StateOrProvince : ''),
                    'action_url' => "/api/properties/{$item->id}"
                ];
            });

        // 2. BUILDING SUGGESTIONS - Multi-unit buildings
        $buildingSuggestions = DB::table('properties')
            ->select(
                'StreetNumber',
                'StreetName',
                'City',
                'StateOrProvince',
                'PostalCode',
                'BuildingName',
                DB::raw('COUNT(*) as unit_count'),
                DB::raw('MIN(ListPrice) as min_price'),
                DB::raw('MAX(ListPrice) as max_price')
            )
            ->where('StandardStatus', 'Active')
            ->whereNotNull('StreetNumber')
            ->whereNotNull('StreetName')
            ->whereNotIn('PropertySubType', $individualPropertyTypes)
            ->where(function ($q) use ($query) {
                $q->where('UnparsedAddress', 'like', "%{$query}%")
                    ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$query}%")
                    ->orWhere('StreetNumber', 'like', "{$query}%")
                    ->orWhere('StreetName', 'like', "%{$query}%")
                    ->orWhere('BuildingName', 'like', "%{$query}%");
            })
            ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode', 'BuildingName')
            ->havingRaw('COUNT(*) > 1')
            ->limit($buildingLimit)
            ->get()
            ->map(function ($item) {
                $buildingName = !empty($item->BuildingName) ? $item->BuildingName : trim($item->StreetNumber . ' ' . $item->StreetName);

                return [
                    'type' => 'building',
                    'building_name' => $buildingName,
                    'street_number' => $item->StreetNumber,
                    'street_name' => $item->StreetName,
                    'address' => trim($item->StreetNumber . ' ' . $item->StreetName),
                    'city' => $item->City,
                    'state' => $item->StateOrProvince,
                    'postal_code' => $item->PostalCode,
                    'unit_count' => $item->unit_count,
                    'min_price' => $item->min_price,
                    'max_price' => $item->max_price,
                    'display_text' => $buildingName .
                        ($item->City ? ', ' . $item->City : '') .
                        ($item->StateOrProvince ? ', ' . $item->StateOrProvince : '') .
                        ' (' . $item->unit_count . ' units)',
                    'action_url' => "/api/buildings?street_number={$item->StreetNumber}&street_name=" . urlencode($item->StreetName)
                ];
            });

        // 3. PLACE SUGGESTIONS - Cities and States
        // First get unique cities
        $citySuggestions = DB::table('properties')
            ->select('City', 'StateOrProvince')
            ->where('City', 'like', "%{$query}%")
            ->whereNotNull('City')
            ->where('City', '!=', '')
            ->groupBy('City', 'StateOrProvince')
            ->limit($placeLimit)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'place',
                    'place_type' => 'city',
                    'name' => $item->City,
                    'state' => $item->StateOrProvince,
                    'display_text' => $item->City . ($item->StateOrProvince ? ', ' . $item->StateOrProvince : ''),
                    'action_url' => "/api/properties/search?city=" . urlencode($item->City)
                ];
            });

        // Then get states
        $stateSuggestions = DB::table('properties')
            ->select('StateOrProvince')
            ->where('StateOrProvince', 'like', "%{$query}%")
            ->whereNotNull('StateOrProvince')
            ->where('StateOrProvince', '!=', '')
            ->groupBy('StateOrProvince')
            ->limit($placeLimit)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'place',
                    'place_type' => 'state',
                    'name' => $item->StateOrProvince,
                    'display_text' => $item->StateOrProvince,
                    'action_url' => "/api/properties/search?state=" . urlencode($item->StateOrProvince)
                ];
            });

        // Combine city and state suggestions
        $placeSuggestions = $citySuggestions->concat($stateSuggestions)->take($placeLimit);

        // Combine all suggestions
        $allSuggestions = collect([])
            ->concat($addressSuggestions)
            ->concat($buildingSuggestions)
            ->concat($placeSuggestions)
            ->sortBy(function ($item) use ($query) {
                // Sort by relevance - items that start with the query should come first
                $searchableText = $item['type'] === 'address' || $item['type'] === 'building'
                    ? $item['address']
                    : $item['name'];

                if (stripos($searchableText, $query) === 0) {
                    return 0; // Highest priority if text starts with query
                } else if (stripos($searchableText, $query) !== false) {
                    return 1; // Medium priority if text contains query
                } else {
                    return 2; // Lowest priority for other matches
                }
            })
            ->values()
            ->take($limit);

        // Group suggestions by type
        $groupedSuggestions = [
            'addresses' => $allSuggestions->where('type', 'address')->values(),
            'buildings' => $allSuggestions->where('type', 'building')->values(),
            'places' => $allSuggestions->where('type', 'place')->values()
        ];

        return response()->json([
            'suggestions' => $groupedSuggestions
        ]);
    }
}