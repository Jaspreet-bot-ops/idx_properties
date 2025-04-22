<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Property::query();
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('UnparsedAddress', 'like', "%{$searchTerm}%")
                  ->orWhere('StreetNumber', 'like', "%{$searchTerm}%")
                  ->orWhere('StreetName', 'like', "%{$searchTerm}%")
                  ->orWhere('City', 'like', "%{$searchTerm}%")
                  ->orWhere('StateOrProvince', 'like', "%{$searchTerm}%")
                  ->orWhere('PostalCode', 'like', "%{$searchTerm}%")
                  ->orWhere('country', 'like', "%{$searchTerm}%")
                  ->orWhere('SubdivisionName', 'like', "%{$searchTerm}%");
            });
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
