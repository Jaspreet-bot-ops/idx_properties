<?php

namespace App\Http\Controllers;

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

    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     $query = Property::query();

    //     // Search functionality
    //     if ($request->has('search') && !empty($request->search)) {
    //         $searchTerm = $request->search;

    //         // Check if the search term contains commas (like "City, State, Country")
    //         if (strpos($searchTerm, ',') !== false) {
    //             $parts = array_map('trim', explode(',', $searchTerm));

    //             // Extract city, state, and country
    //             $city = isset($parts[0]) ? $parts[0] : null;
    //             $state = isset($parts[1]) ? $parts[1] : null;
    //             $country = isset($parts[2]) ? $parts[2] : null;

    //             // Convert state name to abbreviation if needed
    //             $stateAbbr = $state ? $this->getStateAbbreviation($state) : null;

    //             // Build a more precise query with AND conditions between parts
    //             $query->where(function($q) use ($city, $stateAbbr, $country) {
    //                 // Start with the city - this is required


    //                 if ($city) {
    //                     $q->where('City', 'like', "{$city}%");
    //                 }

    //                 // Add state condition if provided
    //                 if ($stateAbbr) {
    //                     $q->where('StateOrProvince', 'like', "{$stateAbbr}%");
    //                 }

    //                 // Add country condition if provided
    //                 if ($country) {
    //                     $q->where('country', 'like', "%{$country}%");
    //                 }
    //             });
    //         } else {
    //             // For single term searches, try to match exactly on common fields
    //             $query->where(function($q) use ($searchTerm) {
    //                 $q->where('City', $searchTerm)
    //                   ->orWhere('PostalCode', $searchTerm)
    //                   ->orWhere('UnparsedAddress', 'like', "%{$searchTerm}%")
    //                   ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$searchTerm}%");

    //                 // Check if it might be a state
    //                 $stateAbbr = $this->getStateAbbreviation($searchTerm);
    //                 if ($stateAbbr !== $searchTerm) {
    //                     $q->orWhere('StateOrProvince', $stateAbbr);
    //                 } else {
    //                     $q->orWhere('StateOrProvince', $searchTerm);
    //                 }
    //             });
    //         }
    //     }

    //     // Filter by specific address components if needed
    //     if ($request->has('street_number') && !empty($request->street_number)) {
    //         $query->where('StreetNumber', 'like', "%{$request->street_number}%");
    //     }

    //     if ($request->has('street_name') && !empty($request->street_name)) {
    //         $query->where('StreetName', 'like', "%{$request->street_name}%");
    //     }

    //     if ($request->has('postal_code') && !empty($request->postal_code)) {
    //         $query->where('PostalCode', 'like', "%{$request->postal_code}%");
    //     }

    //     if ($request->has('city') && !empty($request->city)) {
    //         $query->where('City', 'like', "%{$request->city}%");
    //     }

    //     if ($request->has('country') && !empty($request->country)) {
    //         $query->where('country', 'like', "%{$request->country}%");
    //     }

    //     $properties = $query->paginate(10);

    //     // If this is an AJAX request, return JSON
    //     if ($request->ajax()) {
    //         return response()->json([
    //             'html' => view('partials.properties-table', compact('properties'))->render(),
    //             'pagination' => view('partials.pagination', compact('properties'))->render(),
    //         ]);
    //     }

    //     return view('properties', compact('properties'));
    // }

    public function index(Request $request)
    {
        $query = Property::query();

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

        if ($request->filled('city')) {
            $query->where('City', 'like', "%{$request->city}%");
        }

        if ($request->filled('country')) {
            $query->where('country', 'like', "%{$request->country}%");
        }

        $sortBy = $request->get('sort_by', 'PropertyType'); // Default sort by 'PropertyType'
        $sortDirection = $request->get('sort_direction', 'asc'); // Default sort direction is 'asc'
    
        $query->orderBy($sortBy, $sortDirection);
        
        $properties = $query->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.properties-table', compact('properties'))->render(),
                'pagination' => view('partials.pagination', compact('properties'))->render(),
            ]);
        }

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
    public function show(Property $property)
    {
        $property->load([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails',
        ]);

        return view('properties-show', compact('property'));
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
