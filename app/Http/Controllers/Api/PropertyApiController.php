<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyApiController extends Controller
{
    // public function getNewDevelopments(Request $request)
    // {
    //     // Start with the base query
    //     $query = Property::with([
    //         'details',
    //         'amenities',
    //         'media',
    //         'schools',
    //         'financialDetails'
    //     ]);

    //     // Apply YearBuilt filter (null or greater than 2024)
    //     $query->where('YearBuilt', '>', 2024);

    //     // Apply StandardStatus filter
    //     $query->where('StandardStatus', 'Active');

    //     // Apply ordering if requested
    //     if ($request->has('orderby')) {
    //         $orderBy = $request->input('orderby');
    //         $direction = 'asc';

    //         if (strpos($orderBy, ' desc') !== false) {
    //             $orderBy = str_replace(' desc', '', $orderBy);
    //             $direction = 'desc';
    //         }

    //         // Check if the order column is in the details table
    //         if (in_array($orderBy, ['DevelopmentStatus'])) {
    //             // For columns in related tables, we need to use a different approach
    //             // We'll order by the relationship's column
    //             $query->join('property_details', 'properties.id', '=', 'property_details.property_id')
    //                 ->orderBy('property_details.' . $orderBy, $direction)
    //                 ->select('properties.*'); // Make sure we only select from the properties table
    //         } else {
    //             // For columns in the main properties table
    //             $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
    //                 ->orderBy('YearBuilt', 'desc');
    //         }
    //     } else {
    //         // Default ordering
    //         $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
    //             ->orderBy('YearBuilt', 'desc');
    //     }

    //     // Get the total count before pagination
    //     $totalCount = $query->count();

    //     // Handle pagination parameters
    //     $limit = $request->input('limit', 10); // Default to 10 items per page
    //     $page = $request->input('page', 1);    // Default to first page
    //     $offset = ($page - 1) * $limit;        // Calculate the offset

    //     // Apply limit and offset
    //     $properties = $query->skip($offset)->take($limit)->get();

    //     // Format the response
    //     return response()->json([
    //         'properties' => $properties,
    //         'meta' => [
    //             'current_page' => (int)$page,
    //             'per_page' => (int)$limit,
    //             'total' => $totalCount,
    //             'has_more' => ($offset + $limit) < $totalCount
    //         ]
    //     ]);
    // }


    // public function getNewDevelopments(Request $request)
    // {
    //     // Start with a query to group new developments by address
    //     $buildingsQuery = DB::table('properties')
    //         ->select(
    //             'StreetNumber',
    //             'StreetName',
    //             'City',
    //             'StateOrProvince',
    //             'PostalCode',
    //             'PropertySubType',
    //             'YearBuilt',
    //             DB::raw('COUNT(*) as unit_count'),
    //             DB::raw('MIN(ListPrice) as min_price'),
    //             DB::raw('MAX(ListPrice) as max_price'),
    //             DB::raw('MIN(id) as representative_id') // Get one property ID to represent the building
    //         )
    //         ->where('YearBuilt', '>', 2024)
    //         ->where('StandardStatus', 'Active')
    //         ->whereNotNull('StreetNumber')
    //         ->whereNotNull('StreetName')
    //         ->groupBy('StreetNumber', 'StreetName', 'City', 'StateOrProvince', 'PostalCode', 'PropertySubType', 'YearBuilt');

    //     // Apply ordering if requested
    //     if ($request->has('orderby')) {
    //         $orderBy = $request->input('orderby');
    //         $direction = 'asc';

    //         if (strpos($orderBy, ' desc') !== false) {
    //             $orderBy = str_replace(' desc', '', $orderBy);
    //             $direction = 'desc';
    //         }

    //         // Map the order column to an appropriate aggregate function if needed
    //         switch ($orderBy) {
    //             case 'ListPrice':
    //                 $buildingsQuery->orderBy('min_price', $direction);
    //                 break;
    //             case 'unit_count':
    //                 $buildingsQuery->orderBy('unit_count', $direction);
    //                 break;
    //             default:
    //                 // For other columns, try to order by them directly
    //                 $buildingsQuery->orderBy($orderBy, $direction);
    //                 break;
    //         }
    //     } else {
    //         // Default ordering - prioritize condominiums and newer buildings
    //         $buildingsQuery->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
    //             ->orderBy('YearBuilt', 'desc')
    //             ->orderBy('unit_count', 'desc');
    //     }

    //     // Get the total count before pagination
    //     $totalCount = $buildingsQuery->count();

    //     // Handle pagination parameters
    //     $limit = $request->input('limit', 10); // Default to 10 items per page
    //     $page = $request->input('page', 1); // Default to first page
    //     $offset = ($page - 1) * $limit; // Calculate the offset

    //     // Apply limit and offset
    //     $buildings = $buildingsQuery->skip($offset)->take($limit)->get();

    //     // Get the representative property IDs
    //     $representativeIds = $buildings->pluck('representative_id')->toArray();

    //     // Fetch the representative properties with their relationships
    //     $representativeProperties = Property::with(['details', 'media', 'amenities', 'schools', 'financialDetails'])
    //         ->whereIn('id', $representativeIds)
    //         ->get()
    //         ->keyBy('id'); // Index by ID for easier lookup

    //     // Format the building data with representative property information
    //     $formattedBuildings = $buildings->map(function ($building) use ($representativeProperties) {
    //         // Get the representative property
    //         $property = $representativeProperties[$building->representative_id] ?? null;

    //         // Create a building name from the address
    //         $buildingName = trim($building->StreetNumber . ' ' . $building->StreetName);

    //         // Get a representative image URL if available
    //         $imageUrl = null;
    //         if ($property && isset($property->media) && $property->media->isNotEmpty()) {
    //             $imageUrl = $property->media->first()->MediaURL ?? null;
    //         }

    //         // Clean up city value
    //         $city = ($building->City == ',' || empty($building->City)) ? null : $building->City;

    //         // Create the formatted building data
    //         $formattedBuilding = [
    //             'id' => $building->representative_id,
    //             'building_name' => $buildingName,
    //             'address' => $buildingName,
    //             'city' => $city,
    //             'state' => $building->StateOrProvince,
    //             'postal_code' => $building->PostalCode,
    //             'property_subtype' => $building->PropertySubType,
    //             'year_built' => $building->YearBuilt,
    //             'unit_count' => $building->unit_count,
    //             'price_range' => [
    //                 'min' => $building->min_price,
    //                 'max' => $building->max_price
    //             ],
    //             'image_url' => $imageUrl,
    //             'action_url' => "/api/buildings?street_number={$building->StreetNumber}&street_name=" . urlencode($building->StreetName)
    //         ];

    //         // Add related data from the representative property
    //         if ($property) {
    //             // Add details if available
    //             if (isset($property->details)) {
    //                 $formattedBuilding['details'] = $property->details;
    //             }

    //             // Add amenities if available
    //             if (isset($property->amenities)) {
    //                 $formattedBuilding['amenities'] = $property->amenities;
    //             }

    //             // Add schools if available
    //             if (isset($property->schools)) {
    //                 $formattedBuilding['schools'] = $property->schools;
    //             }

    //             // Add financial details if available
    //             if (isset($property->financialDetails)) {
    //                 $formattedBuilding['financial_details'] = $property->financialDetails;
    //             }
    //         }

    //         return $formattedBuilding;
    //     });

    //     // Format the response
    //     return response()->json([
    //         'properties' => $formattedBuildings,
    //         'meta' => [
    //             'current_page' => (int)$page,
    //             'per_page' => (int)$limit,
    //             'total' => $totalCount,
    //             'has_more' => ($offset + $limit) < $totalCount
    //         ]
    //     ]);
    // }

    public function getNewDevelopments(Request $request)
    {
        // Start with a query to group new developments by address
        $buildingsQuery = DB::table('properties')
            ->select(
                'StreetNumber',
                'StreetName',
                'City',
                'StreetDirPrefix',
                'StreetSuffix',
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
        $representativeProperties = Property::with([ 'media'])
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

    // public function getDevelopments(Request $request)
    // {
    //     // Start with the base query to get developments/buildings
    //     $query = Property::with([
    //         'details',
    //         'media'
    //     ])
    //         ->where('StandardStatus', 'Active')
    //         ->where(function ($q) {
    //             $q->where('YearBuilt', '>', 2024)
    //                 ->orWhere('PropertySubType', 'Condominium');
    //         });

    //     // Group by address or development name
    //     $query->select(
    //         'properties.*',
    //         DB::raw('COUNT(*) as unit_count'),
    //         DB::raw('MIN(ListPrice) as min_price'),
    //         DB::raw('MAX(ListPrice) as max_price')
    //     )
    //         ->groupBy('UnparsedAddress') // Or use ProjectName or another field that identifies the development
    //         ->havingRaw('COUNT(*) >= 1');

    //     // Apply ordering if requested
    //     if ($request->has('orderby')) {
    //         $orderBy = $request->input('orderby');
    //         $direction = 'asc';

    //         if (strpos($orderBy, ' desc') !== false) {
    //             $orderBy = str_replace(' desc', '', $orderBy);
    //             $direction = 'desc';
    //         }

    //         // Apply ordering
    //         $query->orderBy($orderBy, $direction);
    //     } else {
    //         // Default ordering
    //         $query->orderByRaw("CASE WHEN PropertySubType = 'Condominium' THEN 0 ELSE 1 END")
    //             ->orderBy('YearBuilt', 'desc');
    //     }

    //     // Get the total count before pagination
    //     $totalCount = $query->count(DB::raw('DISTINCT UnparsedAddress'));

    //     // Handle pagination parameters
    //     $limit = $request->input('limit', 10);
    //     $page = $request->input('page', 1);
    //     $offset = ($page - 1) * $limit;

    //     // Apply limit and offset
    //     $developments = $query->skip($offset)->take($limit)->get();

    //     // Format the response
    //     return response()->json([
    //         'developments' => $developments,
    //         'meta' => [
    //             'current_page' => (int)$page,
    //             'per_page' => (int)$limit,
    //             'total' => $totalCount,
    //             'has_more' => ($offset + $limit) < $totalCount
    //         ]
    //     ]);
    // }

    // public function getDevelopmentUnits(Request $request, $developmentId)
    // {
    //     // Get the development first to extract the address or identifier
    //     $development = Property::findOrFail($developmentId);
    //     $developmentAddress = $development->UnparsedAddress;

    //     // Get all units in this development
    //     $query = Property::with([
    //         'details',
    //         'amenities',
    //         'media',
    //         'schools',
    //         'financialDetails'
    //     ])
    //         ->where('UnparsedAddress', $developmentAddress)
    //         ->where('StandardStatus', 'Active');

    //     // Apply ordering
    //     if ($request->has('orderby')) {
    //         $orderBy = $request->input('orderby');
    //         $direction = 'asc';

    //         if (strpos($orderBy, ' desc') !== false) {
    //             $orderBy = str_replace(' desc', '', $orderBy);
    //             $direction = 'desc';
    //         }

    //         $query->orderBy($orderBy, $direction);
    //     } else {
    //         // Default ordering by price
    //         $query->orderBy('ListPrice', 'asc');
    //     }

    //     // Handle pagination
    //     $limit = $request->input('limit', 20);
    //     $page = $request->input('page', 1);
    //     $offset = ($page - 1) * $limit;

    //     $totalCount = $query->count();
    //     $units = $query->skip($offset)->take($limit)->get();

    //     return response()->json([
    //         'development' => $development,
    //         'units' => $units,
    //         'meta' => [
    //             'current_page' => (int)$page,
    //             'per_page' => (int)$limit,
    //             'total' => $totalCount,
    //             'has_more' => ($offset + $limit) < $totalCount
    //         ]
    //     ]);
    // }

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

    public function propertyDetails($id)
    {
        // Find the property with all its relationships
        $property = Property::with([
            'details',
            'amenities',
            'media',
            'schools',
            'financialDetails'
        ])->findOrFail($id);

        // Format the response
        return response()->json([
            'success' => true,
            'property' => $property
        ]);
    }

    public function buildings(Request $request)
    {
        // Validate request
        $request->validate([
            'street_number' => 'required|string',
            'street_name' => 'required|string',
        ]);

        $streetNumber = $request->input('street_number');
        $streetName = $request->input('street_name');

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

        // Get all units in the building
        $units = Property::with(['details', 'media'])
            ->where('StreetNumber', $streetNumber)
            ->where('StreetName', $streetName)
            ->where('StandardStatus', 'Active')
            ->get();

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
            'total_units' => $units->count(),
            'sales_units_count' => $salesUnits->count(),
            'rental_units_count' => $rentalUnits->count()
        ]);
    }

    public function places(Request $request)
    {
        // dd("jaspreet");
        // Validate request
        $request->validate([
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:ListPrice,DateListed,BathroomsTotalInteger,BedroomsTotal,LivingArea',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'property_type' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'min_beds' => 'nullable|integer',
            'max_beds' => 'nullable|integer',
            'min_baths' => 'nullable|integer',
            'max_baths' => 'nullable|integer'
        ]);

        $city = $request->input('city');
        $state = $request->input('state');
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'ListPrice');
        $sortDir = $request->input('sort_dir', 'asc');
        $propertyType = $request->input('property_type');

        // Ensure at least one location parameter is provided
        if (empty($city) && empty($state)) {
            return response()->json([
                'success' => false,
                'message' => 'Either city or state parameter is required'
            ], 400);
        }

        // Build query
        $query = Property::with(['details', 'media'])
            ->where('StandardStatus', 'Active');

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
}
