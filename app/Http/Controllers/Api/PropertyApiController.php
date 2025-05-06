<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyApiController extends Controller
{
    public function getNewDevelopments(Request $request)
    {
        // Start with a query to group new developments by address
        $buildingsQuery = DB::table('properties')
            ->select(
                'StreetNumber',
                'StreetName',
                'City',
                'StateOrProvince',
                'PostalCode',
                'PropertySubType',
                'YearBuilt',
                DB::raw('COUNT(*) as unit_count'),
                DB::raw('MIN(ListPrice) as min_price'),
                DB::raw('MAX(ListPrice) as max_price'),
                DB::raw('MIN(id) as representative_id') // Get one property ID to represent the building
            )
            ->where('YearBuilt', '>', 2024)
            ->where('StandardStatus', 'Active')
            ->whereNotNull('StreetNumber')
            ->whereNotNull('StreetName')
            ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode', 'PropertySubType', 'YearBuilt');

        // Apply ordering if requested
        if ($request->has('orderby')) {
            $orderBy = $request->input('orderby');
            $direction = 'asc';

            if (strpos($orderBy, ' desc') !== false) {
                $orderBy = str_replace(' desc', '', $orderBy);
                $direction = 'desc';
            }

            // Map the order column to an appropriate aggregate function if needed
            switch ($orderBy) {
                case 'ListPrice':
                    $buildingsQuery->orderBy('min_price', $direction);
                    break;
                case 'unit_count':
                    $buildingsQuery->orderBy(DB::raw('COUNT(*)'), $direction);
                    break;
                default:
                    // For other columns, try to order by them directly
                    $buildingsQuery->orderBy($orderBy, $direction);
                    break;
            }
        } else {
            // Default ordering - prioritize condominiums and newer buildings
            $buildingsQuery->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
                ->orderBy('YearBuilt', 'desc');
            // Don't use unit_count in the default ordering as it causes issues with count()
        }

        // Get the total count before pagination - use a separate query without the problematic ORDER BY
        $countQuery = DB::table('properties')
            ->select(DB::raw('COUNT(DISTINCT CONCAT(StreetNumber, StreetName, City, StateOrProvince, PostalCode)) as total'))
            ->where('YearBuilt', '>', 2024)
            ->where('StandardStatus', 'Active')
            ->whereNotNull('StreetNumber')
            ->whereNotNull('StreetName');

        $totalCount = $countQuery->first()->total;

        // Now add the unit_count ordering for the main query if needed
        if (!$request->has('orderby')) {
            // Add unit_count ordering only for the main query, not for the count
            $buildingsQuery->orderBy(DB::raw('COUNT(*)'), 'desc');
        }

        // Handle pagination parameters
        $limit = $request->input('limit', 10); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to first page
        $offset = ($page - 1) * $limit; // Calculate the offset

        // Apply limit and offset
        $buildings = $buildingsQuery->skip($offset)->take($limit)->get();

        // Get the representative property IDs
        $representativeIds = $buildings->pluck('representative_id')->toArray();

        // Fetch the representative properties with their relationships
        $representativeProperties = Property::with(['details', 'media', 'amenities', 'schools', 'financialDetails'])
            ->whereIn('id', $representativeIds)
            ->get()
            ->keyBy('id'); // Index by ID for easier lookup

        // Format the building data with representative property information
        $formattedBuildings = $buildings->map(function ($building) use ($representativeProperties) {
            // Get the representative property
            $property = $representativeProperties[$building->representative_id] ?? null;

            // Create a building name from the address
            $buildingName = trim($building->StreetNumber . ' ' . $building->StreetName);

            // Get a representative image URL if available
            $imageUrl = null;
            if ($property && isset($property->media) && $property->media->isNotEmpty()) {
                $imageUrl = $property->media->first()->MediaURL ?? null;
            }

            // Clean up city value
            $city = ($building->City == ',' || empty($building->City)) ? null : $building->City;

            // Create the formatted building data
            $formattedBuilding = [
                'id' => $building->representative_id,
                'building_name' => $buildingName,
                'address' => $buildingName,
                'full_address' => $building->StreetNumber . ' ' .  $building->StreetName .
                    ($city ? ', ' . $city : '') .
                    ($building->StateOrProvince ? ', ' . $building->StateOrProvince : '') .
                    ($building->PostalCode ? ' ' . $building->PostalCode : ''),
                'city' => $city,
                'state' => $building->StateOrProvince,
                'postal_code' => $building->PostalCode,
                'property_subtype' => $building->PropertySubType,
                'year_built' => $building->YearBuilt,
                'unit_count' => $building->unit_count,
                'price_range' => [
                    'min' => $building->min_price,
                    'max' => $building->max_price
                ],
                'image_url' => $imageUrl,
                'action_url' => "/api/buildings?street_number={$building->StreetNumber}&street_name=" . urlencode($building->StreetName)
            ];

            // Add related data from the representative property
            if ($property) {
                // Add details if available
                if (isset($property->media)) {
                    $formattedBuilding['media'] = $property->media;
                }

                // if (isset($property->details)) {
                //     $formattedBuilding['details'] = $property->details;
                // }

                // // Add amenities if available
                // if (isset($property->amenities)) {
                //     $formattedBuilding['amenities'] = $property->amenities;
                // }

                // // Add schools if available
                // if (isset($property->schools)) {
                //     $formattedBuilding['schools'] = $property->schools;
                // }

                // // Add financial details if available
                // if (isset($property->financialDetails)) {
                //     $formattedBuilding['financial_details'] = $property->financialDetails;
                // }
            }

            return $formattedBuilding;
        });

        // Format the response
        return response()->json([
            'properties' => $formattedBuildings,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    public function getHomePageDevelopments(Request $request)
    {
        // Reuse the same query logic but limit to 8 items
        $request->merge(['limit' => 8, 'page' => 1]);
        return $this->getNewDevelopments($request);
    }

    public function getCondominiums(Request $request)
    {
        // Start with a query to group condominiums by address
        $buildingsQuery = DB::table('properties AS p')
            ->select(
                DB::raw('TRIM(p.StreetNumber) as StreetNumber'),
                DB::raw('TRIM(p.StreetName) as StreetName'),
                DB::raw('TRIM(p.City) as City'),
                DB::raw('TRIM(p.StateOrProvince) as StateOrProvince'),
                DB::raw('TRIM(p.PostalCode) as PostalCode'),
                DB::raw('COUNT(*) as unit_count'),
                DB::raw('MIN(p.ListPrice) as min_price'),
                DB::raw('MAX(p.ListPrice) as max_price'),
                DB::raw('MIN(p.id) as representative_id') // Get one property ID to represent the building
            )
            ->where('p.PropertySubType', 'Condominium')
            ->where('p.StandardStatus', 'Active')
            ->whereNotNull('p.StreetNumber')
            ->whereNotNull('p.StreetName')
            // Filter out invalid city values
            ->where(function ($query) {
                $query->whereNull('p.City')
                    ->orWhere('p.City', '!=', ',')
                    ->orWhere('p.City', '!=', '');
            })
            // Group by the same trimmed values that we're selecting
            ->groupBy(
                DB::raw('TRIM(p.StreetNumber)'),
                DB::raw('TRIM(p.StreetName)'),
                DB::raw('TRIM(p.City)'),
                DB::raw('TRIM(p.StateOrProvince)'),
                DB::raw('TRIM(p.PostalCode)')
            )
            // Only include buildings with multiple units
            ->havingRaw('COUNT(*) > 0');

        // Apply ordering if requested
        if ($request->has('orderby')) {
            $orderBy = $request->input('orderby');
            $direction = 'asc';

            if (strpos($orderBy, ' desc') !== false) {
                $orderBy = str_replace(' desc', '', $orderBy);
                $direction = 'desc';
            }

            // Map the order column to an appropriate aggregate function if needed
            switch ($orderBy) {
                case 'ListPrice':
                    $buildingsQuery->orderBy('min_price', $direction);
                    break;
                case 'unit_count':
                    $buildingsQuery->orderBy('unit_count', $direction);
                    break;
                default:
                    // For other columns, try to order by them directly
                    $buildingsQuery->orderBy($orderBy, $direction);
                    break;
            }
        } else {
            // Default ordering by unit count (to prioritize actual buildings with multiple units)
            $buildingsQuery->orderBy('unit_count', 'desc')
                ->orderBy('min_price', 'desc');
        }

        // Get the total count before pagination
        $totalCount = $buildingsQuery->get()->count();

        // Handle pagination parameters
        $limit = $request->input('limit', 10); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to first page
        $offset = ($page - 1) * $limit; // Calculate the offset

        // Apply limit and offset
        $buildings = $buildingsQuery->skip($offset)->take($limit)->get();

        // Get the representative property IDs
        $representativeIds = $buildings->pluck('representative_id')->toArray();

        // Fetch the representative properties with their relationships
        $representativeProperties = Property::with(['media'])
            ->whereIn('id', $representativeIds)
            ->get()
            ->keyBy('id'); // Index by ID for easier lookup

        // Format the building data with representative property information
        $formattedBuildings = $buildings->map(function ($building) use ($representativeProperties) {
            // Get the representative property
            $property = $representativeProperties[$building->representative_id] ?? null;

            // Create a building name from the address
            $buildingName = trim($building->StreetNumber . ' ' . $building->StreetName);

            // Get a representative image URL if available
            $imageUrl = null;
            if ($property && isset($property->media) && $property->media->isNotEmpty()) {
                $imageUrl = $property->media->first()->MediaURL ?? null;
            }

            // Clean up city value
            $city = ($building->City == ',' || empty($building->City)) ? null : $building->City;

            return [
                'id' => $building->representative_id,
                'building_name' => $buildingName,
                'address' => $buildingName,
                'city' => $city,
                'state' => $building->StateOrProvince,
                'postal_code' => $building->PostalCode,
                'unit_count' => $building->unit_count,
                'price_range' => [
                    'min' => $building->min_price,
                    'max' => $building->max_price
                ],
                'image_url' => $imageUrl,
                'property_subtype' => 'Condominium',
                'action_url' => "/api/buildings?street_number={$building->StreetNumber}&street_name=" . urlencode($building->StreetName)
            ];
        });

        // Format the response with success, data, and meta fields
        return response()->json([
            'success' => true,
            'data' => $formattedBuildings,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    public function searchProperties(Request $request)
    {
        $query = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = trim($request->search);
            $parts = array_map('trim', explode(',', $searchTerm));
            $partsCount = count($parts);

            $street = null;
            $city = null;
            $state = null;
            $postalCode = null;
            $country = null;

            // Handle different address input patterns
            if ($partsCount >= 1) {
                $streetOrCity = $parts[0];
                // If it's a full address, like "400 Sunny Isles Blvd 119"
                if (preg_match('/\d+/', $streetOrCity)) {
                    $street = $streetOrCity;
                } else {
                    $city = $streetOrCity;
                }
            }

            if ($partsCount >= 2) {
                $city = $parts[1];
            }

            if ($partsCount >= 3) {
                $state = $parts[2];
            }

            if ($partsCount >= 4) {
                // Either a postal code or a country
                if (preg_match('/\d{4,}/', $parts[3])) {
                    $postalCode = $parts[3];
                } else {
                    $country = $parts[3];
                }
            }

            if ($partsCount >= 5) {
                $country = $parts[4];
            }

            // Convert full state to abbreviation if needed
            $stateAbbr = $state ? $this->getStateAbbreviation($state) : null;

            $query->where(function ($q) use ($street, $city, $stateAbbr, $postalCode, $country) {
                if ($street) {
                    $q->where(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$street}%")
                        ->orWhere('UnparsedAddress', 'like', "%{$street}%");
                }

                if ($city) {
                    $q->where('City', 'like', "%{$city}%");
                }

                if ($stateAbbr) {
                    $q->where('StateOrProvince', 'like', "{$stateAbbr}%");
                }

                if ($postalCode) {
                    $q->where('PostalCode', 'like', "%{$postalCode}%");
                }

                if ($country) {
                    $q->where('country', 'like', "%{$country}%");
                }
            });
        }

        // Individual filters (optional)
        if ($request->filled('street_number')) {
            $query->where('StreetNumber', 'like', "%{$request->street_number}%");
        }

        if ($request->filled('street_name')) {
            $query->where('StreetName', 'like', "%{$request->street_name}%");
        }

        if ($request->filled('postal_code')) {
            $query->where('PostalCode', 'like', "%{$request->postal_code}%");
        }

        // Place parameter - search in both city and state
        if ($request->filled('place')) {
            $place = trim($request->place);
            $stateAbbr = $this->getStateAbbreviation($place);

            $query->where(function ($q) use ($place, $stateAbbr) {
                $q->where('City', 'like', "%{$place}%")
                    ->orWhere('StateOrProvince', 'like', "%{$stateAbbr}%")
                    ->orWhere('StateOrProvince', 'like', "%{$place}%");
            });
        } else {
            // If place is not provided, use individual city and state filters
            if ($request->filled('city')) {
                $query->where('City', 'like', "%{$request->city}%");
            }

            if ($request->filled('state')) {
                $stateAbbr = $this->getStateAbbreviation($request->state);
                $query->where('StateOrProvince', 'like', "%{$stateAbbr}%");
            }
        }

        if ($request->filled('country')) {
            $query->where('country', 'like', "%{$request->country}%");
        }

        // Property type filters
        if ($request->filled('property_type')) {
            $query->where('PropertyType', 'like', "%{$request->property_type}%");
        }

        if ($request->filled('property_subtype')) {
            $query->where('PropertySubType', 'like', "%{$request->property_subtype}%");
        }

        // Price range filters
        if ($request->filled('min_price')) {
            $query->where('ListPrice', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('ListPrice', '<=', $request->max_price);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('StandardStatus', $request->status);
        } else {
            // Default to Active properties only
            $query->where('StandardStatus', 'Active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'ListPrice'); // Default sort by price
        $sortDirection = $request->get('sort_direction', 'asc'); // Default sort direction is ascending

        // Validate sort field to prevent SQL injection
        $allowedSortFields = [
            'ListPrice', 'PropertyType', 'PropertySubType', 'YearBuilt',
            'BedroomsTotal', 'BathroomsFull', 'LivingArea'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('ListPrice', 'asc'); // Default fallback
        }

        // Pagination
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        $properties = $query->paginate($limit);

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $properties->items(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
                'has_more' => $properties->hasMorePages()
            ]
        ]);
    }

    private function getStateAbbreviation($state)
    {
        $states = [
            'alabama' => 'AL',
            'alaska' => 'AK',
            'arizona' => 'AZ',
            'arkansas' => 'AR',
            'california' => 'CA',
            'colorado' => 'CO',
            'connecticut' => 'CT',
            'delaware' => 'DE',
            'florida' => 'FL',
            'georgia' => 'GA',
            'hawaii' => 'HI',
            'idaho' => 'ID',
            'illinois' => 'IL',
            'indiana' => 'IN',
            'iowa' => 'IA',
            'kansas' => 'KS',
            'kentucky' => 'KY',
            'louisiana' => 'LA',
            'maine' => 'ME',
            'maryland' => 'MD',
            'massachusetts' => 'MA',
            'michigan' => 'MI',
            'minnesota' => 'MN',
            'mississippi' => 'MS',
            'missouri' => 'MO',
            'montana' => 'MT',
            'nebraska' => 'NE',
            'nevada' => 'NV',
            'new hampshire' => 'NH',
            'new jersey' => 'NJ',
            'new mexico' => 'NM',
            'new york' => 'NY',
            'north carolina' => 'NC',
            'north dakota' => 'ND',
            'ohio' => 'OH',
            'oklahoma' => 'OK',
            'oregon' => 'OR',
            'pennsylvania' => 'PA',
            'rhode island' => 'RI',
            'south carolina' => 'SC',
            'south dakota' => 'SD',
            'tennessee' => 'TN',
            'texas' => 'TX',
            'utah' => 'UT',
            'vermont' => 'VT',
            'virginia' => 'VA',
            'washington' => 'WA',
            'west virginia' => 'WV',
            'wisconsin' => 'WI',
            'wyoming' => 'WY',
        ];

        $state = strtolower($state);

        // If it's already an abbreviation, return it
        if (in_array(strtoupper($state), array_values($states))) {
            return strtoupper($state);
        }

        return $states[$state] ?? $state;
    }

    public function propertyDetails($id, Request $request)
    {
        // Validate any filter parameters if provided
        $request->validate([
            'type' => 'nullable|string|in:buy,rent,all',
            'property_type' => 'nullable|string',
            'property_subtype' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|integer',
            'max_baths' => 'nullable|integer',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'min_land_size' => 'nullable|numeric',
            'max_land_size' => 'nullable|numeric',
            'min_year_built' => 'nullable|integer|min:1800|max:2025',
            'max_year_built' => 'nullable|integer|min:1800|max:2025',
            'waterfront' => 'nullable|boolean',
            'waterfront_features' => 'nullable|string',
            'swimming_pool' => 'nullable|boolean',
            'tennis_court' => 'nullable|boolean',
            'gated_community' => 'nullable|boolean',
            'penthouse' => 'nullable|boolean',
            'pets_allowed' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'golf_course' => 'nullable|boolean',
            'boat_dock' => 'nullable|boolean',
            'parking_spaces' => 'nullable|integer',
        ]);

        // Start with a base query to find the property
        $propertyQuery = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ])->where('id', $id);

        // Apply type filter if provided
        $type = $request->input('type', 'all');
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $propertyQuery->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $propertyQuery->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
            }
        }

        // Apply property type filter if provided
        if ($request->filled('property_type')) {
            $propertyQuery->where('PropertyType', $request->property_type);
        }

        // Apply property subtype filter if provided
        if ($request->filled('property_subtype')) {
            $propertyQuery->where('PropertySubType', $request->property_subtype);
        }

        // Apply price filters if provided
        if ($request->filled('min_price')) {
            $propertyQuery->where('ListPrice', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $propertyQuery->where('ListPrice', '<=', $request->max_price);
        }

        // Apply bedroom filters if provided
        if ($request->filled('min_beds')) {
            $propertyQuery->where('BedroomsTotal', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $propertyQuery->where('BedroomsTotal', '<=', $request->max_beds);
        }

        // Apply bathroom filters if provided
        if ($request->filled('min_baths')) {
            $propertyQuery->where('BathroomsTotalInteger', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $propertyQuery->where('BathroomsTotalInteger', '<=', $request->max_baths);
        }

        // Apply living size filters if provided
        if ($request->filled('min_living_size')) {
            $propertyQuery->where('LivingArea', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $propertyQuery->where('LivingArea', '<=', $request->max_living_size);
        }

        // Apply land size filters if provided
        if ($request->filled('min_land_size')) {
            $propertyQuery->where('LotSizeSquareFeet', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $propertyQuery->where('LotSizeSquareFeet', '<=', $request->max_land_size);
        }

        // Apply year built filters if provided
        if ($request->filled('min_year_built')) {
            $propertyQuery->where('YearBuilt', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $propertyQuery->where('YearBuilt', '<=', $request->max_year_built);
        }

        // Apply waterfront filter if provided
        if ($request->has('waterfront')) {
            $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN);
            // Check if the property has WaterfrontYN field matching the requested value
            $propertyQuery->whereHas('details', function ($q) use ($waterfrontValue) {
                if ($waterfrontValue) {
                    // If looking for waterfront properties
                    $q->where('WaterfrontYN', true);
                } else {
                    // If looking for non-waterfront properties
                    $q->where(function ($subQuery) {
                        $subQuery->where('WaterfrontYN', false)
                            ->orWhereNull('WaterfrontYN');
                    });
                }
            });
        }

        // Apply waterfront features filter if provided
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            // Split the input by commas if multiple features are provided
            $featuresArray = explode(',', $waterfrontFeatures);

            $propertyQuery->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        // Use LIKE query to find the feature in the comma-separated list
                        $q->where('WaterfrontFeatures', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply swimming pool filter if provided
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Swimming Pool%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Swimming Pool%')
                    ->orWhere('PoolPrivateYN', true);
            });
        }

        // Apply tennis court filter if provided
        if ($request->has('tennis_court') && $request->boolean('tennis_court')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Tennis Court%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Tennis Court%');
            });
        }

        // Apply gated community filter if provided
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('CommunityFeatures', 'LIKE', '%Gated Community%')
                    ->orWhere('AssociationAmenities', 'LIKE', '%Gated%');
            });
        }

        // Apply penthouse filter if provided
        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Penthouse%')
                    ->orWhere('PropertySubType', 'LIKE', '%Penthouse%');
            });
        }

        // Apply pets allowed filter if provided
        if ($request->has('pets_allowed') && $request->boolean('pets_allowed')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('PetsAllowed', true)
                    ->orWhere('PetsAllowedYN', true);
            });
        }

        // Apply furnished filter if provided
        if ($request->has('furnished') && $request->boolean('furnished')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('Furnished', true);
            });
        }

        // Apply golf course filter if provided
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Golf Course%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Golf Course%');
            });
        }

        // Apply boat dock filter if provided
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $propertyQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Boat Dock%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Boat Dock%');
            });
        }

        // Apply parking spaces filter if provided
        if ($request->has('parking_spaces') && is_numeric($request->input('parking_spaces'))) {
            $propertyQuery->whereHas('amenities', function ($q) use ($request) {
                $q->where('ParkingTotal', '>=', $request->input('parking_spaces'));
            });
        }

        // Find the property
        $property = $propertyQuery->first();

        // If property not found, return 404
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found or does not match the specified criteria'
            ], 404);
        }

        // Return the property details
        return response()->json([
            'success' => true,
            'property' => $property
        ]);
    }

    // public function buildings(Request $request)
    // {
    //     // Validate request
    //     $request->validate([
    //         'street_number' => 'required|string',
    //         'type' => 'required|string|in:buy,rent,all',
    //         'street_name' => 'required|string',
    //         'property_type' => 'nullable|string',
    //         'property_subtype' => 'nullable|string',
    //         'min_price' => 'nullable|numeric',
    //         'max_price' => 'nullable|numeric',
    //         'min_beds' => 'nullable|integer',
    //         'max_beds' => 'nullable|integer',
    //         'min_baths' => 'nullable|integer',
    //         'max_baths' => 'nullable|integer',
    //         'min_living_size' => 'nullable|numeric',
    //         'max_living_size' => 'nullable|numeric',
    //         'min_land_size' => 'nullable|numeric',
    //         'max_land_size' => 'nullable|numeric',
    //         'min_year_built' => 'nullable|integer|min:1800|max:2025',
    //         'max_year_built' => 'nullable|integer|min:1800|max:2025',
    //         'parking_spaces' => 'nullable|integer',
    //         'waterfront' => 'nullable|boolean',
    //         'waterfront_features' => 'nullable|string',
    //         'pets_allowed' => 'nullable|boolean',
    //         'furnished' => 'nullable|boolean',
    //         'swimming_pool' => 'nullable|boolean',
    //         'golf_course' => 'nullable|boolean',
    //         'tennis_courts' => 'nullable|boolean',
    //         'gated_community' => 'nullable|boolean',
    //         'boat_dock' => 'nullable|boolean',
    //         'limit' => 'nullable|integer|min:1',
    //         'page' => 'nullable|integer|min:1',
    //         'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea,YearBuilt,LotSizeArea',
    //         'sort_dir' => 'nullable|string|in:asc,desc'
    //     ]);

    //     $streetNumber = $request->input('street_number');
    //     $streetName = $request->input('street_name');
    //     $type = $request->input('type', 'buy'); // Default to 'all' if not specified
    //     $limit = $request->input('limit', 12);
    //     $page = $request->input('page', 1);
    //     $sortBy = $request->input('sort_by', 'ListPrice');
    //     $sortDir = $request->input('sort_dir', 'asc');

    //     // Get building information
    //     $buildingInfo = DB::table('properties')
    //         ->select(
    //             'StreetNumber',
    //             'StreetName',
    //             'City',
    //             'StateOrProvince',
    //             'PostalCode',
    //             'BuildingName',
    //             DB::raw('COUNT(*) as unit_count'),
    //             DB::raw('MIN(ListPrice) as min_price'),
    //             DB::raw('MAX(ListPrice) as max_price')
    //         )
    //         ->where('StreetNumber', $streetNumber)
    //         ->where('StreetName', $streetName)
    //         ->where('StandardStatus', 'Active')
    //         ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode', 'BuildingName')
    //         ->first();

    //     if (!$buildingInfo) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Building not found'
    //         ], 404);
    //     }

    //     // Start building the query for units
    //     $unitsQuery = Property::with(['details', 'media'])
    //         ->where('StreetNumber', $streetNumber)
    //         ->where('StreetName', $streetName)
    //         ->where('StandardStatus', 'Active');

    //     // Apply type filter if provided
    //     if ($type && $type !== 'all') {
    //         switch (strtolower($type)) {
    //             case 'buy':
    //                 // Properties for sale
    //                 $unitsQuery->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //             case 'rent':
    //                 // Properties for rent
    //                 $unitsQuery->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //                 break;
    //         }
    //     }

    //     // Apply property type filter if provided
    //     if ($request->filled('property_type')) {
    //         $unitsQuery->where('PropertyType', $request->property_type);
    //     }

    //     // Apply property subtype filter if provided
    //     if ($request->filled('property_subtype')) {
    //         $unitsQuery->where('PropertySubType', $request->property_subtype);
    //     }

    //     // Apply price filters if provided
    //     if ($request->filled('min_price')) {
    //         $unitsQuery->where('ListPrice', '>=', $request->min_price);
    //     }
    //     if ($request->filled('max_price')) {
    //         $unitsQuery->where('ListPrice', '<=', $request->max_price);
    //     }

    //     // Apply bedroom filters if provided
    //     if ($request->filled('min_beds')) {
    //         $unitsQuery->where('BedroomsTotal', '>=', $request->min_beds);
    //     }
    //     if ($request->filled('max_beds')) {
    //         $unitsQuery->where('BedroomsTotal', '<=', $request->max_beds);
    //     }

    //     // Apply bathroom filters if provided
    //     if ($request->filled('min_baths')) {
    //         $unitsQuery->where('BathroomsTotalInteger', '>=', $request->min_baths);
    //     }
    //     if ($request->filled('max_baths')) {
    //         $unitsQuery->where('BathroomsTotalInteger', '<=', $request->max_baths);
    //     }

    //     // Apply living size filters if provided
    //     if ($request->filled('min_living_size')) {
    //         $unitsQuery->where('LivingArea', '>=', $request->min_living_size);
    //     }
    //     if ($request->filled('max_living_size')) {
    //         $unitsQuery->where('LivingArea', '<=', $request->max_living_size);
    //     }

    //     // Apply land size filters if provided
    //     if ($request->filled('min_land_size')) {
    //         $unitsQuery->where('LotSizeSquareFeet', '>=', $request->min_land_size);
    //     }
    //     if ($request->filled('max_land_size')) {
    //         $unitsQuery->where('LotSizeSquareFeet', '<=', $request->max_land_size);
    //     }

    //     // Apply year built filters if provided
    //     if ($request->filled('min_year_built')) {
    //         $unitsQuery->where('YearBuilt', '>=', $request->min_year_built);
    //     }
    //     if ($request->filled('max_year_built')) {
    //         $unitsQuery->where('YearBuilt', '<=', $request->max_year_built);
    //     }

    //     if ($request->has('waterfront')) {
    //         $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    //         if (!is_null($waterfrontValue)) {
    //             $query->join('property_details', 'properties.id', '=', 'property_details.property_id');

    //             if ($waterfrontValue) {
    //                 // Waterfront = true
    //                 $unitsQuery->where('property_details.WaterfrontYN', true);
    //             } else {
    //                 // Waterfront = false or null
    //                 $unitsQuery->where(function ($subQuery) {
    //                     $subQuery->where('property_details.WaterfrontYN', false)
    //                         ->orWhereNull('property_details.WaterfrontYN');
    //                 });
    //             }
    //         }
    //     }

    //     if ($request->filled('waterfront_features')) {
    //         $waterfrontFeatures = $request->input('waterfront_features');
    //         $featuresArray = explode(',', $waterfrontFeatures);

    //         $unitsQuery->whereHas('details', function ($q) use ($featuresArray) {
    //             foreach ($featuresArray as $feature) {
    //                 $feature = trim($feature);
    //                 if (!empty($feature)) {
    //                     $q->where('WaterfrontFeatures', 'LIKE', '%' . $feature . '%');
    //                 }
    //             }
    //         });
    //     }

    //     if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
    //         $unitsQuery->whereHas('amenities', function ($q) {
    //             $q->where('AssociationAmenities', 'LIKE', '%Pool%');
    //         });
    //     }

    //     if ($request->has('tennis_court') && $request->boolean('tennis_court')) {
    //         $unitsQuery->whereHas('amenities', function ($q) {
    //             $q->where('AssociationAmenities', 'LIKE', '%TennisCourts%');
    //         });
    //     }

    //     if ($request->has('gated_community') && $request->boolean('gated_community')) {
    //         $unitsQuery->whereHas('amenities', function ($q) {
    //             $q->where('CommunityFeatures', 'LIKE', '%Gated%');
    //         });
    //     }

    //     if ($request->has('penthouse') && $request->boolean('penthouse')) {
    //         $unitsQuery->whereHas('amenities', function ($q) {
    //             $q->where('AssociationAmenities', 'LIKE', '%Penthouse%');
    //         });
    //     }

    //     // Apply sorting
    //     $unitsQuery->orderBy($sortBy, $sortDir);

    //     // Get paginated results
    //     $units = $unitsQuery->paginate($limit, ['*'], 'page', $page);

    //     // Get all filtered units in the building
    //     $units = $unitsQuery->get();

    //     // Separate units by type (sale vs rental)
    //     $salesUnits = $units->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
    //     $rentalUnits = $units->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);

    //     // Format building name
    //     $buildingName = !empty($buildingInfo->BuildingName)
    //         ? $buildingInfo->BuildingName
    //         : trim($buildingInfo->StreetNumber . ' ' . $buildingInfo->StreetName);

    //     // Format the response
    //     return response()->json([
    //         'success' => true,
    //         'building' => [
    //             'name' => $buildingName,
    //             'address' => trim($buildingInfo->StreetNumber . ' ' . $buildingInfo->StreetName),
    //             'city' => $buildingInfo->City,
    //             'state' => $buildingInfo->StateOrProvince,
    //             'postal_code' => $buildingInfo->PostalCode,
    //             'unit_count' => $buildingInfo->unit_count,
    //             'price_range' => [
    //                 'min' => $buildingInfo->min_price,
    //                 'max' => $buildingInfo->max_price
    //             ]
    //         ],
    //         'units' => [
    //             'for_sale' => $salesUnits,
    //             'for_rent' => $rentalUnits
    //         ],
    //         'total_units' => $units->count(),
    //         'sales_units_count' => $salesUnits->count(),
    //         'rental_units_count' => $rentalUnits->count(),
    //     ]);
    // }

    public function buildings(Request $request)
    {
        // Validate request
        $request->validate([
            'street_number' => 'required|string',
            'type' => 'required|string|in:buy,rent,all',
            'street_name' => 'required|string',
            'property_type' => 'nullable|string',
            'property_subtype' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            // 'min_beds' => 'nullable|integer',
            // 'max_beds' => 'nullable|integer',
            // 'min_baths' => 'nullable|integer',
            // 'max_baths' => 'nullable|integer',
            'beds' => 'nullable|integer',
            'baths' => 'nullable|integer',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'min_land_size' => 'nullable|numeric',
            'max_land_size' => 'nullable|numeric',
            'min_year_built' => 'nullable|integer|min:1800|max:2025',
            'max_year_built' => 'nullable|integer|min:1800|max:2025',
            'waterfront' => 'nullable|boolean',
            'waterfront_features' => 'nullable|string',
            'swimming_pool' => 'nullable|boolean',
            'tennis_court' => 'nullable|boolean',
            'gated_community' => 'nullable|boolean',
            'penthouse' => 'nullable|boolean',
            'pets_allowed' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'golf_course' => 'nullable|boolean',
            'boat_dock' => 'nullable|boolean',
            'parking_spaces' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea,YearBuilt',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        $streetNumber = $request->input('street_number');
        $streetName = $request->input('street_name');
        $type = $request->input('type', 'all'); // Default to 'all' if not specified
        $limit = $request->input('limit', 12); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to first page
        $sortBy = $request->input('sort_by', 'ListPrice'); // Default sort by price
        $sortDir = $request->input('sort_dir', 'asc'); // Default sort direction

        // Get building information
        $buildingInfo = DB::table('properties')
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
            ->where('StreetNumber', $streetNumber)
            ->where('StreetName', $streetName)
            ->where('StandardStatus', 'Active')
            ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode', 'BuildingName')
            ->first();

        if (!$buildingInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found'
            ], 404);
        }

        // Start building the query for units
        $unitsQuery = Property::with(['details', 'media', 'amenities'])
            ->where('StreetNumber', $streetNumber)
            ->where('StreetName', $streetName)
            ->where('StandardStatus', 'Active');

        // Apply type filter if provided
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $unitsQuery->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $unitsQuery->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
            }
        }

        // Apply property type filter if provided
        if ($request->filled('property_type')) {
            $unitsQuery->where('PropertyType', $request->property_type);
        }

        // Apply property subtype filter if provided
        if ($request->filled('property_subtype')) {
            $unitsQuery->where('PropertySubType', $request->property_subtype);
        }

        // Apply price filters if provided
        if ($request->filled('min_price')) {
            $unitsQuery->where('ListPrice', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $unitsQuery->where('ListPrice', '<=', $request->max_price);
        }

        if ($request->filled('beds')) {
            $unitsQuery->where('BedroomsTotal', $request->beds);
        }
        
        // Apply bathroom filter if provided
        if ($request->filled('baths')) {
            $unitsQuery->where('BathroomsTotalInteger', $request->baths);
        }

        // // Apply bedroom filters if provided
        // if ($request->filled('min_beds')) {
        //     $unitsQuery->where('BedroomsTotal', '>=', $request->min_beds);
        // }
        // if ($request->filled('max_beds')) {
        //     $unitsQuery->where('BedroomsTotal', '<=', $request->max_beds);
        // }

        // // Apply bathroom filters if provided
        // if ($request->filled('min_baths')) {
        //     $unitsQuery->where('BathroomsTotalInteger', '>=', $request->min_baths);
        // }
        // if ($request->filled('max_baths')) {
        //     $unitsQuery->where('BathroomsTotalInteger', '<=', $request->max_baths);
        // }

        // Apply living size filters if provided
        if ($request->filled('min_living_size')) {
            $unitsQuery->where('LivingArea', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $unitsQuery->where('LivingArea', '<=', $request->max_living_size);
        }

        // Apply land size filters if provided
        if ($request->filled('min_land_size')) {
            $unitsQuery->where('LotSizeSquareFeet', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $unitsQuery->where('LotSizeSquareFeet', '<=', $request->max_land_size);
        }

        // Apply year built filters if provided
        if ($request->filled('min_year_built')) {
            $unitsQuery->where('YearBuilt', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $unitsQuery->where('YearBuilt', '<=', $request->max_year_built);
        }

        // Apply waterfront filter if provided
        if ($request->has('waterfront')) {
            $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN);
            // Check if the property has WaterfrontYN field matching the requested value
            $unitsQuery->whereHas('details', function ($q) use ($waterfrontValue) {
                if ($waterfrontValue) {
                    // If looking for waterfront properties
                    $q->where('WaterfrontYN', true);
                } else {
                    // If looking for non-waterfront properties
                    $q->where(function ($subQuery) {
                        $subQuery->where('WaterfrontYN', false)
                            ->orWhereNull('WaterfrontYN');
                    });
                }
            });
        }

        // Apply waterfront features filter if provided
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            // Split the input by commas if multiple features are provided
            $featuresArray = explode(',', $waterfrontFeatures);

            $unitsQuery->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        // Use LIKE query to find the feature in the comma-separated list
                        $q->where('WaterfrontFeatures', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply swimming pool filter if provided
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Swimming Pool%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Pool%')
                    ->orWhere('PoolPrivateYN', true);
            });
        }

        // Apply tennis court filter if provided
        if ($request->has('tennis_court') && $request->boolean('tennis_court')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Tennis Court%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%TennisCourts%');
            });
        }

        // Apply gated community filter if provided
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('CommunityFeatures', 'LIKE', '%Gated Community%')
                    ->orWhere('AssociationAmenities', 'LIKE', '%Gated%');
            });
        }

        // Apply penthouse filter if provided
        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Penthouse%')
                    ->orWhere('PropertySubType', 'LIKE', '%Penthouse%');
            });
        }

        // Apply pets allowed filter if provided
        if ($request->has('pets_allowed') && $request->boolean('pets_allowed')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('PetsAllowed', true)
                    ->orWhere('PetsAllowedYN', true);
            });
        }

        // Apply furnished filter if provided
        if ($request->has('furnished') && $request->boolean('furnished')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('Furnished', true);
            });
        }

        // Apply golf course filter if provided
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Golf Course%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%GolfCourse%');
            });
        }

        // Apply boat dock filter if provided
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $unitsQuery->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Boat Dock%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%BoatDock%');
            });
        }

        // Apply parking spaces filter if provided
        if ($request->has('parking_spaces') && is_numeric($request->input('parking_spaces'))) {
            $unitsQuery->whereHas('amenities', function ($q) use ($request) {
                $q->where('ParkingTotal', '>=', $request->input('parking_spaces'));
            });
        }

        // Apply sorting
        if ($request->filled('sort_by') && $request->filled('sort_dir')) {
            $unitsQuery->orderBy($sortBy, $sortDir);
        } else {
            // Default sorting by price
            $unitsQuery->orderBy('ListPrice', 'asc');
        }

        // Get total count before pagination
        $totalCount = $unitsQuery->count();

        // Apply pagination
        $units = $unitsQuery->skip(($page - 1) * $limit)->take($limit)->get();

        // Separate units by type (sale vs rental)
        $salesUnits = $units->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
        $rentalUnits = $units->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);

        // Format building name
        $buildingName = !empty($buildingInfo->BuildingName)
            ? $buildingInfo->BuildingName
            : trim($buildingInfo->StreetNumber . ' ' . $buildingInfo->StreetName);

        // Format the response
        return response()->json([
            'success' => true,
            'building' => [
                'name' => $buildingName,
                'address' => trim($buildingInfo->StreetNumber . ' ' . $buildingInfo->StreetName),
                'city' => $buildingInfo->City,
                'state' => $buildingInfo->StateOrProvince,
                'postal_code' => $buildingInfo->PostalCode,
                'unit_count' => $buildingInfo->unit_count,
                'price_range' => [
                    'min' => $buildingInfo->min_price,
                    'max' => $buildingInfo->max_price
                ]
            ],
            'units' => [
                'for_sale' => $salesUnits,
                'for_rent' => $rentalUnits
            ],
            'total_units' => $totalCount,
            'sales_units_count' => $salesUnits->count(),
            'rental_units_count' => $rentalUnits->count(),
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'last_page' => ceil($totalCount / $limit),
                'has_more_pages' => ($page * $limit) < $totalCount
            ]
        ]);
    }

    public function places(Request $request)
    {
        // Validate request
        $request->validate([
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'type' => 'required|string|in:buy,rent,all', // Add type parameter
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea,YearBuilt,LotSizeArea',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'property_type' => 'nullable|string',
            'property_subtype' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|integer',
            'max_baths' => 'nullable|integer',
            'beds' => 'nullable|integer',
            'baths' => 'nullable|integer',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'min_land_size' => 'nullable|numeric',
            'max_land_size' => 'nullable|numeric',
            'min_year_built' => 'nullable|integer|min:1800|max:2025',
            'max_year_built' => 'nullable|integer|min:1800|max:2025',
            'parking_spaces' => 'nullable|integer',
            'waterfront' => 'nullable|boolean',
            'waterfront_features' => 'nullable|string',
            'pets_allowed' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'swimming_pool' => 'nullable|boolean',
            'golf_course' => 'nullable|boolean',
            'tennis_courts' => 'nullable|boolean',
            'gated_community' => 'nullable|boolean',
            'boat_dock' => 'nullable|boolean',
        ]);

        $city = $request->input('city');
        $state = $request->input('state');
        $type = $request->input('type', 'all'); // Default to 'all' if not specified
        $limit = $request->input('limit', 12);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'ListPrice');
        $sortDir = $request->input('sort_dir', 'asc');
        $propertyType = $request->input('property_type');
        $propertySubtype = $request->input('property_subtype');

        // dd($request->all(),$type);

        // Ensure at least one location parameter is provided
        if (empty($city) && empty($state)) {
            return response()->json([
                'success' => false,
                'message' => 'Either city or state parameter is required'
            ], 400);
        }

        // Build query
        $query = Property::select('properties.*')
            ->with(['details', 'amenities', 'media'])
            ->where('StandardStatus', 'Active');

        // Apply type filter if provided
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
            }
        }

        // Apply city filter if provided
        if (!empty($city)) {
            $query->where('City', $city);
        }

        // Apply state filter if provided
        if (!empty($state)) {
            $query->where('StateOrProvince', $state);
        }

        // Apply property type filter if provided
        if (!empty($propertyType)) {
            $query->where('PropertyType', $propertyType);
        }

        // Apply property subtype filter if provided
        if (!empty($propertySubtype)) {
            $query->where('PropertySubType', $propertySubtype);
        }

        // Apply price filters if provided
        if ($request->has('min_price')) {
            $query->where('ListPrice', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('ListPrice', '<=', $request->input('max_price'));
        }

        // Apply bedroom filters if provided
        if ($request->has('min_beds')) {
            $query->where('BedroomsTotal', '>=', $request->input('min_beds'));
        }
        if ($request->has('max_beds')) {
            $query->where('BedroomsTotal', '<=', $request->input('max_beds'));
        }

        // Apply bathroom filters if provided
        if ($request->has('min_baths')) {
            $query->where('BathroomsTotalInteger', '>=', $request->input('min_baths'));
        }
        if ($request->has('max_baths')) {
            $query->where('BathroomsTotalInteger', '<=', $request->input('max_baths'));
        }

        // Apply living size filters if provided
        if ($request->has('min_living_size')) {
            $query->where('LivingArea', '>=', $request->input('min_living_size'));
        }
        if ($request->has('max_living_size')) {
            $query->where('LivingArea', '<=', $request->input('max_living_size'));
        }

        // Apply land size filters if provided
        if ($request->has('min_land_size')) {
            $query->where('LotSizeSquareFeet', '>=', $request->input('min_land_size'));
        }
        if ($request->has('max_land_size')) {
            $query->where('LotSizeSquareFeet', '<=', $request->input('max_land_size'));
        }

        // Apply year built filters if provided
        if ($request->has('min_year_built')) {
            $query->where('YearBuilt', '>=', $request->input('min_year_built'));
        }
        if ($request->has('max_year_built')) {
            $query->where('YearBuilt', '<=', $request->input('max_year_built'));
        }

        if ($request->has('parking_spaces') && is_numeric($request->input('parking_spaces'))) {
            $query->whereHas('amenities', function ($q) use ($request) {
                $q->where('ParkingTotal', '=', $request->input('parking_spaces'));
            });
        }

        if ($request->has('waterfront')) {
            $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($waterfrontValue)) {
                $query->join('property_details', 'properties.id', '=', 'property_details.property_id');

                if ($waterfrontValue) {
                    // Waterfront = true
                    $query->where('property_details.WaterfrontYN', true);
                } else {
                    // Waterfront = false or null
                    $query->where(function ($subQuery) {
                        $subQuery->where('property_details.WaterfrontYN', false)
                            ->orWhereNull('property_details.WaterfrontYN');
                    });
                }
            }
        }

        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            $featuresArray = explode(',', $waterfrontFeatures);

            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        $q->where('WaterfrontFeatures', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Pool%');
            });
        }

        if ($request->has('tennis_court') && $request->boolean('tennis_court')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%TennisCourts%');
            });
        }

        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('CommunityFeatures', 'LIKE', '%Gated%');
            });
        }

        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Penthouse%');
            });
        }

        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Golf Course%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%GolfCourse%');
            });
        }

        // Apply boat dock filter if provided
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Boat Dock%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%BoatDock%');
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDir);

        // Get paginated results
        $properties = $query->paginate($limit, ['*'], 'page', $page);

        // Get location information
        $locationInfo = [
            'city' => $city,
            'state' => $state,
            'total_properties' => $properties->total()
        ];

        // Format the response
        return response()->json([
            'success' => true,
            'location' => $locationInfo,
            'properties' => $properties->items(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
                'has_more_pages' => $properties->hasMorePages()
            ]
        ]);
    }

    public function getPropertiesInMapBounds(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'ne_lat' => 'required|numeric', // Northeast corner latitude
            'ne_lng' => 'required|numeric', // Northeast corner longitude
            'sw_lat' => 'required|numeric', // Southwest corner latitude
            'sw_lng' => 'required|numeric', // Southwest corner longitude
            'limit' => 'nullable|integer|min:1|max:200', // limit the number of results, max 200
            'page' => 'nullable|integer|min:1',
            'type' => 'nullable|string|in:buy,rent,all',
            'property_type' => 'nullable|string',
            'property_subtype' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|integer',
            'max_baths' => 'nullable|integer',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'waterfront' => 'nullable|boolean',
            'min_year_built' => 'nullable|integer',
            'max_year_built' => 'nullable|integer',
            'zoom_level' => 'nullable|numeric', // Map zoom level for clustering decisions
            'sort_by' => 'nullable|string|in:ListPrice,DateListed,YearBuilt,BedroomsTotal,BathroomsTotalInteger,LivingArea', // Sorting field
            'sort_dir' => 'nullable|string|in:asc,desc', // Sorting direction
        ]);

        // Get parameters from request
        $neLat = $request->input('ne_lat');
        $neLng = $request->input('ne_lng');
        $swLat = $request->input('sw_lat');
        $swLng = $request->input('sw_lng');
        $limit = $request->input('limit', 200); // default
        $page = $request->input('page', 1);
        $type = $request->input('type', 'all');
        $zoomLevel = $request->input('zoom_level');
        $sortBy = $request->input('sort_by', 'ListPrice'); // Default sort by price
        $sortDir = $request->input('sort_dir', 'desc'); // Default sort direction

        // Calculate offset here, so it's defined for all code paths

        $offset = ($page - 1) * $limit;

        // Start building the query
        $query = Property::with(['details', 'media'])
            ->select('properties.*')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('StandardStatus', 'Active');

        // Handle the international date line (when the map crosses the 180/-180 longitude line)
        if ($neLng < $swLng) {
            // Map view crosses the international date line
            $query->where(function ($q) use ($neLat, $neLng, $swLat, $swLng) {
                $q->where(function ($subQ) use ($neLat, $swLat, $swLng) {
                    $subQ->where('longitude', '>=', $swLng)
                        ->where('longitude', '<=', 180)
                        ->where('latitude', '>=', $swLat)
                        ->where('latitude', '<=', $neLat);
                })->orWhere(function ($subQ) use ($neLat, $neLng, $swLat) {
                    $subQ->where('longitude', '>=', -180)
                        ->where('longitude', '<=', $neLng)
                        ->where('latitude', '>=', $swLat)
                        ->where('latitude', '<=', $neLat);
                });
            });
        } else {
            // Normal case - map view doesn't cross the international date line
            $query->whereBetween('longitude', [$swLng, $neLng])
                ->whereBetween('latitude', [$swLat, $neLat]);
        }

        // Apply type filter if provided
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
            }
        }

        // Apply property type filter if provided
        if ($request->filled('property_type')) {
            $query->where('PropertyType', $request->property_type);
        }

        // Apply property subtype filter if provided
        if ($request->filled('property_subtype')) {
            $query->where('PropertySubType', $request->property_subtype);
        }

        // Apply price filters if provided
        if ($request->filled('min_price')) {
            $query->where('ListPrice', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('ListPrice', '<=', $request->max_price);
        }

        // Apply bedroom filters if provided
        if ($request->filled('min_beds')) {
            $query->where('BedroomsTotal', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $query->where('BedroomsTotal', '<=', $request->max_beds);
        }

        // Apply bathroom filters if provided
        if ($request->filled('min_baths')) {
            $query->where('BathroomsTotalInteger', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $query->where('BathroomsTotalInteger', '<=', $request->max_baths);
        }

        // Apply living size filters if provided
        if ($request->filled('min_living_size')) {
            $query->where('LivingArea', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $query->where('LivingArea', '<=', $request->max_living_size);
        }

        // Apply year built filters if provided
        if ($request->filled('min_year_built')) {
            $query->where('YearBuilt', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $query->where('YearBuilt', '<=', $request->max_year_built);
        }

        // Apply waterfront filter if provided
        if ($request->has('waterfront')) {
            $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN);
            // Check if the property has WaterfrontYN field matching the requested value
            $query->whereHas('details', function ($q) use ($waterfrontValue) {
                if ($waterfrontValue) {
                    // If looking for waterfront properties
                    $q->where('WaterfrontYN', true);
                } else {
                    // If looking for non-waterfront properties
                    $q->where(function ($subQuery) {
                        $subQuery->where('WaterfrontYN', false)
                            ->orWhereNull('WaterfrontYN');
                    });
                }
            });
        }

        // Get the total count before pagination
        $totalCount = $query->count();

        // Determine if we should cluster properties based on zoom level and count
        $shouldCluster = $zoomLevel && $zoomLevel < 14 && $totalCount > 200;

        $response = [];

        if ($shouldCluster) {
            // For lower zoom levels, cluster properties by grid cells
            $gridSize = 0.01; // Grid cell size in degrees (adjust based on zoom level)
            if ($zoomLevel < 10) $gridSize = 0.05;
            else if ($zoomLevel < 12) $gridSize = 0.02;

            // Use raw SQL to group properties by grid cells
            $clusters = DB::select("
                SELECT 
                    FLOOR(latitude / ?) * ? as lat_grid,
                    FLOOR(longitude / ?) * ? as lng_grid,
                    COUNT(*) as property_count,
                    AVG(latitude) as center_lat,
                    AVG(longitude) as center_lng,
                    MIN(ListPrice) as min_price,
                    MAX(ListPrice) as max_price
                FROM properties
                WHERE 
                    latitude IS NOT NULL AND
                    longitude IS NOT NULL AND
                    StandardStatus = 'Active' AND
                    latitude BETWEEN ? AND ? AND
                    longitude BETWEEN ? AND ?
                GROUP BY lat_grid, lng_grid
                ORDER BY property_count DESC
                LIMIT ?
            ", [$gridSize, $gridSize, $gridSize, $gridSize, $swLat, $neLat, $swLng, $neLng, $limit]);

            $response['clusters'] = $clusters;
            $response['is_clustered'] = true;
        } else {
            // For higher zoom levels or when there are fewer properties, return individual properties

            // Apply sorting to get the "top" properties
            // For buy properties, prioritize newer and more expensive properties
            // For rent properties, prioritize newer and more affordable properties
            if ($type === 'rent') {
                // For rentals, people often look for affordable options
                if ($sortBy === 'ListPrice') {
                    $sortDir = $sortDir ?: 'asc'; // Default to ascending for rentals
                }
            } else {
                // For sales, people often look at more expensive properties first
                if ($sortBy === 'ListPrice') {
                    $sortDir = $sortDir ?: 'desc'; // Default to descending for sales
                }
            }

            // Apply the requested sorting
            $query->orderBy($sortBy, $sortDir);

            // Add secondary sorting for better results
            if ($sortBy !== 'YearBuilt') {
                $query->orderBy('YearBuilt', 'desc'); // Newer properties first
            }

            if ($sortBy !== 'BedroomsTotal') {
                $query->orderBy('BedroomsTotal', 'desc'); // More bedrooms
            }

            // Apply pagination to get the top properties
            $properties = $query->skip($offset)->take($limit)->get();

            $response['properties'] = $properties;
            $response['is_clustered'] = false;
        }

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $response,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount,
                'bounds' => [
                    'northeast' => [
                        'lat' => (float)$neLat,
                        'lng' => (float)$neLng
                    ],
                    'southwest' => [
                        'lat' => (float)$swLat,
                        'lng' => (float)$swLng
                    ]
                ],
                'zoom_level' => $zoomLevel
            ]
        ]);
    }

    // public function getAllProperties(Request $request)
    // {
    //     // Validate pagination parameters
    //     $request->validate([
    //         'limit' => 'nullable|integer|min:1|max:100',
    //         'page' => 'nullable|integer|min:1',
    //         'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea,YearBuilt',
    //         'sort_dir' => 'nullable|string|in:asc,desc',
    //     ]);

    //     // Get pagination parameters
    //     $limit = $request->input('limit', 12); // Default to 10 items per page
    //     $page = $request->input('page', 1); // Default to first page
    //     $sortBy = $request->input('sort_by', 'ListPrice'); // Default sort by price
    //     $sortDir = $request->input('sort_dir', 'desc'); // Default sort direction

    //     // Build the query with minimal relations to improve performance
    //     $query = Property::with(['media'])
    //         ->where('StandardStatus', 'Active'); // Only include active properties

    //     // Apply sorting
    //     $query->orderBy($sortBy, $sortDir);

    //     // Get paginated results
    //     $properties = $query->paginate($limit);

    //     // Format the response
    //     return response()->json([
    //         'success' => true,
    //         'data' => $properties->items(),
    //         'meta' => [
    //             'current_page' => $properties->currentPage(),
    //             'per_page' => $properties->perPage(),
    //             'total' => $properties->total(),
    //             'last_page' => $properties->lastPage(),
    //             'has_more_pages' => $properties->hasMorePages()
    //         ]
    //     ]);
    // }

    public function getAllProperties(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea,YearBuilt',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'type' => 'nullable|string|in:buy,rent,all',
            'property_type' => 'nullable|string',
            'property_subtype' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|integer',
            'max_baths' => 'nullable|integer',
            'beds' => 'nullable|integer',
            'baths' => 'nullable|integer',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'min_land_size' => 'nullable|numeric',
            'max_land_size' => 'nullable|numeric',
            'min_year_built' => 'nullable|integer|min:1800|max:2025',
            'max_year_built' => 'nullable|integer|min:1800|max:2025',
            'waterfront' => 'nullable|boolean',
            'waterfront_features' => 'nullable|string',
            'swimming_pool' => 'nullable|boolean',
            'tennis_court' => 'nullable|boolean',
            'gated_community' => 'nullable|boolean',
            'penthouse' => 'nullable|boolean',
            'pets_allowed' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'golf_course' => 'nullable|boolean',
            'boat_dock' => 'nullable|boolean',
            'parking_spaces' => 'nullable|integer',
        ]);

        // Get pagination parameters
        $limit = $request->input('limit', 12); // Default to 12 items per page
        $page = $request->input('page', 1); // Default to first page
        $sortBy = $request->input('sort_by', 'ListPrice'); // Default sort by price
        $sortDir = $request->input('sort_dir', 'desc'); // Default sort direction
        $type = $request->input('type', 'all'); // Default to all property types

        // Build the query with relationships
        $query = Property::with(['details', 'media', 'amenities', 'schools', 'financialDetails'])
            ->where('StandardStatus', 'Active'); // Only include active properties

        // Apply type filter if provided
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('PropertyType', ['ResidentialLease', 'CommercialLease']);
                    break;
            }
        }

        // Apply property type filter if provided
        if ($request->filled('property_type')) {
            $query->where('PropertyType', $request->property_type);
        }

        // Apply property subtype filter if provided
        if ($request->filled('property_subtype')) {
            $query->where('PropertySubType', $request->property_subtype);
        }

        // Apply price filters if provided
        if ($request->filled('min_price')) {
            $query->where('ListPrice', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('ListPrice', '<=', $request->max_price);
        }

        // Apply bedroom filters if provided
        if ($request->filled('min_beds')) {
            $query->where('BedroomsTotal', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $query->where('BedroomsTotal', '<=', $request->max_beds);
        }

        // Apply bathroom filters if provided
        if ($request->filled('min_baths')) {
            $query->where('BathroomsTotalInteger', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $query->where('BathroomsTotalInteger', '<=', $request->max_baths);
        }

        // Apply living size filters if provided
        if ($request->filled('min_living_size')) {
            $query->where('LivingArea', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $query->where('LivingArea', '<=', $request->max_living_size);
        }

        // Apply land size filters if provided
        if ($request->filled('min_land_size')) {
            $query->where('LotSizeSquareFeet', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $query->where('LotSizeSquareFeet', '<=', $request->max_land_size);
        }

        // Apply year built filters if provided
        if ($request->filled('min_year_built')) {
            $query->where('YearBuilt', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $query->where('YearBuilt', '<=', $request->max_year_built);
        }

        // Apply waterfront filter if provided
        if ($request->has('waterfront')) {
            $waterfrontValue = filter_var($request->input('waterfront'), FILTER_VALIDATE_BOOLEAN);
            // Check if the property has WaterfrontYN field matching the requested value
            $query->whereHas('details', function ($q) use ($waterfrontValue) {
                if ($waterfrontValue) {
                    // If looking for waterfront properties
                    $q->where('WaterfrontYN', true);
                } else {
                    // If looking for non-waterfront properties
                    $q->where(function ($subQuery) {
                        $subQuery->where('WaterfrontYN', false)
                            ->orWhereNull('WaterfrontYN');
                    });
                }
            });
        }

        // Apply waterfront features filter if provided
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            // Split the input by commas if multiple features are provided
            $featuresArray = explode(',', $waterfrontFeatures);

            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        // Use LIKE query to find the feature in the comma-separated list
                        $q->where('WaterfrontFeatures', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply swimming pool filter if provided
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Swimming Pool%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Swimming Pool%')
                    ->orWhere('PoolPrivateYN', true);
            });
        }

        // Apply tennis court filter if provided
        if ($request->has('tennis_court') && $request->boolean('tennis_court')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Tennis Court%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Tennis Court%');
            });
        }

        // Apply gated community filter if provided
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('CommunityFeatures', 'LIKE', '%Gated Community%')
                    ->orWhere('AssociationAmenities', 'LIKE', '%Gated%');
            });
        }

        // Apply penthouse filter if provided
        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Penthouse%')
                    ->orWhere('PropertySubType', 'LIKE', '%Penthouse%');
            });
        }

        // Apply pets allowed filter if provided
        if ($request->has('pets_allowed') && $request->boolean('pets_allowed')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('PetsAllowed', true)
                    ->orWhere('PetsAllowedYN', true);
            });
        }

        // Apply furnished filter if provided
        if ($request->has('furnished') && $request->boolean('furnished')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('Furnished', true);
            });
        }

        // Apply golf course filter if provided
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Golf Course%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Golf Course%');
            });
        }

        // Apply boat dock filter if provided
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('amenities', function ($q) {
                $q->where('AssociationAmenities', 'LIKE', '%Boat Dock%')
                    ->orWhere('CommunityFeatures', 'LIKE', '%Boat Dock%');
            });
        }

        // Apply parking spaces filter if provided
        if ($request->has('parking_spaces') && is_numeric($request->input('parking_spaces'))) {
            $query->whereHas('amenities', function ($q) use ($request) {
                $q->where('ParkingTotal', '>=', $request->input('parking_spaces'));
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDir);

        // Get paginated results
        $properties = $query->paginate($limit);

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $properties->items(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
                'has_more_pages' => $properties->hasMorePages()
            ]
        ]);
    }
}
