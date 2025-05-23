<?php

namespace App\Http\Controllers;

use App\Models\BridgeProperty;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{

    private $stateMap = [
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
        'district of columbia' => 'DC',
    ];

    /**
     * Convert state name to abbreviation if possible
     */
    protected function getStateAbbreviation($state)
    {
        $states = [
            'Alabama' => 'AL',
            'Alaska' => 'AK',
            'Arizona' => 'AZ',
            'Arkansas' => 'AR',
            'California' => 'CA',
            'Colorado' => 'CO',
            'Connecticut' => 'CT',
            'Delaware' => 'DE',
            'Florida' => 'FL',
            'Georgia' => 'GA',
            'Hawaii' => 'HI',
            'Idaho' => 'ID',
            'Illinois' => 'IL',
            'Indiana' => 'IN',
            'Iowa' => 'IA',
            'Kansas' => 'KS',
            'Kentucky' => 'KY',
            'Louisiana' => 'LA',
            'Maine' => 'ME',
            'Maryland' => 'MD',
            'Massachusetts' => 'MA',
            'Michigan' => 'MI',
            'Minnesota' => 'MN',
            'Mississippi' => 'MS',
            'Missouri' => 'MO',
            'Montana' => 'MT',
            'Nebraska' => 'NE',
            'Nevada' => 'NV',
            'New Hampshire' => 'NH',
            'New Jersey' => 'NJ',
            'New Mexico' => 'NM',
            'New York' => 'NY',
            'North Carolina' => 'NC',
            'North Dakota' => 'ND',
            'Ohio' => 'OH',
            'Oklahoma' => 'OK',
            'Oregon' => 'OR',
            'Pennsylvania' => 'PA',
            'Rhode Island' => 'RI',
            'South Carolina' => 'SC',
            'South Dakota' => 'SD',
            'Tennessee' => 'TN',
            'Texas' => 'TX',
            'Utah' => 'UT',
            'Vermont' => 'VT',
            'Virginia' => 'VA',
            'Washington' => 'WA',
            'West Virginia' => 'WV',
            'Wisconsin' => 'WI',
            'Wyoming' => 'WY',
        ];

        $state = trim($state);
        return $states[$state] ?? $state;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $propertyId = $request->input('property_id'); // Get property_id from request
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $query = BridgeProperty::query();

        // If we have a specific property ID, use that for exact matching
        if ($propertyId) {
            $query->where('id', $propertyId);
        }
        // Otherwise, apply search filter if provided
        else if ($search) {
            // Check if this is a city search
            if (preg_match('/^([^,]+),\s*([A-Z]{2})$/', $search, $matches)) {
                $city = trim($matches[1]);
                $state = $matches[2];
                $query->where('city', $city)
                    ->where('state_or_province', $state);
            }
            // Check if this is a state search
            else if (preg_match('/^([A-Z]{2})$/', $search, $matches)) {
                $state = $matches[1];
                $query->where('state_or_province', $state);
            }
            // Check if this is a postal code search
            else if (preg_match('/^(\d{5}),\s*([A-Z]{2})$/', $search, $matches)) {
                $postalCode = $matches[1];
                $state = $matches[2];
                $query->where('postal_code', $postalCode)
                    ->where('state_or_province', $state);
            }
            // Check if this might be a street address with city, state, postal code
            else if (preg_match('/^(\d+)\s+([^,]+),\s*([^,]+),\s*([A-Z]{2})\s*(\d{5})$/', $search, $addressMatches)) {
                $streetNumber = $addressMatches[1];
                $streetName = trim($addressMatches[2]);
                $city = trim($addressMatches[3]);
                $state = $addressMatches[4];
                $postalCode = $addressMatches[5];

                $query->where(function ($q) use ($streetNumber, $streetName, $city, $state, $postalCode) {
                    $q->where('street_number', $streetNumber)
                        ->where('street_name', 'like', $streetName . '%')
                        ->where('city', $city)
                        ->where('state_or_province', $state)
                        ->where('postal_code', $postalCode);
                });
            }
            // Check if this might be a street number and name without city/state
            else if (preg_match('/^(\d+)\s+(.+)$/', $search, $streetMatches)) {
                $streetNumber = $streetMatches[1];
                $streetName = trim($streetMatches[2]);

                // If it looks like a street address (number followed by text)
                $query->where(function ($q) use ($streetNumber, $streetName, $search) {
                    // Try exact match on street number and name
                    $q->where(function ($sq) use ($streetNumber, $streetName) {
                        $sq->where('street_number', $streetNumber)
                            ->where('street_name', 'like', $streetName . '%');
                    })
                        // Or try matching the full unparsed address
                        ->orWhere('unparsed_address', 'like', '%' . $search . '%');
                });
            }
            // General search
            else {
                $query->where(function ($q) use ($search) {
                    $q->where('unparsed_address', 'like', "%{$search}%")
                        ->orWhere('street_number', 'like', "%{$search}%")
                        ->orWhere('street_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state_or_province', 'like', "%{$search}%")
                        ->orWhere('postal_code', 'like', "%{$search}%");
                });
            }
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $properties = $query->paginate(15)->withQueryString();

        return view('properties', compact('properties'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('properties.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            // Add other validation rules as needed
        ]);

        Property::create($validated);

        return redirect()->route('properties.index')
            ->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BridgeProperty $property)
    {
        // Load all relationships to ensure complete data is available in the view
        $property->load([
            'details',
            'media',
            'schools',
            'financialDetails',
            'listAgent',
            'coListAgent',
            'buyerAgent',
            'coBuyerAgent',
            'listOffice',
            'coListOffice',
            'buyerOffice',
            'coBuyerOffice',
            'elementarySchool',
            'middleSchool',
            'highSchool',
            'features'
        ]);

        // Get all features grouped by category for easy display
        $featuresGrouped = collect();
        if ($property->features && $property->features->count() > 0) {
            // Make sure to eager load the category relationship
            $property->load('features.category');

            // Group features by category
            $featuresGrouped = $property->features->groupBy(function ($feature) {
                return $feature->category ? $feature->category->name : 'Other';
            });
        }

        return view('properties-show', compact('property', 'featuresGrouped'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        return view('properties.edit', compact('property'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            // Add other validation rules as needed
        ]);

        $property->update($validated);

        return redirect()->route('properties.index')
            ->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', 'Property deleted successfully.');
    }

    // In PropertyApiController.php or similar
    public function getPropertyDetails(Request $request, $id)
    {
        // Validate request parameters
        $request->validate([
            'property_sub_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|numeric',
            'max_baths' => 'nullable|numeric',
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
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:list_price,list_date,bathrooms_total_decimal,bedrooms_total,living_area,year_built,lot_size_area',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        // Start with the base query
        $query = BridgeProperty::with(['details', 'media', 'features', 'listAgent', 'listOffice']);

        // Apply the ID filter first
        $query->where('id', $id);

        // Apply property sub type filter
        if ($request->filled('property_sub_type')) {
            $query->where('property_sub_type', $request->property_sub_type);
        }

        // Apply price filters
        if ($request->filled('min_price')) {
            $query->where('list_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('list_price', '<=', $request->max_price);
        }

        // Apply bedroom filters
        if ($request->filled('min_beds')) {
            $query->where('bedrooms_total', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $query->where('bedrooms_total', '<=', $request->max_beds);
        }

        // Apply bathroom filters
        if ($request->filled('min_baths')) {
            $query->where('bathrooms_total_integer', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $query->where('bathrooms_total_integer', '<=', $request->max_baths);
        }

        // Apply living size filters
        if ($request->filled('min_living_size')) {
            $query->where('living_area', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $query->where('living_area', '<=', $request->max_living_size);
        }

        // Apply land size filters
        if ($request->filled('min_land_size')) {
            $query->where('lot_size_acres', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $query->where('lot_size_acres', '<=', $request->max_land_size);
        }

        // Apply year built filters
        if ($request->filled('min_year_built')) {
            $query->where('year_built', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $query->where('year_built', '<=', $request->max_year_built);
        }

        // Apply waterfront filter
        if ($request->has('waterfront')) {
            $waterfrontValue = $request->input('waterfront');
            $query->where('waterfront_yn', $waterfrontValue);
        }

        // Apply parking total filter
        if ($request->has('parking_spaces')) {
            $parkingTotal = $request->input('parking_spaces');
            $query->where('parking_total', $parkingTotal);
        }

        // Apply pets allowed filter
        if ($request->has('pets_allowed')) {
            $petsAllowedValue = filter_var($request->input('pets_allowed'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($petsAllowedValue) {
                $q->where('miamire_pets_allowed_yn', $petsAllowedValue);
            });
        }

        // Apply waterfront features filter
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            $featuresArray = explode(',', $waterfrontFeatures);
            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        $q->where('waterfront_features', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply furnished filter
        if ($request->has('furnished')) {
            $furnishedValue = filter_var($request->input('furnished'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($furnishedValue) {
                $q->where('furnished', $furnishedValue ? 'Yes' : 'No');
            });
        }

        // Apply amenity filters
        // Tennis courts - under Association Amenities
        if ($request->has('tennis_courts') && $request->boolean('tennis_courts')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Tennis Court%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Boat dock - under Association Amenities
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Boat Dock%')
                        ->orWhere('name', 'LIKE', '%Dock%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Golf course - under Association Amenities
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Golf Course%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Swimming pool - under Association Amenities
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Pool%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Gated community - under Community Features
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Gated Community%')
                        ->orWhere('name', 'LIKE', '%Gated%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Community Features');
                    });
            });
        }

        // Apply sorting if needed
        if ($request->filled('sort_by')) {
            $sortBy = $request->input('sort_by');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
        }

        // Get the property
        $property = $query->firstOrFail();

        // Format the response with all the property details
        return response()->json([
            'success' => true,
            'property' => [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'unit_number' => $property->unit_number,
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'property_type' => $property->property_type,
                'property_sub_type' => $property->property_sub_type,
                'status' => $property->standard_status,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'lot_size' => $property->lot_size_acres,
                'year_built' => $property->year_built,
                'description' => $property->public_remarks,
                'photos' => $property->media->map(function ($media) {
                    return [
                        'url' => $media->media_url,
                        'type' => $media->media_type
                    ];
                }),
                'features' => $property->features->pluck('name'),
                'agent' => $property->listAgent ? [
                    'name' => $property->listAgent->full_name,
                    'phone' => $property->listAgent->phone,
                    'email' => $property->listAgent->email
                ] : null,
                'office' => $property->listOffice ? [
                    'name' => $property->listOffice->name,
                    'phone' => $property->listOffice->phone
                ] : null,
                // 'waterfront' => $property->waterfront_yn,
                // 'parking_spaces' => $property->parking_total,
                // 'pets_allowed' => $property->details && isset($property->details->miamire_pets_allowed_yn) ? 
                //     $property->details->miamire_pets_allowed_yn : null,
                // 'furnished' => $property->details && isset($property->details->furnished) ? 
                //     $property->details->furnished : null,
                // 'waterfront_features' => $property->details && isset($property->details->waterfront_features) ? 
                //     $property->details->waterfront_features : null,
            ],
            // 'filters' => [
            //     'property_sub_type' => $request->input('property_sub_type'),
            //     'min_price' => $request->input('min_price'),
            //     'max_price' => $request->input('max_price'),
            //     'min_beds' => $request->input('min_beds'),
            //     'max_beds' => $request->input('max_beds'),
            //     'min_baths' => $request->input('min_baths'),
            //     'max_baths' => $request->input('max_baths'),
            //     'min_living_size' => $request->input('min_living_size'),
            //     'max_living_size' => $request->input('max_living_size'),
            //     'min_land_size' => $request->input('min_land_size'),
            //     'max_land_size' => $request->input('max_land_size'),
            //     'min_year_built' => $request->input('min_year_built'),
            //     'max_year_built' => $request->input('max_year_built'),
            //     'waterfront' => $request->has('waterfront') ? $request->boolean('waterfront') : null,
            //     'parking_spaces' => $request->input('parking_spaces'),
            //     'sort_by' => $request->input('sort_by'),
            //     'sort_dir' => $request->input('sort_dir')
            // ]
        ]);
    }

    // In PropertyApiController.php or similar
    public function getBuildingDetails(Request $request)
    {
        $request->validate([
            'street_number' => 'required|string',
            'street_name' => 'required|string',
            'building_name' => 'required|string',
            'city' => 'required|string',
            'type' => 'nullable|string|in:buy,rent,all',
            'property_sub_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|numeric',
            'max_baths' => 'nullable|numeric',
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
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:list_price,list_date,bathrooms_total_decimal,bedrooms_total,living_area,year_built,lot_size_area',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        $streetNumber = $request->input('street_number');
        $streetName = $request->input('street_name');
        $buildingName = $request->input('building_name');
        $city = $request->input('city');

        // Query to get all properties in this building
        $query = BridgeProperty::with(['details', 'media', 'features'])
            ->where('street_number', $streetNumber)
            ->where('street_name', $streetName)
            ->where('city', $city)
            ->whereHas('details', function ($q) use ($buildingName) {
                $q->where('building_name', $buildingName);
            });

        $type = $request->input('type', 'all');
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
            }
        }


        // Apply property sub type filter
        if ($request->filled('property_sub_type')) {
            $query->where('property_sub_type', $request->property_sub_type);
        }

        // Apply price filters
        if ($request->filled('min_price')) {
            $query->where('list_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('list_price', '<=', $request->max_price);
        }

        // Apply bedroom filters
        if ($request->filled('min_beds')) {
            $query->where('bedrooms_total', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $query->where('bedrooms_total', '<=', $request->max_beds);
        }

        // Apply bathroom filters
        if ($request->filled('min_baths')) {
            $query->where('bathrooms_total_integer', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $query->where('bathrooms_total_integer', '<=', $request->max_baths);
        }

        // Apply living size filters
        if ($request->filled('min_living_size')) {
            $query->where('living_area', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $query->where('living_area', '<=', $request->max_living_size);
        }

        // Apply land size filters
        if ($request->filled('min_land_size')) {
            $query->where('lot_size_acres', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $query->where('lot_size_acres', '<=', $request->max_land_size);
        }

        // Apply year built filters
        if ($request->filled('min_year_built')) {
            $query->where('year_built', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $query->where('year_built', '<=', $request->max_year_built);
        }

        // Apply waterfront filter
        if ($request->has('waterfront')) {
            $waterfrontValue = $request->input('waterfront');
            $query->where('waterfront_yn', $waterfrontValue);
        }

        // Apply parking total filter
        if ($request->has('parking_spaces')) {
            $parkingTotal = $request->input('parking_spaces');
            $query->where('parking_total', $parkingTotal);
        }

        // Apply pets allowed filter
        if ($request->has('pets_allowed')) {
            $petsAllowedValue = filter_var($request->input('pets_allowed'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($petsAllowedValue) {
                $q->where('miamire_pets_allowed_yn', $petsAllowedValue);
            });
        }

        // Apply waterfront features filter
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            $featuresArray = explode(',', $waterfrontFeatures);
            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        $q->where('waterfront_features', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply furnished filter
        if ($request->has('furnished')) {
            $furnishedValue = filter_var($request->input('furnished'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($furnishedValue) {
                $q->where('furnished', $furnishedValue ? 'Yes' : 'No');
            });
        }

        // Apply amenity filters
        // Tennis courts - under Association Amenities
        if ($request->has('tennis_courts') && $request->boolean('tennis_courts')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Tennis Court%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Boat dock - under Association Amenities
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Boat Dock%')
                        ->orWhere('name', 'LIKE', '%Dock%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Golf course - under Association Amenities
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Golf Course%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Swimming pool - under Association Amenities
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Pool%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Gated community - under Community Features
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Gated Community%')
                        ->orWhere('name', 'LIKE', '%Gated%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Community Features');
                    });
            });
        }

        // Get total count before pagination
        $totalCount = $query->count();

        // Apply sorting
        $sortBy = $request->input('sort_by', 'list_price');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        $limit = $request->input('limit', 12);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;
        $properties = $query->skip($offset)->take($limit)->get();

        // Get building details from the first property's details
        $buildingDetails = null;
        $representativeProperty = $properties->first();
        if ($representativeProperty && $representativeProperty->details) {
            $buildingDetails = [
                'building_name' => $representativeProperty->details->building_name,
                'year_built' => $representativeProperty->year_built,
                'address' => trim($streetNumber . ' ' . $streetName),
                'city' => $representativeProperty->city,
                'state' => $representativeProperty->state_or_province,
                'postal_code' => $representativeProperty->postal_code,
                'total_units' => $totalCount,
                'property_sub_type' => $representativeProperty->property_sub_type,
                // Add other building details as needed
            ];
        }

        // Format the properties for the response
        $formattedProperties = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'unit_number' => $property->unit_number,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'status' => $property->standard_status,
                'photos' => $property->media->take(1)->map(function ($media) {
                    return $media->media_url;
                }),
                // Add other property details as needed
            ];
        });

        return response()->json([
            'success' => true,
            'building' => $buildingDetails,
            'properties' => $formattedProperties,
            'total_properties' => $totalCount,
            'price_range' => [
                'min' => $properties->min('list_price'),
                'max' => $properties->max('list_price')
            ],
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'last_page' => ceil($totalCount / $limit),
                'from' => $offset + 1,
                'to' => min($offset + $limit, $totalCount),
                'has_more_pages' => ($page * $limit) < $totalCount
            ],
            // 'filters' => [
            //     'property_sub_type' => $request->input('property_sub_type'),
            //     'min_price' => $request->input('min_price'),
            //     'max_price' => $request->input('max_price'),
            //     'min_beds' => $request->input('min_beds'),
            //     'max_beds' => $request->input('max_beds'),
            //     'min_baths' => $request->input('min_baths'),
            //     'max_baths' => $request->input('max_baths'),
            //     'min_living_size' => $request->input('min_living_size'),
            //     'max_living_size' => $request->input('max_living_size'),
            //     'min_land_size' => $request->input('min_land_size'),
            //     'max_land_size' => $request->input('max_land_size'),
            //     'min_year_built' => $request->input('min_year_built'),
            //     'max_year_built' => $request->input('max_year_built'),
            //     'waterfront' => $request->has('waterfront') ? $request->boolean('waterfront') : null,
            //     'parking_spaces' => $request->input('parking_spaces'),
            //     'sort_by' => $sortBy,
            //     'sort_dir' => $sortDir
            // ]
        ]);
    }

    // Helper method to format location name
    public function getPropertiesByLocation(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'type' => 'nullable|string|in:buy,rent,all',
            'property_sub_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|numeric',
            'max_baths' => 'nullable|numeric',
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
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:list_price,list_date,bathrooms_total_decimal,bedrooms_total,living_area,year_built,lot_size_area',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        // Start with the BridgeProperty model
        $query = BridgeProperty::with(['details', 'media', 'features']);

        // Apply location filters
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('state')) {
            $query->where('state_or_province', $request->state);
        }

        if ($request->filled('postal_code')) {
            $query->where('postal_code', $request->postal_code);
        }

        // Apply type filter (buy/rent/all)
        $type = $request->input('type', 'all');
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
            }
        }

        if ($request->filled('property_sub_type')) {
            $query->where('property_sub_type', $request->property_sub_type);
        }

        // Apply price filters
        if ($request->filled('min_price')) {
            $query->where('list_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('list_price', '<=', $request->max_price);
        }

        // Apply bedroom filters
        if ($request->filled('min_beds')) {
            $query->where('bedrooms_total', '>=', $request->min_beds);
        }

        if ($request->filled('max_beds')) {
            $query->where('bedrooms_total', '<=', $request->max_beds);
        }

        // Apply bathroom filters
        if ($request->filled('min_baths')) {
            $query->where('bathrooms_total_integer', '>=', $request->min_baths);
        }

        if ($request->filled('max_baths')) {
            $query->where('bathrooms_total_integer', '<=', $request->max_baths);
        }

        // Apply living size filters
        if ($request->filled('min_living_size')) {
            $query->where('living_area', '>=', $request->min_living_size);
        }

        if ($request->filled('max_living_size')) {
            $query->where('living_area', '<=', $request->max_living_size);
        }

        // Apply land size filters
        if ($request->filled('min_land_size')) {
            $query->where('lot_size_acres', '>=', $request->min_land_size);
        }

        if ($request->filled('max_land_size')) {
            $query->where('lot_size_acres', '<=', $request->max_land_size);
        }

        // Apply year built filters
        if ($request->filled('min_year_built')) {
            $query->where('year_built', '>=', $request->min_year_built);
        }

        if ($request->filled('max_year_built')) {
            $query->where('year_built', '<=', $request->max_year_built);
        }

        // Apply waterfront filter
        if ($request->has('waterfront')) {
            $waterfrontValue = $request->input('waterfront'); // this will be '0' or '1'
            $query->where('waterfront_yn', $waterfrontValue);
        }

        // Apply parking total filter
        if ($request->has('parking_total')) {
            $parkingTotal = $request->input('parking_total');
            $query->where('parking_total', $parkingTotal);
        }

        if ($request->has('pets_allowed')) {
            $petsAllowedValue = filter_var($request->input('pets_allowed'), FILTER_VALIDATE_BOOLEAN);

            $query->whereHas('details', function ($q) use ($petsAllowedValue) {
                $q->where('miamire_pets_allowed_yn', $petsAllowedValue);
            });
        }

        // Apply waterfront features filter
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            $featuresArray = explode(',', $waterfrontFeatures);

            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        $q->where('waterfront_features', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Tennis courts - under Association Amenities
        if ($request->has('tennis_courts') && $request->boolean('tennis_courts')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Tennis Court%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Boat dock - under Association Amenities
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Boat Dock%')
                        ->orWhere('name', 'LIKE', '%Dock%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Golf course - under Association Amenities
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Golf Course%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Gated community - under Community Features
        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Gated Community%')
                        ->orWhere('name', 'LIKE', '%Gated%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Community Features');
                    });
            });
        }

        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Penthouse%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Architectural Style');
                    });
            });
        }

        // Get total count before pagination
        $totalCount = $query->count();

        // Apply sorting
        $sortBy = $request->input('sort_by', 'list_price');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Add secondary sorting for better results

        // Apply pagination
        $limit = $request->input('limit', 12);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;
        $properties = $query->skip($offset)->take($limit)->get();

        // Get media for properties
        $propertyIds = $properties->pluck('id')->toArray();
        $mediaQuery = DB::table('bridge_property_media')
            ->whereIn('property_id', $propertyIds)
            ->orderBy('order', 'asc');
        $media = $mediaQuery->get()->groupBy('property_id');

        // Format the properties for the response
        $formattedProperties = $properties->map(function ($property) use ($media) {
            $propertyMedia = $media[$property->id] ?? collect([]);

            return [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'unit_number' => $property->unit_number,
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'lot_size' => $property->lot_size_acres,
                'year_built' => $property->year_built,
                'property_type' => $property->property_type,
                'property_sub_type' => $property->property_sub_type,
                'status' => $property->standard_status,
                'photos' => $propertyMedia->map(function ($media) {
                    return $media->media_url;
                }),
            ];
        });

        // Get location information for the header/title
        $locationInfo = [
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'postal_code' => $request->input('postal_code'),
            'display_name' => $this->formatLocationName($request),
            'total_properties' => $totalCount
        ];

        // Format the response
        return response()->json([
            'success' => true,
            'location' => $locationInfo,
            'properties' => $formattedProperties,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'last_page' => ceil($totalCount / $limit),
                'from' => $offset + 1,
                'to' => min($offset + $limit, $totalCount),
                'has_more_pages' => ($page * $limit) < $totalCount
            ],
            // 'filters' => [
            //     'type' => $type,
            //     'property_type' => $request->input('property_type'),
            //     'property_sub_type' => $request->input('property_sub_type'),
            //     'min_price' => $request->input('min_price'),
            //     'max_price' => $request->input('max_price'),
            //     'min_beds' => $request->input('min_beds'),
            //     'max_beds' => $request->input('max_beds'),
            //     'min_baths' => $request->input('min_baths'),
            //     'max_baths' => $request->input('max_baths'),
            //     'min_living_size' => $request->input('min_living_size'),
            //     'max_living_size' => $request->input('max_living_size'),
            //     'min_land_size' => $request->input('min_land_size'),
            //     'max_land_size' => $request->input('max_land_size'),
            //     'min_year_built' => $request->input('min_year_built'),
            //     'max_year_built' => $request->input('max_year_built'),
            //     'waterfront' => $request->has('waterfront') ? $request->boolean('waterfront') : null,
            //     'parking_spaces' => $request->input('parking_spaces'),
            //     'sort_by' => $sortBy,
            //     'sort_dir' => $sortDir
            // ]
        ]);
    }

    /**
     * Helper method to format location name
     */
    private function formatLocationName(Request $request)
    {
        $parts = [];

        if ($request->has('city')) {
            $parts[] = $request->input('city');
        }

        if ($request->has('state')) {
            $parts[] = $request->input('state');
        }

        if ($request->has('postal_code')) {
            $parts[] = $request->input('postal_code');
        }

        return implode(', ', $parts);
    }

    public function getPropertyByID($id)
    {
        $property = BridgeProperty::with(['details', 'media', 'features', 'listAgent', 'listOffice'])
            ->findOrFail($id);

        // Format the response with all the property details
        return response()->json([
            'property' => [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'listing_id' => $property->listing_id,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'unit_number' => $property->unit_number,
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'property_type' => $property->property_type,
                'property_sub_type' => $property->property_sub_type,
                'status' => $property->standard_status,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'lot_size' => $property->lot_size_acres,
                'year_built' => $property->year_built,
                'description' => $property->public_remarks,
                'photos' => $property->media->map(function ($media) {
                    return [
                        'url' => $media->media_url,
                        'type' => $media->media_type
                    ];
                }),
                'features' => $property->features->pluck('name'),
                'agent' => $property->listAgent ? [
                    'name' => $property->listAgent->full_name,
                    'phone' => $property->listAgent->phone,
                    'email' => $property->listAgent->email
                ] : null,
                'office' => $property->listOffice ? [
                    'name' => $property->listOffice->name,
                    'phone' => $property->listOffice->phone
                ] : null,
            ]
        ]);
    }


    public function getProperties(Request $request)
    {
        // dd("dfdsf");
        if (!$request->has('type') || $request->type === null) {
            $request->merge(['type' => 'buy']);
        }

        // Validate common request parameters
        $validationRules = [
            'property_id' => 'nullable|integer',
            'street_number' => 'nullable|string',
            'street_name' => 'nullable|string',
            'building_name' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'type' => 'nullable|string|in:buy,rent,sold,all',
            'property_sub_type' => 'nullable|string',
            'features' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|numeric',
            'max_baths' => 'nullable|numeric',
            'min_living_size' => 'nullable|numeric',
            'max_living_size' => 'nullable|numeric',
            'min_land_size' => 'nullable|numeric',
            'max_land_size' => 'nullable|numeric',
            'min_year_built' => 'nullable|integer|min:1800|max:2025',
            'max_year_built' => 'nullable|integer|min:1800|max:2025',
            'parking_spaces' => 'nullable|integer',
            'waterfront' => 'nullable|boolean',
            'waterfront_description' => 'nullable|string',
            'pets_allowed' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'swimming_pool' => 'nullable|boolean',
            'golf_course' => 'nullable|boolean',
            'tennis_courts' => 'nullable|boolean',
            'gated_community' => 'nullable|boolean',
            'boat_dock' => 'nullable|boolean',
            'penthouse' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:list_price,list_date,bathrooms_total_decimal,bedrooms_total,living_area,year_built,lot_size_area',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];

        // Determine the mode based on parameters
        $mode = $this->determineMode($request);

        if (!$mode) {
            $mode = 'all'; // Default to getting all properties
        }

        // Add mode-specific validation rules
        if ($mode === 'property') {
            $validationRules['property_id'] = 'required|string';
        } elseif ($mode === 'building') {
            $validationRules['street_number'] = 'required|string';
            $validationRules['street_name'] = 'required|string';
        } elseif ($mode === 'location') {
            // At least one location parameter is required
            if (!$request->filled('city') && !$request->filled('state') && !$request->filled('postal_code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one location parameter (city, state, or postal_code) is required'
                ], 422);
            }
        }

        $request->validate($validationRules);

        // Base API URL and access token
        $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
        $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';

        // Build query parameters for the API request
        $queryParams = [
            'access_token' => $accessToken,
            'limit' => $request->input('limit', 24),
            'offset' => ($request->input('page', 1) - 1) * $request->input('limit', 24),
        ];

        // Apply mode-specific filters
        if ($mode === 'property') {
            $queryParams['ListingId'] = $request->property_id;
        } elseif ($mode === 'building') {
            $queryParams['StreetNumber'] = $request->street_number;
            $queryParams['StreetName'] = $request->street_name;
            $queryParams['BuildingName'] = $request->building_name;
        } elseif ($mode === 'location') {
            if ($request->filled('city')) {
                $queryParams['City'] = $request->city;
            }
            if ($request->filled('state')) {
                $queryParams['StateOrProvince'] = $request->state;
            }
            if ($request->filled('postal_code')) {
                $queryParams['PostalCode'] = $request->postal_code;
            }
        }
        
        // Apply common filters
        $this->applyBridgeApiFilters($queryParams, $request);
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'list_price');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Map our sort fields to Bridge API field names
        $sortFieldMap = [
            'list_price' => 'ListPrice',
            'list_date' => 'ListDate',
            'bathrooms_total_decimal' => 'BathroomsTotalDecimal',
            'bedrooms_total' => 'BedroomsTotal',
            'living_area' => 'LivingArea',
            'year_built' => 'YearBuilt',
            'lot_size_area' => 'LotSizeArea'
        ];

        $bridgeSortField = $sortFieldMap[$sortBy] ?? 'ListPrice';
        $queryParams['order'] = $bridgeSortField . ' ' . strtoupper($sortDir);

        try {
            // Make the API request
        $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';

        // dd($queryParams,"ggdfgd");
        $response = Http::withOptions([
        'verify' => false, // Disable SSL verification
    ])->get($baseUrl, $queryParams);

            if (!$response->successful()) {
                Log::error('Bridge API request failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'query' => $queryParams
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch properties from external API'
                ], 500);
            }

            $data = $response->json();
            $properties = $data['bundle'] ?? [];
            $totalCount = $data['total'] ?? count($properties);

            // Format the response based on the mode
            if ($mode === 'property' && !empty($properties)) {
                return $this->formatBridgePropertyResponse($properties[0]);
            } elseif ($mode === 'building') {
                return $this->formatBridgeBuildingResponse($properties, $request, $totalCount);
            } else {
                return $this->formatBridgeLocationResponse($properties, $request, $totalCount);
            }
        } catch (\Exception $e) {
            Log::error('Exception when fetching properties from Bridge API', [
                'exception' => $e->getMessage(),
                'query' => $queryParams
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching properties'
            ], 500);
        }
    }

    /**
     * Apply common filters to Bridge API query parameters
     * 
     * @param array &$queryParams The query parameters array to modify
     * @param Request $request The request object
     */
    private function applyBridgeApiFilters(&$queryParams, Request $request)
    {
        // Property type filters
        if ($request->filled('type')) {
            if ($request->type === 'buy') {
                $queryParams['PropertyType'] = 'Residential';
                $queryParams['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
            } elseif ($request->type === 'rent') {
                $queryParams['PropertyType'] = 'Residential Lease';
                $queryParams['StandardStatus.in'] = 'Active,Active Under Contract,Pending';
                // $queryParams['PropertySubType'] = 'Rental';
            }else if ($request->type === 'sold') {
                $queryParams['PropertyType.in'] = 'Residential,Residential Lease';
                $queryParams['StandardStatus'] = 'Closed';
            }
        }

        if ($request->filled('property_sub_type')) {
            $propertySubTypes = $request->property_sub_type;

            // Check if it's a comma-separated list
            if (strpos($propertySubTypes, ',') !== false) {
                // For multiple values, use the .in operator with the comma-separated list
                $queryParams['PropertySubType.in'] = $propertySubTypes;
            } else {
                // For a single value, use the exact match
                $queryParams['PropertySubType'] = $propertySubTypes;
            }
        }

        // Price range
        if ($request->filled('min_price')) {
            $queryParams['ListPrice.gte'] = $request->min_price;
        }
        if ($request->filled('max_price')) {
            $queryParams['ListPrice.lte'] = $request->max_price;
        }

        // Bedrooms
        if ($request->filled('min_beds')) {
            $queryParams['BedroomsTotal.gte'] = $request->min_beds;
        }
        if ($request->filled('max_beds')) {
            $queryParams['BedroomsTotal.lte'] = $request->max_beds;
        }

        // Bathrooms
        if ($request->filled('min_baths')) {
            $queryParams['BathroomsTotalDecimal.gte'] = $request->min_baths;
        }
        if ($request->filled('max_baths')) {
            $queryParams['BathroomsTotalDecimal.lte'] = $request->max_baths;
        }

        // Living size
        if ($request->filled('min_living_size')) {
            $queryParams['LivingArea.gte'] = $request->min_living_size;
        }
        if ($request->filled('max_living_size')) {
            $queryParams['LivingArea.lte'] = $request->max_living_size;
        }

        // Land size
        if ($request->filled('min_land_size')) {
            $queryParams['LotSizeSquareFeet.gte'] = $request->min_land_size;
        }
        if ($request->filled('max_land_size')) {
            $queryParams['LotSizeSquareFeet.lte'] = $request->max_land_size;
        }

        // Year built
        if ($request->filled('min_year_built')) {
            $queryParams['YearBuilt.gte'] = $request->min_year_built;
        }
        if ($request->filled('max_year_built')) {
            $queryParams['YearBuilt.lte'] = $request->max_year_built;
        }

        // Amenities and features
        if ($request->filled('parking_spaces')) {
            $queryParams['ParkingTotal'] = $request->parking_spaces;
        }

        // if ($request->has('pets_allowed') && $request->boolean('pets_allowed')) {
        //     $queryParams['MIAMIRE_PetsAllowedYN'] = true;
        // }

        // if ($request->has('waterfront') && $request->boolean('waterfront')) {
        //     // Bridge API uses text values for Furnished field
        //     $queryParams['WaterfrontYN'] = ;
        // }
        
        // Handle feature filtering with support for comma-separated lists
        if ($request->filled('features')) {
            $features = $request->features;
            $featureArray = explode(',', $features);

            // Trim whitespace from each feature
            $featureArray = array_map('trim', $featureArray);

            // Group features by their target fields
            $associationAmenities = [];
            $communityFeatures = [];
            $architecturalStyles = [];
            $propertySubTypes = [];
            $booleanFeatures = [];

            foreach ($featureArray as $feature) {
                if (stripos($feature, 'Tennis Court') !== false) {
                    $associationAmenities[] = 'Tennis Court(s)';
                } else if (stripos($feature, 'Boat Docks') !== false || stripos($feature, 'boat') !== false) {
                    $associationAmenities[] = 'Boat Dock';
                } else if (stripos($feature, 'Golf Course') !== false) {
                    $associationAmenities[] = 'Golf Course';
                } else if (stripos($feature, 'Swimming Pool') !== false) {
                    $associationAmenities[] = 'Pool';
                } else if (stripos($feature, 'Gated Community') !== false) {
                    $communityFeatures[] = 'Gated Community';
                } else if (stripos($feature, 'Penthouse') !== false) {
                    $architecturalStyles[] = 'Penthouse';
                }
                else if (stripos($feature, 'Waterfront') !== false) {
                    // dd("Dfsdfg");
                    $queryParams["[WaterfrontYN][eq]"] = 'true';
                }
                else if (stripos($feature, 'Pet Allowed') !== false) {
                    $queryParams["[MIAMIRE_PetsAllowedYN][eq]"] = 'true';
                }
                // Add more feature mappings as needed
            }

            // Add the grouped features to the query params using Bridge API format
            $andIndex = 1; // Start index for AND conditions

            if (!empty($associationAmenities)) {
                $queryParams["and[{$andIndex}][AssociationAmenities][in]"] = implode(',', $associationAmenities);
                $andIndex++;
            }

            if (!empty($communityFeatures)) {
                $queryParams["and[{$andIndex}][CommunityFeatures][in]"] = implode(',', $communityFeatures);
                $andIndex++;
            }

            if (!empty($architecturalStyles)) {
                $queryParams["and[{$andIndex}][ArchitecturalStyle][in]"] = implode(',', $architecturalStyles);
                $andIndex++;
            }
        }


        // Special case for waterfront features
        if ($request->filled('waterfront_description')) {
            // Get the waterfront feature value
            $waterfrontFeature = $request->waterfront_description;

            // Use exact match with Bridge API's format
            // Find the next available index for AND conditions
            $andIndex = isset($queryParams['and']) ? count($queryParams['and']) + 1 : 1;

            $queryParams["and[{$andIndex}][WaterfrontFeatures][eq]"] = $waterfrontFeature;
        }
    }

    /**
     * Format response for a single property from Bridge API
     * 
     * @param array $property The property data
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatBridgePropertyResponse($property)
    {
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'property' => $this->transformBridgeProperty($property)
        ]);
    }

    /**
     * Format response for building properties from Bridge API
     * 
     * @param array $properties The properties data
     * @param Request $request The request object
     * @param int $totalCount Total count of properties
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatBridgeBuildingResponse($properties, $request, $totalCount)
    {
        $transformedProperties = array_map([$this, 'transformBridgeProperty'], $properties);

        return response()->json([
            'success' => true,
            'properties' => $transformedProperties,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $request->input('limit', 24),
                'current_page' => $request->input('page', 1),
                'last_page' => ceil($totalCount / $request->input('limit', 24))
            ]
        ]);
    }

    /**
     * Format response for location properties from Bridge API
     * 
     * @param array $properties The properties data
     * @param Request $request The request object
     * @param int $totalCount Total count of properties
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatBridgeLocationResponse($properties, $request, $totalCount)
    {
        $transformedProperties = array_map([$this, 'transformBridgeProperty'], $properties);

        return response()->json([
            'success' => true,
            'properties' => $transformedProperties,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $request->input('limit', 24),
                'current_page' => $request->input('page', 1),
                'last_page' => ceil($totalCount / $request->input('limit', 24))
            ]
        ]);
    }

    /**
     * Transform a Bridge API property to our expected format
     * 
     * @param array $property The property data from Bridge API
     * @return array Transformed property data
     */
    /**
     * Transform a Bridge API property to our expected format
     * 
     * @param array $property The property data from Bridge API
     * @return array Transformed property data
     */
    private function transformBridgeProperty($property)
    {
        // Map Bridge API fields to our expected format
        return [
            // 'id' => $property['ListingId'] ?? null,
            'listing_id' => $property['ListingId'] ?? null,
            'street_number' => $property['StreetNumber'] ?? null,
            'street_name' => $property['StreetName'] ?? null,
            'address' => trim($property['StreetNumber'] . ' ' . $property['StreetName']),
            'building_name' => $property['BuildingName'] ?? null,
            'city' => $property['City'] ?? null,
            'state' => $property['StateOrProvince'] ?? null,
            'postal_code' => $property['PostalCode'] ?? null,
            'list_price' => $property['ListPrice'] ?? null,
            'list_date' => $property['ListDate'] ?? null,
            'bedrooms' => $property['BedroomsTotal'] ?? null,
            'bathrooms' => $property['BathroomsTotalDecimal'] ?? null,
            'living_area' => $property['LivingArea'] ?? null,
            'lot_size' => $property['LotSizeArea'] ?? null,
            'year_built' => $property['YearBuilt'] ?? null,
            'property_type' => $property['PropertyType'] ?? null,
            'property_sub_type' => $property['PropertySubType'] ?? null,
            'description' => $property['PublicRemarks'] ?? null,
            'status' => $property['StandardStatus'] ?? null,
            // 'features' => [
            //     'waterfront' => ($property['WaterfrontYN'] ?? 'N') === 'Y',
            //     'pets_allowed' => ($property['PetsAllowedYN'] ?? 'N') === 'Y',
            //     'furnished' => ($property['FurnishedYN'] ?? 'N') === 'Y',
            //     'swimming_pool' => ($property['PoolYN'] ?? 'N') === 'Y',
            //     'golf_course' => ($property['GolfCourseYN'] ?? 'N') === 'Y',
            //     'tennis_courts' => ($property['TennisYN'] ?? 'N') === 'Y',
            //     'gated_community' => ($property['SecurityFeaturesYN'] ?? 'N') === 'Y',
            //     'boat_dock' => ($property['BoatDockYN'] ?? 'N') === 'Y',
            //     'penthouse' => ($property['PenthouseYN'] ?? 'N') === 'Y',
            //     'waterfront_features' => $property['WaterBodyName'] ?? null,
            //     'parking_spaces' => $property['ParkingSpaces'] ?? null,
            // ],
            'media' => $this->extractMediaFromBridgeProperty($property),
            'location' => [
                'latitude' => $property['Latitude'] ?? null,
                'longitude' => $property['Longitude'] ?? null,
            ],
            'unparsed_address' => $property['UnparsedAddress'] ?? null,
        ];
    }

    /**
     * Extract media items from Bridge API property data
     * 
     * @param array $property The property data from Bridge API
     * @return array Media items
     */
    private function extractMediaFromBridgeProperty($property)
    {
        $media = [];

        // Extract photos from the Media array if available
        if (isset($property['Media']) && is_array($property['Media'])) {
            foreach ($property['Media'] as $mediaItem) {
                if (isset($mediaItem['MediaURL']) && isset($mediaItem['MediaCategory'])) {
                    $media[] = [
                        'url' => $mediaItem['MediaURL'],
                        'type' => $mediaItem['MediaCategory'],
                        'description' => $mediaItem['MediaDescription'] ?? null,
                    ];
                }
            }
        }

        return $media;
    }

    /**
     * Determine the mode of operation based on request parameters
     * 
     * @param Request $request The request object
     * @return string|null The mode (property, building, location, or null)
     */
    private function determineMode(Request $request)
    {
        if ($request->filled('property_id')) {
            return 'property';
        } elseif ($request->filled('street_number') && $request->filled('street_name')) {
            return 'building';
        } elseif ($request->filled('city') || $request->filled('state') || $request->filled('postal_code')) {
            return 'location';
        }

        return null;
    }































    // public function getProperties(Request $request)
    // {
    //     // Validate common request parameters
    //     $validationRules = [
    //         'property_id' => 'nullable|integer',
    //         'street_number' => 'nullable|string',
    //         'street_name' => 'nullable|string',
    //         'building_name' => 'nullable|string',
    //         'city' => 'nullable|string',
    //         'state' => 'nullable|string',
    //         'postal_code' => 'nullable|string',
    //         'type' => 'nullable|string|in:buy,rent,all',
    //         'property_sub_type' => 'nullable|string',
    //         'min_price' => 'nullable|numeric',
    //         'max_price' => 'nullable|numeric',
    //         'min_beds' => 'nullable|integer',
    //         'max_beds' => 'nullable|integer',
    //         'min_baths' => 'nullable|numeric',
    //         'max_baths' => 'nullable|numeric',
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
    //         'penthouse' => 'nullable|boolean',
    //         'limit' => 'nullable|integer|min:1',
    //         'page' => 'nullable|integer|min:1',
    //         'sort_by' => 'nullable|string|in:list_price,list_date,bathrooms_total_decimal,bedrooms_total,living_area,year_built,lot_size_area',
    //         'sort_dir' => 'nullable|string|in:asc,desc',
    //     ];

    //     // Determine the mode based on parameters
    //     $mode = $this->determineMode($request);

    //     if (!$mode) {
    //         $mode = 'all'; // Default to getting all properties
    //     }

    //     // Add mode-specific validation rules
    //     if ($mode === 'property') {
    //         $validationRules['property_id'] = 'required|integer';
    //     } elseif ($mode === 'building') {
    //         $validationRules['street_number'] = 'required|string';
    //         $validationRules['street_name'] = 'required|string';
    //         // $validationRules['building_name'] = 'required|string';
    //         // $validationRules['city'] = 'required|string';
    //     } elseif ($mode === 'location') {
    //         // At least one location parameter is required
    //         if (!$request->filled('city') && !$request->filled('state') && !$request->filled('postal_code')) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'At least one location parameter (city, state, or postal_code) is required'
    //             ], 422);
    //         }
    //     }

    //     $request->validate($validationRules);

    //     // Start with the base query
    //     $query = BridgeProperty::with(['details', 'media', 'features']);

    //     // Apply mode-specific filters
    //     if ($mode === 'property') {
    //         $query->where('id', $request->property_id);
    //     } elseif ($mode === 'building') {
    //         $query->where('street_number', $request->street_number)
    //             ->where('street_name', $request->street_name);
    //     } elseif ($mode === 'location') {
    //         if ($request->filled('city')) {
    //             $query->where('city', $request->city);
    //         }
    //         if ($request->filled('state')) {
    //             $query->where('state_or_province', $request->state);
    //         }
    //         if ($request->filled('postal_code')) {
    //             $query->where('postal_code', $request->postal_code);
    //         }
    //     }

    //     // Apply common filters
    //     $this->applyCommonFilters($query, $request);

    //     // Get total count before pagination (for building and location modes)
    //     $totalCount = ($mode !== 'property') ? $query->count() : 1;

    //     // Apply sorting
    //     $sortBy = $request->input('sort_by', 'list_price');
    //     $sortDir = $request->input('sort_dir', 'asc');
    //     $query->orderBy($sortBy, $sortDir);

    //     // Apply pagination for building and location modes
    //     if ($mode !== 'property') {
    //         $limit = $request->input('limit', 12);
    //         $page = $request->input('page', 1);
    //         $offset = ($page - 1) * $limit;
    //         $properties = $query->skip($offset)->take($limit)->get();
    //     } else {
    //         // For single property mode, just get the first result
    //         $properties = $query->get();
    //     }

    //     // Format the response based on the mode
    //     if ($mode === 'property') {
    //         return $this->formatPropertyResponse($properties->first());
    //     } elseif ($mode === 'building') {
    //         return $this->formatBuildingResponse($properties, $request, $totalCount);
    //     } else {
    //         return $this->formatLocationResponse($properties, $request, $totalCount);
    //     }
    // }

    /**
     * Determine which mode to use based on request parameters
     */
    // protected function determineMode(Request $request)
    // {
    //     if ($request->filled('property_id')) {
    //         return 'property';
    //     }

    //     if (
    //         $request->filled('street_number') &&
    //         $request->filled('street_name') &&
    //         $request->filled('building_name')
    //     ) {
    //         return 'building';
    //     }


    //     if (
    //         $request->filled('city') ||
    //         $request->filled('state') ||
    //         $request->filled('postal_code')
    //     ) {
    //         return 'location';
    //     }

    //     return null; // fallback to "all" mode
    // }


    /**
     * Apply common filters to the query
     */
    private function applyCommonFilters($query, Request $request)
    {
        // Apply type filter (buy/rent/all)
        $type = $request->input('type', 'all');
        if ($type && $type !== 'all') {
            switch (strtolower($type)) {
                case 'buy':
                    // Properties for sale
                    $query->whereNotIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
                case 'rent':
                    // Properties for rent
                    $query->whereIn('property_type', ['Residential Lease', 'CommercialLease']);
                    break;
            }
        }

        // Apply property sub type filter
        // if ($request->filled('property_sub_type')) {
        //     $query->where('property_sub_type', $request->property_sub_type);
        // }

        if ($request->filled('property_sub_type')) {
            $propertySubTypes = explode(',', $request->property_sub_type);
            // Trim whitespace from each value
            $propertySubTypes = array_map('trim', $propertySubTypes);

            if (count($propertySubTypes) > 1) {
                $query->whereIn('property_sub_type', $propertySubTypes);
            } else {
                $query->where('property_sub_type', $propertySubTypes[0]);
            }
        }

        // Apply price filters
        if ($request->filled('min_price')) {
            $query->where('list_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('list_price', '<=', $request->max_price);
        }

        // Apply bedroom filters
        if ($request->filled('min_beds')) {
            $query->where('bedrooms_total', '>=', $request->min_beds);
        }
        if ($request->filled('max_beds')) {
            $query->where('bedrooms_total', '<=', $request->max_beds);
        }

        // Apply bathroom filters
        if ($request->filled('min_baths')) {
            $query->where('bathrooms_total_integer', '>=', $request->min_baths);
        }
        if ($request->filled('max_baths')) {
            $query->where('bathrooms_total_integer', '<=', $request->max_baths);
        }

        // Apply living size filters
        if ($request->filled('min_living_size')) {
            $query->where('living_area', '>=', $request->min_living_size);
        }
        if ($request->filled('max_living_size')) {
            $query->where('living_area', '<=', $request->max_living_size);
        }

        // Apply land size filters
        if ($request->filled('min_land_size')) {
            $query->where('lot_size_acres', '>=', $request->min_land_size);
        }
        if ($request->filled('max_land_size')) {
            $query->where('lot_size_acres', '<=', $request->max_land_size);
        }

        // Apply year built filters
        if ($request->filled('min_year_built')) {
            $query->where('year_built', '>=', $request->min_year_built);
        }
        if ($request->filled('max_year_built')) {
            $query->where('year_built', '<=', $request->max_year_built);
        }

        // Apply waterfront filter
        if ($request->has('waterfront')) {
            $waterfrontValue = $request->input('waterfront');
            $query->where('waterfront_yn', $waterfrontValue);
        }

        // Apply parking total filter
        if ($request->has('parking_spaces')) {
            $parkingTotal = $request->input('parking_spaces');
            $query->where('parking_total', $parkingTotal);
        }

        // Apply pets allowed filter
        if ($request->has('pets_allowed')) {
            $petsAllowedValue = filter_var($request->input('pets_allowed'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($petsAllowedValue) {
                $q->where('miamire_pets_allowed_yn', $petsAllowedValue);
            });
        }

        // Apply waterfront features filter
        if ($request->filled('waterfront_features')) {
            $waterfrontFeatures = $request->input('waterfront_features');
            $featuresArray = explode(',', $waterfrontFeatures);
            $query->whereHas('details', function ($q) use ($featuresArray) {
                foreach ($featuresArray as $feature) {
                    $feature = trim($feature);
                    if (!empty($feature)) {
                        $q->where('waterfront_features', 'LIKE', '%' . $feature . '%');
                    }
                }
            });
        }

        // Apply furnished filter
        if ($request->has('furnished')) {
            $furnishedValue = filter_var($request->input('furnished'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('details', function ($q) use ($furnishedValue) {
                $q->where('furnished', $furnishedValue ? 'Yes' : 'No');
            });
        }

        // Apply amenity filters
        // Tennis courts - under Association Amenities
        if ($request->has('tennis_courts') && $request->boolean('tennis_courts')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Tennis Court%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Boat dock - under Association Amenities
        if ($request->has('boat_dock') && $request->boolean('boat_dock')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Boat Dock%')
                        ->orWhere('name', 'LIKE', '%Dock%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Golf course - under Association Amenities
        if ($request->has('golf_course') && $request->boolean('golf_course')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Golf Course%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        if ($request->has('gated_community') && $request->boolean('gated_community')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Gated Community%')
                        ->orWhere('name', 'LIKE', '%Gated%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Community Features');
                    });
            });
        }

        // Penthouse - under Architectural Style
        if ($request->has('penthouse') && $request->boolean('penthouse')) {
            $query->whereHas('features', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%Penthouse%');
                })
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Architectural Style');
                    });
            });
        }

        // Swimming pool - under Association Amenities
        if ($request->has('swimming_pool') && $request->boolean('swimming_pool')) {
            $query->whereHas('features', function ($q) {
                $q->where('name', 'LIKE', '%Pool%')
                    ->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'Association Amenities');
                    });
            });
        }

        // Gated community - under Community Features
    }

    private function formatPropertyResponse($property)
    {
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found'
            ], 404);
        }

        // Load all relationships for a complete property response
        $property->load([
            'details',
            'media',
            'features',
            'booleanFeatures',
            'taxInformation',
            'financialDetails',
            'leaseInformation',
            'listAgent',
            'coListAgent',
            'buyerAgent',
            'coBuyerAgent',
            'listOffice',
            'coListOffice',
            'buyerOffice',
            'coBuyerOffice',
            'schools',
            'elementarySchool',
            'middleSchool',
            'highSchool'
        ]);

        // Create the base property data from all fillable fields
        $propertyData = [
            'id' => $property->id,
            'listing_key' => $property->listing_key,
            'listing_id' => $property->listing_id,
            'mls_status' => $property->mls_status,
            'standard_status' => $property->standard_status,
            'property_type' => $property->property_type,
            'property_sub_type' => $property->property_sub_type,

            // Address information
            'street_number' => $property->street_number,
            'street_number_numeric' => $property->street_number_numeric,
            'street_dir_prefix' => $property->street_dir_prefix,
            'street_name' => $property->street_name,
            'street_suffix' => $property->street_suffix,
            'street_dir_suffix' => $property->street_dir_suffix,
            'unit_number' => $property->unit_number,
            'city' => $property->city,
            'state_or_province' => $property->state_or_province,
            'postal_code' => $property->postal_code,
            'postal_code_plus4' => $property->postal_code_plus4,
            'county_or_parish' => $property->county_or_parish,
            'country' => $property->country,
            'country_region' => $property->country_region,
            'unparsed_address' => $property->unparsed_address,

            // Listing details
            'list_price' => $property->list_price,
            'original_list_price' => $property->original_list_price,
            'close_price' => $property->close_price,
            'days_on_market' => $property->days_on_market,
            'listing_contract_date' => $property->listing_contract_date,
            'on_market_date' => $property->on_market_date,
            'off_market_date' => $property->off_market_date,
            'pending_timestamp' => $property->pending_timestamp,
            'close_date' => $property->close_date,
            'contract_status_change_date' => $property->contract_status_change_date,
            'listing_agreement' => $property->listing_agreement,
            'contingency' => $property->contingency,

            // Property specifications
            'bedrooms_total' => $property->bedrooms_total,
            'bathrooms_total_decimal' => $property->bathrooms_total_decimal,
            'bathrooms_full' => $property->bathrooms_full,
            'bathrooms_half' => $property->bathrooms_half,
            'bathrooms_total_integer' => $property->bathrooms_total_integer,
            'living_area' => $property->living_area,
            'living_area_units' => $property->living_area_units,
            'lot_size_square_feet' => $property->lot_size_square_feet,
            'lot_size_acres' => $property->lot_size_acres,
            'lot_size_units' => $property->lot_size_units,
            'lot_size_dimensions' => $property->lot_size_dimensions,
            'year_built' => $property->year_built,
            'year_built_details' => $property->year_built_details,
            'stories_total' => $property->stories_total,

            // Parking information
            'garage_yn' => $property->garage_yn,
            'attached_garage_yn' => $property->attached_garage_yn,
            'garage_spaces' => $property->garage_spaces,
            'carport_spaces' => $property->carport_spaces,
            'carport_yn' => $property->carport_yn,
            'open_parking_yn' => $property->open_parking_yn,
            'covered_spaces' => $property->covered_spaces,
            'parking_total' => $property->parking_total,

            // Pool/Spa information
            'pool_private_yn' => $property->pool_private_yn,
            'spa_yn' => $property->spa_yn,

            // Financial information
            'tax_annual_amount' => $property->tax_annual_amount,
            'tax_year' => $property->tax_year,
            'tax_lot' => $property->tax_lot,
            'parcel_number' => $property->parcel_number,
            'association_fee' => $property->association_fee,
            'association_fee_frequency' => $property->association_fee_frequency,

            // Geographic coordinates
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,

            // Virtual tour
            'virtual_tour_url_unbranded' => $property->virtual_tour_url_unbranded,

            // Public remarks
            'public_remarks' => $property->public_remarks,
            'private_remarks' => $property->private_remarks,
            'syndication_remarks' => $property->syndication_remarks,

            // Timestamps from API
            'original_entry_timestamp' => $property->original_entry_timestamp,
            'modification_timestamp' => $property->modification_timestamp,
            'price_change_timestamp' => $property->price_change_timestamp,
            'status_change_timestamp' => $property->status_change_timestamp,
            'major_change_timestamp' => $property->major_change_timestamp,
            'photos_change_timestamp' => $property->photos_change_timestamp,
            'bridge_modification_timestamp' => $property->bridge_modification_timestamp,

            // Flags
            'new_construction_yn' => $property->new_construction_yn,
            'furnished' => $property->furnished,
            'waterfront_yn' => $property->waterfront_yn,
            'view_yn' => $property->view_yn,
            'horse_yn' => $property->horse_yn,

            // Metadata
            'source_system_key' => $property->source_system_key,
            'originating_system_key' => $property->originating_system_key,
            'originating_system_name' => $property->originating_system_name,
            'originating_system_id' => $property->originating_system_id,
        ];

        // Add relationships data
        $propertyData['media'] = $property->media->map(function ($media) {
            return [
                'id' => $media->id,
                'media_url' => $media->media_url,
                'media_type' => $media->media_type,
                'order' => $media->order,
                'description' => $media->description,
                'modification_timestamp' => $media->modification_timestamp
            ];
        });

        $propertyData['features'] = $property->features->map(function ($feature) {
            return [
                'id' => $feature->id,
                'name' => $feature->name,
                'category' => $feature->category ? $feature->category->name : null
            ];
        });

        $propertyData['boolean_features'] = $property->booleanFeatures->map(function ($feature) {
            return [
                'id' => $feature->id,
                'name' => $feature->name,
                'value' => $feature->value
            ];
        });

        $propertyData['details'] = $property->details ? $property->details->toArray() : null;
        $propertyData['tax_information'] = $property->taxInformation ? $property->taxInformation->toArray() : null;
        $propertyData['financial_details'] = $property->financialDetails ? $property->financialDetails->toArray() : null;
        $propertyData['lease_information'] = $property->leaseInformation ? $property->leaseInformation->toArray() : null;

        // Add agent and office information
        $propertyData['list_agent'] = $property->listAgent ? [
            'id' => $property->listAgent->id,
            'full_name' => $property->listAgent->full_name,
            'phone' => $property->listAgent->phone,
            'email' => $property->listAgent->email,
            'agent_key' => $property->listAgent->agent_key
        ] : null;

        $propertyData['co_list_agent'] = $property->coListAgent ? [
            'id' => $property->coListAgent->id,
            'full_name' => $property->coListAgent->full_name,
            'phone' => $property->coListAgent->phone,
            'email' => $property->coListAgent->email,
            'agent_key' => $property->coListAgent->agent_key
        ] : null;

        $propertyData['buyer_agent'] = $property->buyerAgent ? [
            'id' => $property->buyerAgent->id,
            'full_name' => $property->buyerAgent->full_name,
            'phone' => $property->buyerAgent->phone,
            'email' => $property->buyerAgent->email,
            'agent_key' => $property->buyerAgent->agent_key
        ] : null;

        $propertyData['co_buyer_agent'] = $property->coBuyerAgent ? [
            'id' => $property->coBuyerAgent->id,
            'full_name' => $property->coBuyerAgent->full_name,
            'phone' => $property->coBuyerAgent->phone,
            'email' => $property->coBuyerAgent->email,
            'agent_key' => $property->coBuyerAgent->agent_key
        ] : null;

        $propertyData['list_office'] = $property->listOffice ? [
            'id' => $property->listOffice->id,
            'name' => $property->listOffice->name,
            'phone' => $property->listOffice->phone,
            'office_key' => $property->listOffice->office_key
        ] : null;

        $propertyData['co_list_office'] = $property->coListOffice ? [
            'id' => $property->coListOffice->id,
            'name' => $property->coListOffice->name,
            'phone' => $property->coListOffice->phone,
            'office_key' => $property->coListOffice->office_key
        ] : null;

        $propertyData['buyer_office'] = $property->buyerOffice ? [
            'id' => $property->buyerOffice->id,
            'name' => $property->buyerOffice->name,
            'phone' => $property->buyerOffice->phone,
            'office_key' => $property->buyerOffice->office_key
        ] : null;

        $propertyData['co_buyer_office'] = $property->coBuyerOffice ? [
            'id' => $property->coBuyerOffice->id,
            'name' => $property->coBuyerOffice->name,
            'phone' => $property->coBuyerOffice->phone,
            'office_key' => $property->coBuyerOffice->office_key
        ] : null;

        // Add school information
        $propertyData['schools'] = $property->schools ? $property->schools->toArray() : null;

        $propertyData['elementary_school'] = $property->elementarySchool ? [
            'id' => $property->elementarySchool->id,
            'name' => $property->elementarySchool->name,
            'district' => $property->elementarySchool->district
        ] : null;

        $propertyData['middle_school'] = $property->middleSchool ? [
            'id' => $property->middleSchool->id,
            'name' => $property->middleSchool->name,
            'district' => $property->middleSchool->district
        ] : null;

        $propertyData['high_school'] = $property->highSchool ? [
            'id' => $property->highSchool->id,
            'name' => $property->highSchool->name,
            'district' => $property->highSchool->district
        ] : null;

        return response()->json([
            'success' => true,
            'property' => $propertyData
        ]);
    }

    private function formatBuildingResponse($properties, Request $request, $totalCount)
    {
        // Prepare comprehensive building details from the first property
        $buildingDetails = null;
        $representativeProperty = $properties->first();

        if ($representativeProperty && $representativeProperty->details) {
            // Load additional relationships for the representative property
            $representativeProperty->load(['details', 'features', 'booleanFeatures']);

            $buildingDetails = [
                // Basic building information
                'building_name' => $representativeProperty->details->building_name ?? null,
                'year_built' => $representativeProperty->year_built,
                'address' => trim($request->input('street_number') . ' ' . $request->input('street_name')),
                'city' => $representativeProperty->city,
                'state_or_province' => $representativeProperty->state_or_province,
                'postal_code' => $representativeProperty->postal_code,
                'county_or_parish' => $representativeProperty->county_or_parish,
                'country' => $representativeProperty->country,
                'total_units' => $totalCount,

                // Property classification
                'property_type' => $representativeProperty->property_type,
                'property_sub_type' => $representativeProperty->property_sub_type,

                // Geographic coordinates
                'latitude' => $representativeProperty->latitude,
                'longitude' => $representativeProperty->longitude,

                // Building specifications
                'stories_total' => $representativeProperty->stories_total,
                'new_construction_yn' => $representativeProperty->new_construction_yn,

                // Building amenities (from details)
                'structure_type' => $representativeProperty->details->StructureType ?? null,
                'architectural_style' => $representativeProperty->details->ArchitecturalStyle ?? null,
                'heating' => $representativeProperty->details->Heating ?? null,
                'cooling' => $representativeProperty->details->Cooling ?? null,
                'water_source' => $representativeProperty->details->WaterSource ?? null,

                // Building features
                'features' => $representativeProperty->features->map(function ($feature) {
                    return [
                        'name' => $feature->name,
                        'category' => $feature->category ? $feature->category->name : null
                    ];
                }),

                // Association information
                'association_fee' => $representativeProperty->association_fee,
                'association_fee_frequency' => $representativeProperty->association_fee_frequency,

                // Building flags
                'waterfront_yn' => $representativeProperty->waterfront_yn,
                'view_yn' => $representativeProperty->view_yn,
                'pool_private_yn' => $representativeProperty->pool_private_yn,
                'spa_yn' => $representativeProperty->spa_yn,

                // Additional building details
                'virtual_tour_url_unbranded' => $representativeProperty->virtual_tour_url_unbranded,
                'public_remarks' => $representativeProperty->public_remarks,
            ];

            // Add school information if available
            if ($representativeProperty->elementarySchool || $representativeProperty->middleSchool || $representativeProperty->highSchool) {
                $buildingDetails['schools'] = [
                    'elementary_school' => $representativeProperty->elementarySchool ? [
                        'name' => $representativeProperty->elementarySchool->name,
                        'district' => $representativeProperty->elementarySchool->district
                    ] : null,
                    'middle_school' => $representativeProperty->middleSchool ? [
                        'name' => $representativeProperty->middleSchool->name,
                        'district' => $representativeProperty->middleSchool->district
                    ] : null,
                    'high_school' => $representativeProperty->highSchool ? [
                        'name' => $representativeProperty->highSchool->name,
                        'district' => $representativeProperty->highSchool->district
                    ] : null
                ];
            }
        }

        // Format properties list with comprehensive details
        $formattedProperties = $properties->map(function ($property) {
            // Load media for each property
            $property->load(['media', 'details']);

            return [
                // Basic property information
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'listing_id' => $property->listing_id,
                'unit_number' => $property->unit_number,

                // Listing details
                'price' => $property->list_price,
                'original_list_price' => $property->original_list_price,
                'close_price' => $property->close_price,
                'days_on_market' => $property->days_on_market,
                'status' => $property->standard_status,
                'mls_status' => $property->mls_status,
                'listing_contract_date' => $property->listing_contract_date,
                'on_market_date' => $property->on_market_date,
                'off_market_date' => $property->off_market_date,
                'pending_timestamp' => $property->pending_timestamp,
                'close_date' => $property->close_date,
                'contract_status_change_date' => $property->contract_status_change_date,
                'listing_agreement' => $property->listing_agreement,
                'contingency' => $property->contingency,

                // Property specifications
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'bathrooms_full' => $property->bathrooms_full,
                'bathrooms_half' => $property->bathrooms_half,
                'living_area' => $property->living_area,
                'living_area_units' => $property->living_area_units,
                'year_built' => $property->year_built,
                'year_built_details' => $property->year_built_details,

                // Parking information
                'garage_yn' => $property->garage_yn,
                'garage_spaces' => $property->garage_spaces,
                'carport_spaces' => $property->carport_spaces,
                'parking_total' => $property->parking_total,

                // Unit features
                'furnished' => $property->furnished,
                'view_yn' => $property->view_yn,

                // Financial information
                'association_fee' => $property->association_fee,
                'association_fee_frequency' => $property->association_fee_frequency,
                'tax_annual_amount' => $property->tax_annual_amount,
                'tax_year' => $property->tax_year,

                // Unit details
                'floor_number' => $property->details ? $property->details->floor_number : null,
                'pets_allowed' => $property->details ? $property->details->miamire_pets_allowed_yn : null,
                'interior_features' => $property->details ? $property->details->InteriorFeatures : null,
                'appliances' => $property->details ? $property->details->Appliances : null,
                'flooring' => $property->details ? $property->details->Flooring : null,

                // Media
                'photos' => $property->media->map(function ($media) {
                    return [
                        'url' => $media->media_url,
                        'type' => $media->media_type,
                        'order' => $media->order,
                        'description' => $media->description
                    ];
                }),

                // Timestamps
                'original_entry_timestamp' => $property->original_entry_timestamp,
                'modification_timestamp' => $property->modification_timestamp,
                'price_change_timestamp' => $property->price_change_timestamp,
                'status_change_timestamp' => $property->status_change_timestamp,

                // Description
                'public_remarks' => $property->public_remarks,
                'virtual_tour_url_unbranded' => $property->virtual_tour_url_unbranded,

                // Agent information (if needed)
                'list_agent_id' => $property->list_agent_id,
                'list_office_id' => $property->list_office_id
            ];
        });

        return response()->json([
            'success' => true,
            'building' => $buildingDetails,
            'properties' => $formattedProperties,
            'total_properties' => $totalCount,
            'meta' => [
                'current_page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('limit', 12),
                'total' => $totalCount,
                'last_page' => ceil($totalCount / ($request->input('limit', 12))),
                'from' => (($request->input('page', 1)) - 1) * $request->input('limit', 12) + 1,
                'to' => min($request->input('page', 1) * $request->input('limit', 12), $totalCount),
                'has_more_pages' => ($request->input('page', 1) * $request->input('limit', 12)) < $totalCount,
            ],
            'filters' => [
                'street_number' => $request->input('street_number'),
                'street_name' => $request->input('street_name'),
                'building_name' => $request->input('building_name'),
                'city' => $request->input('city'),
                'type' => $request->input('type', 'all'),
                'property_sub_type' => $request->input('property_sub_type'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'min_beds' => $request->input('min_beds'),
                'max_beds' => $request->input('max_beds'),
                'min_baths' => $request->input('min_baths'),
                'max_baths' => $request->input('max_baths'),
                'min_living_size' => $request->input('min_living_size'),
                'max_living_size' => $request->input('max_living_size'),
                'sort_by' => $request->input('sort_by', 'list_price'),
                'sort_dir' => $request->input('sort_dir', 'asc')
            ]
        ]);
    }

    /**
     * Format response for properties list based on location filters
     */
    private function formatLocationResponse($properties, Request $request, $totalCount)
    {
        // Format properties list
        $formattedProperties = $properties->map(function ($property) {
            // Fetch media for each property
            $mediaUrls = $property->media->map(function ($media) {
                return $media->media_url;
            });
            return [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'listing_id' => $property->listing_id,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'unit_number' => $property->unit_number,
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'lot_size' => $property->lot_size_acres,
                'year_built' => $property->year_built,
                'property_type' => $property->property_type,
                'property_sub_type' => $property->property_sub_type,
                'status' => $property->standard_status,
                'photos' => $mediaUrls,
                // Add other property details as needed
            ];
        });

        // Compose display name for location
        $displayName = $this->formatLocationName($request);

        return response()->json([
            'success' => true,
            'location' => [
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'display_name' => $displayName,
                'total_properties' => $totalCount,
            ],
            'properties' => $formattedProperties,
            'meta' => [
                'current_page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('limit', 12),
                'total' => $totalCount,
                'last_page' => ceil($totalCount / ($request->input('limit', 12))),
                'from' => (($request->input('page', 1)) - 1) * $request->input('limit', 12) + 1,
                'to' => min($request->input('page', 1) * $request->input('limit', 12), $totalCount),
                'has_more_pages' => ($request->input('page', 1) * $request->input('limit', 12)) < $totalCount,
            ],
            // Additional info or filters can be added here
        ]);
    }

    // In app/Http/Controllers/PropertyController.php

    public function getPropertiesInBounds(Request $request)
    {
        // Validate bounds parameters
        $request->validate([
            'north' => 'required|numeric',
            'south' => 'required|numeric',
            'east' => 'required|numeric',
            'west' => 'required|numeric',
            // Optional: other filters like pagination, sorting, etc.
        ]);

        $north = $request->input('north');
        $south = $request->input('south');
        $east = $request->input('east');
        $west = $request->input('west');

        // Build query
        $query = BridgeProperty::with(['media', 'features']);

        // Filter properties within bounds
        $query->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);

        // Optional: apply other filters (price, beds, etc.)
        // $this->applyCommonFilters($query, $request);

        // Fetch properties
        $properties = $query->get();

        // Format response
        $formatted = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'photos' => $property->media->map(function ($media) {
                    return $media->media_url;
                }),
            ];
        });

        // After fetching properties within bounds
        return $this->formatPropertiesInBounds($properties, $request, $formatted->count());
    }

    private function formatPropertiesInBounds($properties, $request, $totalCount)
    {
        // Format properties list
        $formattedProperties = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'address' => trim($property->street_number . ' ' . $property->street_name),
                'unit_number' => $property->unit_number,
                'city' => $property->city,
                'state' => $property->state_or_province,
                'postal_code' => $property->postal_code,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'lot_size' => $property->lot_size_acres,
                'year_built' => $property->year_built,
                'property_type' => $property->property_type,
                'property_sub_type' => $property->property_sub_type,
                'status' => $property->standard_status,
                'photos' => $property->media->map(function ($media) {
                    return $media->media_url;
                }),
            ];
        });

        // Compose display name for location (optional, can be customized)
        $displayName = $this->formatLocationName($request);

        return response()->json([
            'success' => true,
            'location' => [
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'display_name' => $displayName,
                'total_properties' => $totalCount,
            ],
            'properties' => $formattedProperties,
            'meta' => [
                'current_page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('limit', 12),
                'total' => $totalCount,
                'last_page' => ceil($totalCount / ($request->input('limit', 12))),
                'from' => (($request->input('page', 1)) - 1) * $request->input('limit', 12) + 1,
                'to' => min($request->input('page', 1) * $request->input('limit', 12), $totalCount),
                'has_more_pages' => ($request->input('page', 1) * $request->input('limit', 12)) < $totalCount,
            ],
        ]);
    }

    // public function getPropertyByListingId($listingId)
    // {
    //     // Bridge API credentials
    //     $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
    //     $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';

    //     try {
    //         // Make API request to fetch the property by listing ID
    //         $response = Http::get($baseUrl . '/' . $listingId, [
    //             'access_token' => $accessToken,
    //             'fields' => '_all' // Request all available fields
    //         ]);

    //         // Check if the request was successful
    //         if (!$response->successful()) {
    //             Log::error('Bridge API request failed for listing ID: ' . $listingId, [
    //                 'status' => $response->status(),
    //                 'response' => $response->json()
    //             ]);

    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Property not found with the provided listing ID'
    //             ], 404);
    //         }

    //         // Get the property data from the response
    //         $responseData = $response->json();

    //         // Check if the bundle key exists in the response
    //         if (!isset($responseData['bundle'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid API response format'
    //             ], 500);
    //         }

    //         // Extract the property data from the bundle
    //         $propertyData = $responseData['bundle'];

    //         // Helper function to safely get nested values
    //         $getValue = function($data, $key, $default = null) {
    //             return $data[$key] ?? $default;
    //         };

    //         // Helper function to convert JSON string to comma-separated string
    //         $jsonToCommaString = function ($jsonString) {
    //             if (empty($jsonString)) return null;
    //             try {
    //                 $array = json_decode($jsonString, true);
    //                 if (is_array($array) && !empty($array)) {
    //                     return implode(', ', $array);
    //                 }
    //                 return $jsonString; // Return original if not a valid JSON array
    //             } catch (\Exception $e) {
    //                 return $jsonString; // Return original on error
    //             }
    //         };

    //         // Helper function to convert array to comma-separated string
    //         $arrayToCommaString = function ($array) {
    //             if (empty($array)) return null;
    //             if (is_array($array)) {
    //                 return implode(', ', $array);
    //             }
    //             return $array;
    //         };

    //         // Extract photos from Media array
    //         $photos = [];
    //         if (isset($propertyData['Media']) && is_array($propertyData['Media'])) {
    //             foreach ($propertyData['Media'] as $media) {
    //                 if (isset($media['MediaURL']) && ($media['MediaCategory'] ?? '') === 'Photo') {
    //                     $photos[] = $media['MediaURL'];
    //                 }
    //             }
    //         }

    //         // Format the address
    //         $streetNumber = $getValue($propertyData, 'StreetNumber', '');
    //         $streetDirPrefix = $getValue($propertyData, 'StreetDirPrefix', '');
    //         $streetName = $getValue($propertyData, 'StreetName', '');
    //         $address = trim($streetNumber . ' ' . $streetDirPrefix . ' ' . $streetName);

    //         // Format the property data in the requested format
    //         $formattedProperty = [
    //             'id' => $getValue($propertyData, 'ListingId'),
    //             'status' => $getValue($propertyData, 'StandardStatus'),
    //             'MlsStatus' => $getValue($propertyData, 'MlsStatus'),
    //             'DaysOnMarket' => $getValue($propertyData, 'DaysOnMarket'),
    //             'Taxs' => $getValue($propertyData, 'TaxAnnualAmount'),
    //             'HOA' => $getValue($propertyData, 'AssociationFee'),
    //             'PropertyType' => $getValue($propertyData, 'PropertySubType'),
    //             'YearBuilt' => $getValue($propertyData, 'YearBuilt'),
    //             'LotSize' => $getValue($propertyData, 'LivingArea') . ' Sqft',
    //             'County' => $getValue($propertyData, 'CountyOrParish'),
    //             'listing_id' => $getValue($propertyData, 'ListingId'),
    //             'listing_key' => $getValue($propertyData, 'ListingKey'),
    //             'street_number' => $streetNumber,
    //             'street_name' => $streetName,
    //             'unparsed_address' => $getValue($propertyData, 'UnparsedAddress'),
    //             'address' => $address,
    //             'unit_number' => $getValue($propertyData, 'UnitNumber'),
    //             'latitude' => $getValue($propertyData, 'Latitude'),
    //             'longitude' => $getValue($propertyData, 'Longitude'),
    //             'city' => $getValue($propertyData, 'City'),
    //             'state' => $getValue($propertyData, 'StateOrProvince'),
    //             'postal_code' => $getValue($propertyData, 'PostalCode'),
    //             'price' => $getValue($propertyData, 'ListPrice'),
    //             'bedrooms' => $getValue($propertyData, 'BedroomsTotal'),
    //             'bathrooms' => $getValue($propertyData, 'BathroomsFull'),
    //             'photos' => $photos,
    //             'SyndicationRemarks' => $getValue($propertyData, 'SyndicationRemarks'),

    //             'Property_details' => [
    //                 'Subdivision' => $getValue($propertyData, 'SubdivisionName'),
    //                 'Style' => $getValue($propertyData, 'MIAMIRE_Style'),
    //                 'WaterFront' => $getValue($propertyData, 'WaterfrontYN'),
    //                 'View' => $arrayToCommaString($getValue($propertyData, 'View')),
    //                 'Water Description' => $arrayToCommaString($getValue($propertyData, 'WaterfrontFeatures')),
    //                 'Area' => $getValue($propertyData, 'MIAMIRE_Area'),
    //                 'Sqft Total' => $getValue($propertyData, 'BuildingAreaTotal'),
    //                 'Sqft LivArea' => $getValue($propertyData, 'LivingArea'),
    //                 'AdjustedAreaSF' => $getValue($propertyData, 'MIAMIRE_AdjustedAreaSF'),
    //                 'YearBuilt Description' => $getValue($propertyData, 'YearBuiltDetails')
    //             ],

    //             'Building_Information' => [
    //                 'Stories' => $getValue($propertyData, 'StoriesTotal'),
    //                 'Building Size' => '-',
    //                 'YearBuilt' => $getValue($propertyData, 'YearBuilt'),
    //                 'Lot Size' => $getValue($propertyData, 'LotSizeSquareFeet') . ' ' . $getValue($propertyData, 'LotSizeUnits')
    //             ],

    //             'Property_Information' => [
    //                 'Parcel Number' => $getValue($propertyData, 'ParcelNumber'),
    //                 'Parcel Number MLX' => $getValue($propertyData, 'ParcelNumber') ? substr($getValue($propertyData, 'ParcelNumber'), -4) : null,
    //                 'MlsArea' => $getValue($propertyData, 'PublicSurveyTownship'),
    //                 'TownshipRange' => $getValue($propertyData, 'PublicSurveyRange'),
    //                 'Section' => $getValue($propertyData, 'PublicSurveySection'),
    //                 'Subdivision Complex Bldg' => $getValue($propertyData, 'SubdivisionName'),
    //                 'Zoning Information' => $getValue($propertyData, 'Zoning')
    //             ],

    //             'General_Information' => [
    //                 'Num Garage Space' => $getValue($propertyData, 'GarageSpaces'),
    //                 'Num Carport Space' => $getValue($propertyData, 'CarportSpaces'),
    //                 'Parking Description' => $arrayToCommaString($getValue($propertyData, 'ParkingFeatures')),
    //                 'Spa' => $getValue($propertyData, 'SpaYN'),
    //                 'Pool' => $getValue($propertyData, 'MIAMIRE_PoolYN'),
    //                 'Pool Description' => $arrayToCommaString($getValue($propertyData, 'PoolFeatures')),
    //                 'Front Exposure' => $getValue($propertyData, 'DirectionFaces'),
    //                 'Approximate LotSize' => $getValue($propertyData, 'LotSizeSquareFeet'),
    //                 'Property Sqft' => $getValue($propertyData, 'LotSizeSquareFeet'),
    //                 'Lot Description' => $arrayToCommaString($getValue($propertyData, 'LotFeatures')),
    //                 'Pool Dimensions' => $getValue($propertyData, 'MIAMIRE_PoolDimensions'),
    //                 'Design' => $arrayToCommaString($getValue($propertyData, 'ArchitecturalStyle')),
    //                 'Design Description' => $arrayToCommaString($getValue($propertyData, 'ArchitecturalStyle')),
    //                 'Construction' => $arrayToCommaString($getValue($propertyData, 'ConstructionMaterials')),
    //                 'Roof Description' => $arrayToCommaString($getValue($propertyData, 'Roof')),
    //                 'Flooring' => $arrayToCommaString($getValue($propertyData, 'Flooring')),
    //                 'Floor Description' => $arrayToCommaString($getValue($propertyData, 'Flooring'))
    //             ],

    //             'Financial_Information' => [
    //                 'Type of Association' => $arrayToCommaString($getValue($propertyData, 'MIAMIRE_TypeofAssociation')),
    //                 'Assoc fee paid per' => $getValue($propertyData, 'AssociationFeeFrequency'),
    //                 'Tax Year' => $getValue($propertyData, 'TaxYear'),
    //                 'Tax Information' => $getValue($propertyData, 'TaxLegalDescription')
    //             ],

    //             'Agent_Info' => [
    //                 'Full Name' => $getValue($propertyData, 'ListAgentFullName'),
    //                 'Office Name' => $getValue($propertyData, 'ListOfficeName')
    //             ],

    //             'Room_Information' => [
    //                 'Room Description' => null, // Not directly available in API response
    //                 'Bedroom Description' => null, // Not directly available in API response
    //                 'Master Bathroom Description:' => null, // Not directly available in API response
    //                 'Master Bath Features' => null, // Not directly available in API response
    //                 'Dining Description' => null, // Not directly available in API response
    //             ],

    //             'Additional_Property_Information' => [
    //                 'Heating Description' => $arrayToCommaString($getValue($propertyData, 'Heating')),
    //                 'Cooling Description' => $arrayToCommaString($getValue($propertyData, 'Cooling')),
    //                 'Water Description' => $arrayToCommaString($getValue($propertyData, 'WaterfrontFeatures')),
    //                 'Sewer Description' => $arrayToCommaString($getValue($propertyData, 'Sewer')),
    //                 'Pets Allowed' => $getValue($propertyData, 'MIAMIRE_PetsAllowedYN'),
    //                 'Guest House Description' => $arrayToCommaString($getValue($propertyData, 'MIAMIRE_GuestHouseDescription')),
    //                 'Furnished' => $getValue($propertyData, 'Furnished'),
    //                 'Interior Features' => $arrayToCommaString($getValue($propertyData, 'InteriorFeatures')),
    //                 'Equipment Appliances' => $arrayToCommaString($getValue($propertyData, 'Appliances')),
    //                 'Window Treatment' => $arrayToCommaString($getValue($propertyData, 'WindowFeatures')),
    //                 'Exterior Features' => $arrayToCommaString($getValue($propertyData, 'ExteriorFeatures')),
    //                 'Subdivision Information' => $arrayToCommaString($getValue($propertyData, 'MIAMIRE_SubdivisionInformation')),
    //             ],
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'property' => $formattedProperty
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Exception when fetching property from Bridge API', [
    //             'listing_id' => $listingId,
    //             'exception' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while fetching the property: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getPropertyByListingId($listingId)
    {
        // Bridge API credentials
        $baseUrl = 'https://api.bridgedataoutput.com/api/v2/miamire/listings';
        $accessToken = 'f091fc0d25a293957350aa6a022ea4fb';

        try {

              $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($baseUrl . '/' . $listingId, [
                'access_token' => $accessToken
            ]);
            // // Make API request to fetch the property by listing ID or listing key
            // $response = Http::get($baseUrl . '/' . $listingId, [
            //     'access_token' => $accessToken
            // ]);

            // Check if the request was successful
            if (!$response->successful()) {
                Log::error('Bridge API request failed for listing ID: ' . $listingId, [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Property not found with the provided listing ID'
                ], 404);
            }

            // Get the property data from the response
            $responseData = $response->json();

            // Check if the bundle key exists in the response
            if (!isset($responseData['bundle'])) {
                Log::error('Invalid API response format for listing ID: ' . $listingId, [
                    'response' => $responseData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API response format'
                ], 500);
            }

            // Extract the property data from the bundle
            $propertyData = $responseData['bundle'];

            // Extract photos from Media array
            $photos = [];
            if (isset($propertyData['Media']) && is_array($propertyData['Media'])) {
                foreach ($propertyData['Media'] as $media) {
                    if (isset($media['MediaURL']) && ($media['MediaCategory'] ?? '') === 'Photo') {
                        $photos[] = $media['MediaURL'];
                    }
                }
            }

            // Format the address
            $streetNumber = $propertyData['StreetNumber'] ?? '';
            $streetDirPrefix = $propertyData['StreetDirPrefix'] ?? '';
            $streetName = $propertyData['StreetName'] ?? '';
            $address = trim($streetNumber . ' ' . ($streetDirPrefix ? $streetDirPrefix . ' ' : '') . $streetName);

            // Helper function to convert arrays to comma-separated strings
            $arrayToString = function ($array) {
                if (empty($array)) return null;
                if (is_array($array)) {
                    return implode(', ', $array);
                }
                return (string)$array;
            };

            // Format the property data in the requested format
            $formattedProperty = [
                'id' => $propertyData['ListingId'] ?? null,
                'status' => $propertyData['StandardStatus'] ?? null,
                'MlsStatus' => $propertyData['MlsStatus'] ?? null,
                'DaysOnMarket' => $propertyData['DaysOnMarket'] ?? null,
                'Taxs' => $propertyData['TaxAnnualAmount'] ?? null,
                'HOA' => $propertyData['AssociationFee'] ?? null,
                'PropertyType' => $propertyData['PropertySubType'] ?? null,
                'YearBuilt' => $propertyData['YearBuilt'] ?? null,
                'LotSize' => ($propertyData['LivingArea'] ?? '') . ' Sqft',
                'County' => $propertyData['CountyOrParish'] ?? null,
                'listing_id' => $propertyData['ListingId'] ?? null,
                'listing_key' => $propertyData['ListingKey'] ?? null,
                'street_number' => $streetNumber,
                'street_name' => $streetName,
                'unparsed_address' => $propertyData['UnparsedAddress'] ?? null,
                'address' => $address,
                'unit_number' => $propertyData['UnitNumber'] ?? null,
                'latitude' => $propertyData['Latitude'] ?? null,
                'longitude' => $propertyData['Longitude'] ?? null,
                'city' => $propertyData['City'] ?? null,
                'state' => $propertyData['StateOrProvince'] ?? null,
                'postal_code' => $propertyData['PostalCode'] ?? null,
                'price' => $propertyData['ListPrice'] ?? null,
                'bedrooms' => $propertyData['BedroomsTotal'] ?? null,
                'bathrooms' => $propertyData['BathroomsFull'] ?? null,
                'photos' => $photos,
                'SyndicationRemarks' => $propertyData['SyndicationRemarks'] ?? null,

                'Property_details' => [
                    'Subdivision' => $propertyData['SubdivisionName'] ?? null,
                    'Style' => $propertyData['MIAMIRE_Style'] ?? null,
                    'WaterFront' => $propertyData['WaterfrontYN'] ?? null,
                    'View' => $arrayToString($propertyData['View'] ?? null),
                    'Water Description' => $arrayToString($propertyData['WaterfrontFeatures'] ?? null),
                    'Area' => $propertyData['MIAMIRE_Area'] ?? null,
                    'Sqft Total' => $propertyData['BuildingAreaTotal'] ?? null,
                    'Sqft LivArea' => $propertyData['LivingArea'] ?? null,
                    'AdjustedAreaSF' => $propertyData['MIAMIRE_AdjustedAreaSF'] ?? null,
                    'YearBuilt Description' => $propertyData['YearBuiltDetails'] ?? null
                ],

                'Building_Information' => [
                    'Stories' => $propertyData['StoriesTotal'] ?? null,
                    'Building Size' => $propertyData['BuildingAreaTotal'] ?? '-',
                    'YearBuilt' => $propertyData['YearBuilt'] ?? null,
                    'Lot Size' => ($propertyData['LotSizeSquareFeet'] ?? '') . ' ' . ($propertyData['LotSizeUnits'] ?? '')
                ],

                'Property_Information' => [
                    'Parcel Number' => $propertyData['ParcelNumber'] ?? null,
                    'Parcel Number MLX' => isset($propertyData['ParcelNumber']) ? substr($propertyData['ParcelNumber'], -4) : null,
                    'MlsArea' => $propertyData['MLSAreaMajor'] ?? null,
                    'TownshipRange' => $propertyData['PublicSurveyRange'] ?? null,
                    'Section' => $propertyData['PublicSurveySection'] ?? null,
                    'Subdivision Complex Bldg' => $propertyData['SubdivisionName'] ?? null,
                    'Zoning Information' => $propertyData['Zoning'] ?? null
                ],

                'General_Information' => [
                    'Num Garage Space' => $propertyData['GarageSpaces'] ?? null,
                    'Num Carport Space' => $propertyData['CarportSpaces'] ?? null,
                    'Parking Description' => $arrayToString($propertyData['ParkingFeatures'] ?? null),
                    'Spa' => $propertyData['SpaYN'] ?? null,
                    'Pool' => $propertyData['MIAMIRE_PoolYN'] ?? null,
                    'Pool Description' => $arrayToString($propertyData['PoolFeatures'] ?? null),
                    'Front Exposure' => $propertyData['DirectionFaces'] ?? null,
                    'Approximate LotSize' => $propertyData['LotSizeSquareFeet'] ?? null,
                    'Property Sqft' => $propertyData['LotSizeSquareFeet'] ?? null,
                    'Lot Description' => $arrayToString($propertyData['LotFeatures'] ?? null),
                    'Pool Dimensions' => $propertyData['MIAMIRE_PoolDimensions'] ?? null,
                    'Design' => $arrayToString($propertyData['ArchitecturalStyle'] ?? null),
                    'Design Description' => $arrayToString($propertyData['ArchitecturalStyle'] ?? null),
                    'Construction' => $arrayToString($propertyData['ConstructionMaterials'] ?? null),
                    'Roof Description' => $arrayToString($propertyData['Roof'] ?? null),
                    'Flooring' => $arrayToString($propertyData['Flooring'] ?? null),
                    'Floor Description' => $arrayToString($propertyData['Flooring'] ?? null)
                ],

                'Financial_Information' => [
                    'Type of Association' => $arrayToString($propertyData['MIAMIRE_TypeofAssociation'] ?? null),
                    'Assoc fee paid per' => $propertyData['AssociationFeeFrequency'] ?? null,
                    'Tax Year' => $propertyData['TaxYear'] ?? null,
                    'Tax Information' => $propertyData['TaxLegalDescription'] ?? null
                ],

                'Agent_Info' => [
                    'Full Name' => $propertyData['ListAgentFullName'] ?? null,
                    'Office Name' => $propertyData['ListOfficeName'] ?? null
                ],

                'Room_Information' => [
                    'Room Description' => null,
                    'Bedroom Description' => $arrayToString($propertyData['RoomBedroomFeatures'] ?? null),
                    'Master Bathroom Description:' => $arrayToString($propertyData['RoomMasterBathroomFeatures'] ?? null),
                    'Master Bath Features' => $arrayToString($propertyData['RoomMasterBathroomFeatures'] ?? null),
                    'Dining Description' => $arrayToString($propertyData['RoomDiningRoomFeatures'] ?? null)
                ],

                'Additional_Property_Information' => [
                    'Heating Description' => $arrayToString($propertyData['Heating'] ?? null),
                    'Cooling Description' => $arrayToString($propertyData['Cooling'] ?? null),
                    'Water Description' => $arrayToString($propertyData['WaterSource'] ?? null),
                    'Sewer Description' => $arrayToString($propertyData['Sewer'] ?? null),
                    'Pets Allowed' => $propertyData['MIAMIRE_PetsAllowedYN'] ?? null,
                    'Guest House Description' => $arrayToString($propertyData['MIAMIRE_GuestHouseDescription'] ?? null),
                    'Furnished' => $propertyData['Furnished'] ?? null,
                    'Interior Features' => $arrayToString($propertyData['InteriorFeatures'] ?? null),
                    'Equipment Appliances' => $arrayToString($propertyData['Appliances'] ?? null),
                    'Window Treatment' => $arrayToString($propertyData['WindowFeatures'] ?? null),
                    'Exterior Features' => $arrayToString($propertyData['ExteriorFeatures'] ?? null),
                    'Subdivision Information' => $arrayToString($propertyData['MIAMIRE_SubdivisionInformation'] ?? null)
                ]
            ];

            // Log the successful mapping for debugging
            Log::info('Successfully mapped property data for listing ID: ' . $listingId);

            return response()->json([
                'success' => true,
                'property' => $formattedProperty
            ]);
        } catch (\Exception $e) {
            Log::error('Exception when fetching property from Bridge API', [
                'listing_id' => $listingId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the property: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get nearby properties using Bridge API
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNearbyProperties(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|min:0.1|max:50',
            'limit' => 'nullable|integer|min:1|max:50',
            'property_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 5); // Default 5 miles
        $limit = $request->input('limit', 15); // Default 10 properties

        // Get access token from config
        $accessToken = config('services.trestle.access_token');
        $datasetId = config('services.trestle.dataset_id');

        // If we don't have a stored token, try to get one

        // Build the API URL
        $apiUrl = "https://api.bridgedataoutput.com/api/v2/miamire/listings";

        // Build query parameters
        $queryParams = [
            'access_token' => 'f091fc0d25a293957350aa6a022ea4fb',
            'limit' => 15,
            'near' => "{$longitude},{$latitude}",
            'radius' => "{$radius}mi",
        ];

        try {
            $response = Http::withOptions([
            'verify' => false, // Disable SSL verification
        ])->get($apiUrl, $queryParams);
            // Make the API request
            // $response = Http::get($apiUrl, $queryParams);

            // Check if the request was successful
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching nearby properties',
                    'error' => $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Format the response
            $properties = collect($data['bundle'] ?? []);

            $formattedProperties = $properties->map(function ($property) {
                return [
                    'listing_id' => $property['ListingId'] ?? null,
                    'listing_key' => $property['ListingKey'] ?? null,
                    'building_name' => $property['BuildingName'] ?? null,
                    'address' => trim(($property['StreetNumber'] ?? '') . ' ' . ($property['StreetName'] ?? '')),
                    'unit_number' => $property['UnitNumber'] ?? null,
                    'city' => $property['City'] ?? null,
                    'state' => $property['StateOrProvince'] ?? null,
                    'postal_code' => $property['PostalCode'] ?? null,
                    'price' => $property['ListPrice'] ?? null,
                    'bedrooms' => $property['BedroomsTotal'] ?? null,
                    'bathrooms' => $property['BathroomsTotalDecimal'] ?? null,
                    'living_area' => $property['LivingArea'] ?? null,
                    'property_type' => $property['PropertyType'] ?? null,
                    'property_sub_type' => $property['PropertySubType'] ?? null,
                    'year_built' => $property['YearBuilt'] ?? null,
                    'photos' => collect($property['Media'] ?? [])->pluck('MediaURL'),
                    'distance' => $property['distance'] ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'properties' => $formattedProperties,
                'total' => $data['total'] ?? count($formattedProperties),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching nearby properties',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
