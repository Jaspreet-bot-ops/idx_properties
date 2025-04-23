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
    private function getStateAbbreviation($stateName)
    {
        $stateLower = strtolower(trim($stateName));
        
        // If it's already an abbreviation (2 characters), return as is
        if (strlen($stateLower) == 2) {
            return strtoupper($stateLower);
        }
        
        // Check if it's in our map
        if (isset($this->stateMap[$stateLower])) {
            return $this->stateMap[$stateLower];
        }
        
        // If not found, return original
        return $stateName;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Property::query();
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            
            // Check if the search term contains commas (like "City, State, Country")
            if (strpos($searchTerm, ',') !== false) {
                $parts = array_map('trim', explode(',', $searchTerm));
                
                // Extract city, state, and country
                $address = isset($parts[0]) ? $parts[0] : null;
                $city = isset($parts[1]) ? $parts[1] : null;
                $state = isset($parts[2]) ? $parts[2] : null;
                $country = isset($parts[3]) ? $parts[3] : null;
                
                // Convert state name to abbreviation if needed
                $stateAbbr = $state ? $this->getStateAbbreviation($state) : null;
                
                // Build a more precise query with AND conditions between parts
                $query->where(function($q) use ($address, $city, $stateAbbr, $country) {
                    // Start with the city - this is required
                    if ($address) {
                        $q->where('UnparsedAddress', 'like', "{$address}%");
                    }

                    if ($city) {
                        $q->where('City', 'like', "{$city}%");
                    }
                    
                    // Add state condition if provided
                    if ($stateAbbr) {
                        $q->where('StateOrProvince', 'like', "{$stateAbbr}%");
                    }
                    
                    // Add country condition if provided
                    if ($country) {
                        $q->where('country', 'like', "%{$country}%");
                    }
                });
            } else {
                // For single term searches, try to match exactly on common fields
                $query->where(function($q) use ($searchTerm) {
                    $q->where('City', $searchTerm)
                      ->orWhere('PostalCode', $searchTerm)
                      ->orWhere('UnparsedAddress', 'like', "%{$searchTerm}%")
                      ->orWhere(DB::raw("CONCAT(StreetNumber, ' ', StreetName)"), 'like', "%{$searchTerm}%");
                      
                    // Check if it might be a state
                    $stateAbbr = $this->getStateAbbreviation($searchTerm);
                    if ($stateAbbr !== $searchTerm) {
                        $q->orWhere('StateOrProvince', $stateAbbr);
                    } else {
                        $q->orWhere('StateOrProvince', $searchTerm);
                    }
                });
            }
        }
        
        // Filter by specific address components if needed
        if ($request->has('street_number') && !empty($request->street_number)) {
            $query->where('StreetNumber', 'like', "%{$request->street_number}%");
        }
        
        if ($request->has('street_name') && !empty($request->street_name)) {
            $query->where('StreetName', 'like', "%{$request->street_name}%");
        }
        
        if ($request->has('postal_code') && !empty($request->postal_code)) {
            $query->where('PostalCode', 'like', "%{$request->postal_code}%");
        }
        
        if ($request->has('city') && !empty($request->city)) {
            $query->where('City', 'like', "%{$request->city}%");
        }
        
        if ($request->has('country') && !empty($request->country)) {
            $query->where('country', 'like', "%{$request->country}%");
        }
        
        $properties = $query->paginate(10);
        
        // If this is an AJAX request, return JSON
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
