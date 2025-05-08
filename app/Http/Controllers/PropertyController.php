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
            $featuresGrouped = $property->features->groupBy(function($feature) {
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
}
