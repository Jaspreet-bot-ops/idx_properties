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
        // Start with the base query
        $query = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ]);

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
                $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
                    ->orderBy('YearBuilt', 'desc');
            }
        } else {
            // Default ordering
            $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
                ->orderBy('YearBuilt', 'desc');
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

    public function getCondominiums(Request $request)
    {
        // Start with the base query
        $query = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ]);

        // Filter properties by PropertySubType = Condominium
        $query->where('PropertySubType', 'Condominium');

        // Apply StandardStatus filter (Active properties only)
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
                $query->join('property_details', 'properties.id', '=', 'property_details.property_id')
                    ->orderBy('property_details.' . $orderBy, $direction)
                    ->select('properties.*'); // Make sure we only select from the properties table
            } else {
                // For columns in the main properties table
                $query->orderBy($orderBy, $direction);
            }
        } else {
            // Default ordering by ListPrice
            $query->orderBy('ListPrice', 'desc');
        }

        // Get the total count before pagination
        $totalCount = $query->count();

        // Handle pagination parameters
        $limit = $request->input('limit', 10); // Default to 10 items per page
        $page = $request->input('page', 1);    // Default to first page
        $offset = ($page - 1) * $limit;        // Calculate the offset

        // Apply limit and offset
        $properties = $query->skip($offset)->take($limit)->get();

        // Format the response with success, data, and meta fields
        return response()->json([
            'success' => true,
            'data' => $properties,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }


    public function getDevelopments(Request $request)
    {
        // Start with the base query to get developments/buildings
        $query = Property::with([
            'details',
            'media'
        ])
            ->where('StandardStatus', 'Active')
            ->where(function ($q) {
                $q->where('YearBuilt', '>', 2024)
                    ->orWhere('PropertySubType', 'Condominium');
            });

        // Group by address or development name
        $query->select(
            'properties.*',
            DB::raw('COUNT(*) as unit_count'),
            DB::raw('MIN(ListPrice) as min_price'),
            DB::raw('MAX(ListPrice) as max_price')
        )
            ->groupBy('UnparsedAddress') // Or use ProjectName or another field that identifies the development
            ->havingRaw('COUNT(*) >= 1');

        // Apply ordering if requested
        if ($request->has('orderby')) {
            $orderBy = $request->input('orderby');
            $direction = 'asc';

            if (strpos($orderBy, ' desc') !== false) {
                $orderBy = str_replace(' desc', '', $orderBy);
                $direction = 'desc';
            }

            // Apply ordering
            $query->orderBy($orderBy, $direction);
        } else {
            // Default ordering
            $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
                ->orderBy('YearBuilt', 'desc');
        }

        // Get the total count before pagination
        $totalCount = $query->count(DB::raw('DISTINCT UnparsedAddress'));

        // Handle pagination parameters
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        // Apply limit and offset
        $developments = $query->skip($offset)->take($limit)->get();

        // Format the response
        return response()->json([
            'developments' => $developments,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    public function getDevelopmentUnits(Request $request, $developmentId)
    {
        // Get the development first to extract the address or identifier
        $development = Property::findOrFail($developmentId);
        $developmentAddress = $development->UnparsedAddress;

        // Get all units in this development
        $query = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ])
            ->where('UnparsedAddress', $developmentAddress)
            ->where('StandardStatus', 'Active');

        // Apply ordering
        if ($request->has('orderby')) {
            $orderBy = $request->input('orderby');
            $direction = 'asc';

            if (strpos($orderBy, ' desc') !== false) {
                $orderBy = str_replace(' desc', '', $orderBy);
                $direction = 'desc';
            }

            $query->orderBy($orderBy, $direction);
        } else {
            // Default ordering by price
            $query->orderBy('ListPrice', 'asc');
        }

        // Handle pagination
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        $totalCount = $query->count();
        $units = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'development' => $development,
            'units' => $units,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => $totalCount,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    }

    /**
     * Search properties with flexible address parsing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Helper method to convert full state names to abbreviations
     * 
     * @param string $state
     * @return string|null
     */
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
}
