<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyApiController extends Controller
{
    public function getNewDevelopments(Request $request)
    {
        // Start with the base query
        $query = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ]);
        
        // Filter properties by development status using the relationship
        // $developmentStatuses = [
        //     'Completed', 'FinishedLots', 'Proposed', 'RawLand', 
        //     'RoughGrade', 'SitePlanApproved', 'SitePlanFiled', 'UnderConstruction'
        // ];
        
        // $query->whereHas('details', function($q) use ($developmentStatuses) {
        //     $q->whereIn('DevelopmentStatus', $developmentStatuses);
        // });

        // Apply YearBuilt filter (null or greater than 2024)
        $query->where('YearBuilt', '>', 2024);

        // Apply StandardStatus filter
        $query->where('StandardStatus', 'Active');

        // Apply ordering if requested
        if ($request->has('orderby')) {
            $orderBy = $request->input('orderby');
            $direction = 'asc';
            
            if (strpos($orderBy, ' desc') !== false) {
                $orderBy = str_replace(' desc', '', $orderBy);
                $direction = 'desc';
            }
            
            // Check if the order column is in the details table
            if (in_array($orderBy, ['DevelopmentStatus'])) {
                // For columns in related tables, we need to use a different approach
                // We'll order by the relationship's column
                $query->join('property_details', 'properties.id', '=', 'property_details.property_id')
                      ->orderBy('property_details.' . $orderBy, $direction)
                      ->select('properties.*'); // Make sure we only select from the properties table
            } else {
                // For columns in the main properties table
                $query->orderBy($orderBy, $direction);
            }
        } else {
            // Default ordering
            $query->orderBy('YearBuilt', 'desc');
        }

        // Get the total count before pagination
        $totalCount = $query->count();
        
        // Handle pagination parameters
        $limit = $request->input('limit', 10); // Default to 10 items per page
        $page = $request->input('page', 1);    // Default to first page
        $offset = ($page - 1) * $limit;        // Calculate the offset
        
        // Apply limit and offset
        $properties = $query->skip($offset)->take($limit)->get();
        
        // Format the response
        return response()->json([
            'properties' => $properties,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }
    
    // Add a separate endpoint for the homepage that shows just 8 properties
    public function getHomePageDevelopments(Request $request)
    {
        // Reuse the same query logic but limit to 8 items
        $request->merge(['limit' => 8, 'page' => 1]);
        return $this->getNewDevelopments($request);
    }

}
