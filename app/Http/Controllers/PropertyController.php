<?php

namespace App\Http\Controllers;

use App\Models\BridgeProperty;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
            'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE',
            'Florida' => 'FL', 'Georgia' => 'GA', 'Hawaii' => 'HI', 'Idaho' => 'ID',
            'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA', 'Kansas' => 'KS',
            'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME', 'Maryland' => 'MD',
            'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS',
            'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV',
            'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM', 'New York' => 'NY',
            'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH', 'Oklahoma' => 'OK',
            'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI', 'South Carolina' => 'SC',
            'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT',
            'Vermont' => 'VT', 'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV',
            'Wisconsin' => 'WI', 'Wyoming' => 'WY',
        ];

        $state = trim($state);
        return $states[$state] ?? $state;
    }

    // public function index(Request $request)
    // {
    //     $query = Property::query();

    //     // Search functionality
    //     if ($request->has('search') && !empty($request->search)) {
    //         $searchTerm = trim($request->search);

    //         $parts = array_map('trim', explode(',', $searchTerm));
    //         $partsCount = count($parts);

    //         $street = null;
    //         $city = null;
    //         $state = null;
    //         $postalCode = null;
    //         $country = null;

    //         // Handle different address input patterns
    //         if ($partsCount >= 1) {
    //             $streetOrCity = $parts[0];

    //             // If it's a full address, like "400 Sunny Isles Blvd 119"
    //             if (preg_match('/\d+/', $streetOrCity)) {
    //                 $street = $streetOrCity;
    //             } else {
    //                 $city = $streetOrCity;
    //             }
    //         }

    //         if ($partsCount >= 2) {
    //             $city = $parts[1];
    //         }

    //         if ($partsCount >= 3) {
    //             $state = $parts[2];
    //         }

    //         if ($partsCount >= 4) {
    //             // Either a postal code or a country
    //             if (preg_match('/\d{4,}/', $parts[3])) {
    //                 $postalCode = $parts[3];
    //             } else {
    //                 $country = $parts[3];
    //             }
    //         }

    //         if ($partsCount >= 5) {
    //             $country = $parts[4];
    //         }

    //         // Convert full state to abbreviation if needed
    //         $stateAbbr = $state ? $this->getStateAbbreviation($state) : null;

    //         $query->where(function ($q) use ($street, $city, $stateAbbr, $postalCode, $country) {
    //             if ($street) {
    //                 $q->where(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$street}%")
    //                     ->orWhere('UnparsedAddress', 'like', "%{$street}%");
    //             }

    //             if ($city) {
    //                 $q->where('City', 'like', "%{$city}%");
    //             }

    //             if ($stateAbbr) {
    //                 $q->where('StateOrProvince', 'like', "{$stateAbbr}%");
    //             }

    //             if ($postalCode) {
    //                 $q->where('PostalCode', 'like', "%{$postalCode}%");
    //             }

    //             if ($country) {
    //                 $q->where('country', 'like', "%{$country}%");
    //             }
    //         });
    //     }

    //     // Individual filters (optional)
    //     if ($request->filled('street_number')) {
    //         $query->where('StreetNumber', 'like', "%{$request->street_number}%");
    //     }

    //     if ($request->filled('street_name')) {
    //         $query->where('StreetName', 'like', "%{$request->street_name}%");
    //     }

    //     if ($request->filled('postal_code')) {
    //         $query->where('PostalCode', 'like', "%{$request->postal_code}%");
    //     }

    //     if ($request->filled('city')) {
    //         $query->where('City', 'like', "%{$request->city}%");
    //     }

    //     if ($request->filled('country')) {
    //         $query->where('country', 'like', "%{$request->country}%");
    //     }

    //     $sortBy = $request->get('sort_by', 'PropertyType'); // Default sort by 'PropertyType'
    //     $sortDirection = $request->get('sort_direction', 'asc'); // Default sort direction is 'asc'

    //     $query->orderBy($sortBy, $sortDirection);

    //     $properties = $query->paginate(10);

    //     if ($request->ajax()) {
    //         return response()->json([
    //             'html' => view('partials.properties-table', compact('properties'))->render(),
    //             'pagination' => view('partials.pagination', compact('properties'))->render(),
    //         ]);
    //     }

    //     return view('properties', compact('properties'));
    // }

    // public function index(Request $request)
    // {
    //     $query = BridgeProperty::query();

    //     // Search functionality
    //     if ($request->has('search') && !empty($request->search)) {
    //         $searchTerm = trim($request->search);

    //         $parts = array_map('trim', explode(',', $searchTerm));
    //         $partsCount = count($parts);

    //         $street = null;
    //         $city = null;
    //         $state = null;
    //         $postalCode = null;
    //         $country = null;

    //         // Handle different address input patterns
    //         if ($partsCount >= 1) {
    //             $streetOrCity = $parts[0];

    //             // If it's a full address, like "400 Sunny Isles Blvd 119"
    //             if (preg_match('/\d+/', $streetOrCity)) {
    //                 $street = $streetOrCity;
    //             } else {
    //                 $city = $streetOrCity;
    //             }
    //         }

    //         if ($partsCount >= 2) {
    //             $city = $parts[1];
    //         }

    //         if ($partsCount >= 3) {
    //             $state = $parts[2];
    //         }

    //         if ($partsCount >= 4) {
    //             // Either a postal code or a country
    //             if (preg_match('/\d{4,}/', $parts[3])) {
    //                 $postalCode = $parts[3];
    //             } else {
    //                 $country = $parts[3];
    //             }
    //         }

    //         if ($partsCount >= 5) {
    //             $country = $parts[4];
    //         }

    //         // Convert full state to abbreviation if needed
    //         $stateAbbr = $state ? $this->getStateAbbreviation($state) : null;

    //         $query->where(function ($q) use ($street, $city, $stateAbbr, $postalCode, $country) {
    //             if ($street) {
    //                 $q->where(DB::raw("CONCAT(street_number, ' ', street_name)"), 'like', "%{$street}%")
    //                     ->orWhere('unparsed_address', 'like', "%{$street}%");
    //             }

    //             if ($city) {
    //                 $q->where('city', 'like', "%{$city}%");
    //             }

    //             if ($stateAbbr) {
    //                 $q->where('state_or_province', 'like', "{$stateAbbr}%");
    //             }

    //             if ($postalCode) {
    //                 $q->where('postal_code', 'like', "%{$postalCode}%");
    //             }

    //             if ($country) {
    //                 $q->where('country', 'like', "%{$country}%");
    //             }
    //         });
    //     }

    //     // Individual filters (optional)
    //     if ($request->filled('street_number')) {
    //         $query->where('street_number', 'like', "%{$request->street_number}%");
    //     }

    //     if ($request->filled('street_name')) {
    //         $query->where('street_name', 'like', "%{$request->street_name}%");
    //     }

    //     if ($request->filled('postal_code')) {
    //         $query->where('postal_code', 'like', "%{$request->postal_code}%");
    //     }

    //     if ($request->filled('city')) {
    //         $query->where('city', 'like', "%{$request->city}%");
    //     }

    //     if ($request->filled('country')) {
    //         $query->where('country', 'like', "%{$request->country}%");
    //     }

    //     $sortBy = $request->get('sort_by', 'property_type'); // Default sort by 'property_type'
    //     $sortDirection = $request->get('sort_direction', 'asc'); // Default sort direction is 'asc'

    //     $query->orderBy($sortBy, $sortDirection);

    //     $properties = $query->paginate(10);

    //     if ($request->ajax()) {
    //         return response()->json([
    //             'html' => view('partials.properties-table', compact('properties'))->render(),
    //             'pagination' => view('partials.pagination', compact('properties'))->render(),
    //         ]);
    //     }

    //     return view('properties', compact('properties'));
    // }

    // public function index(Request $request)
    // {
    //     $search = $request->input('search');
    //     $sortBy = $request->input('sort_by', 'id');
    //     $sortDirection = $request->input('sort_direction', 'desc');

    //     $query = BridgeProperty::query();

    //     // Apply search filter if provided
    //     if ($search) {
    //         // Check if this is a property_id search
    //         if (preg_match('/property_id:(\d+)/', $search, $matches)) {
    //             // If we have a property ID, just search for that specific property
    //             $propertyId = $matches[1];
    //             $query->where('id', $propertyId);
    //         }
    //         // Check if this is a street search
    //         else if (preg_match('/street:(.+)/', $search, $matches)) {
    //             $streetInfo = trim($matches[1]);

    //             // Try to extract street number and name
    //             if (preg_match('/^(\d+)\s+(.+)$/', $streetInfo, $streetMatches)) {
    //                 $streetNumber = $streetMatches[1];
    //                 $streetName = trim($streetMatches[2]);

    //                 // Search for properties with this street number and name
    //                 $query->where('street_number', $streetNumber)
    //                       ->where('street_name', 'like', $streetName . '%');
    //             } else {
    //                 // If we couldn't parse it, just search the street name
    //                 $query->where('street_name', 'like', '%' . $streetInfo . '%');
    //             }
    //         }
    //         // Check if this is a city search
    //         else if (preg_match('/^([^,]+),\s*([A-Z]{2})$/', $search, $matches)) {
    //             $city = trim($matches[1]);
    //             $state = $matches[2];
    //             $query->where('city', $city)
    //                   ->where('state_or_province', $state);
    //         }
    //         // Check if this is a state search
    //         else if (preg_match('/^([A-Z]{2})$/', $search, $matches)) {
    //             $state = $matches[1];
    //             $query->where('state_or_province', $state);
    //         }
    //         // Check if this is a postal code search
    //         else if (preg_match('/^(\d{5}),\s*([A-Z]{2})$/', $search, $matches)) {
    //             $postalCode = $matches[1];
    //             $state = $matches[2];
    //             $query->where('postal_code', $postalCode)
    //                   ->where('state_or_province', $state);
    //         }
    //         // Check if this might be a street number and name without our prefix
    //         else if (preg_match('/^(\d+)\s+(.+)$/', $search, $streetMatches)) {
    //             $streetNumber = $streetMatches[1];
    //             $streetName = trim($streetMatches[2]);

    //             // If it looks like a street address (number followed by text)
    //             $query->where(function($q) use ($streetNumber, $streetName, $search) {
    //                 // Try exact match on street number and name
    //                 $q->where(function($sq) use ($streetNumber, $streetName) {
    //                     $sq->where('street_number', $streetNumber)
    //                        ->where('street_name', 'like', $streetName . '%');
    //                 })
    //                 // Or try matching the full unparsed address
    //                 ->orWhere('unparsed_address', 'like', '%' . $search . '%');
    //             });
    //         }
    //         // General search
    //         else {
    //             $query->where(function($q) use ($search) {
    //                 $q->where('unparsed_address', 'like', "%{$search}%")
    //                   ->orWhere('street_number', 'like', "%{$search}%")
    //                   ->orWhere('street_name', 'like', "%{$search}%")
    //                   ->orWhere('city', 'like', "%{$search}%")
    //                   ->orWhere('state_or_province', 'like', "%{$search}%")
    //                   ->orWhere('postal_code', 'like', "%{$search}%");
    //             });
    //         }
    //     }

    //     // Apply sorting
    //     $query->orderBy($sortBy, $sortDirection);

    //     // Get paginated results
    //     $properties = $query->paginate(15)->withQueryString();

    //     return view('properties', compact('properties'));
    // }     

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

    /**
     * Unified property data retrieval function that handles:
     * 1. Single property details
     * 2. Building properties
     * 3. Location-based properties
     */
    public function getProperties(Request $request)
    {
        // Validate common request parameters
        $validationRules = [
            'property_id' => 'nullable|integer',
            'street_number' => 'nullable|string',
            'street_name' => 'nullable|string',
            'building_name' => 'nullable|string',
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
            $validationRules['property_id'] = 'required|integer';
        } elseif ($mode === 'building') {
            $validationRules['street_number'] = 'required|string';
            $validationRules['street_name'] = 'required|string';
            $validationRules['building_name'] = 'required|string';
            $validationRules['city'] = 'required|string';
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

        // Start with the base query
        $query = BridgeProperty::with(['details', 'media', 'features']);

        // Apply mode-specific filters
        if ($mode === 'property') {
            $query->where('id', $request->property_id);
        } elseif ($mode === 'building') {
            $query->where('street_number', $request->street_number)
                ->where('street_name', $request->street_name)
                ->where('city', $request->city)
                ->whereHas('details', function ($q) use ($request) {
                    $q->where('building_name', $request->building_name);
                });
        } elseif ($mode === 'location') {
            if ($request->filled('city')) {
                $query->where('city', $request->city);
            }
            if ($request->filled('state')) {
                $query->where('state_or_province', $request->state);
            }
            if ($request->filled('postal_code')) {
                $query->where('postal_code', $request->postal_code);
            }
        }

        // Apply common filters
        $this->applyCommonFilters($query, $request);

        // Get total count before pagination (for building and location modes)
        $totalCount = ($mode !== 'property') ? $query->count() : 1;

        // Apply sorting
        $sortBy = $request->input('sort_by', 'list_price');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination for building and location modes
        if ($mode !== 'property') {
            $limit = $request->input('limit', 12);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $limit;
            $properties = $query->skip($offset)->take($limit)->get();
        } else {
            // For single property mode, just get the first result
            $properties = $query->get();
        }

        // Format the response based on the mode
        if ($mode === 'property') {
            return $this->formatPropertyResponse($properties->first());
        } elseif ($mode === 'building') {
            return $this->formatBuildingResponse($properties, $request, $totalCount);
        } else {
            return $this->formatLocationResponse($properties, $request, $totalCount);
        }
    }

    /**
     * Determine which mode to use based on request parameters
     */
    protected function determineMode(Request $request)
    {
        if ($request->filled('property_id')) {
            return 'property';
        }

        if (
            $request->filled('street_number') &&
            $request->filled('street_name') &&
            $request->filled('building_name') &&
            $request->filled('city')
        ) {
            return 'building';
        }

        if (
            $request->filled('city') ||
            $request->filled('state') ||
            $request->filled('postal_code')
        ) {
            return 'location';
        }

        return null; // fallback to "all" mode
    }


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
    }

    /**
     * Format response for a single property
     */
    /**
     * Format response for a single property
     */
    private function formatPropertyResponse($property)
    {
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found'
            ], 404);
        }

        // Load additional relationships for a single property
        $property->load(['listAgent', 'listOffice', 'media', 'features']);

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
                // 'features' => $property->features->pluck('name'),
                // 'agent' => $property->listAgent ? [
                //     'name' => $property->listAgent->full_name,
                //     'phone' => $property->listAgent->phone,
                //     'email' => $property->listAgent->email
                // ] : null,
                // 'office' => $property->listOffice ? [
                //     'name' => $property->listOffice->name,
                //     'phone' => $property->listOffice->phone
                // ] : null,
                // Additional fields can be added here as needed
            ]
        ]);
    }


    /**
     * Format response for building properties list
     */
    private function formatBuildingResponse($properties, Request $request, $totalCount)
    {
        // Prepare building details from the first property
        $buildingDetails = null;
        $representativeProperty = $properties->first();
        if ($representativeProperty && $representativeProperty->details) {
            $buildingDetails = [
                'building_name' => $representativeProperty->details->building_name ?? null,
                'year_built' => $representativeProperty->year_built,
                'address' => trim($request->input('street_number') . ' ' . $request->input('street_name')),
                'city' => $representativeProperty->city,
                'state' => $representativeProperty->state_or_province,
                'postal_code' => $representativeProperty->postal_code,
                'total_units' => $totalCount,
                'property_sub_type' => $representativeProperty->property_sub_type,
                // Add other building details as needed
            ];
        }

        // Format properties list
        $formattedProperties = $properties->map(function ($property) {
            // Fetch media for each property
            $mediaUrls = $property->media->map(function ($media) {
                return $media->media_url;
            });
            return [
                'id' => $property->id,
                'listing_key' => $property->listing_key,
                'unit_number' => $property->unit_number,
                'price' => $property->list_price,
                'bedrooms' => $property->bedrooms_total,
                'bathrooms' => $property->bathrooms_total_decimal,
                'living_area' => $property->living_area,
                'status' => $property->standard_status,
                'photos' => $mediaUrls,
                // Add other property details as needed
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
            // Additional info or filters can be added here
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
}
