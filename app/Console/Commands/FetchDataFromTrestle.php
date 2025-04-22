<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;

class FetchDataFromTrestle extends Command
{

    public function __construct()
{
    parent::__construct();
    ini_set('memory_limit', '512M');
}

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:trestle-properties {--limit=1000} {--offset=0} {--force : Force refresh all properties} {--debug : Show detailed error messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetching data from the Trestle Core IDX API';

    /**
     * Trestle API credentials
     */
    protected $clientId;
    protected $clientSecret;
    protected $scope;
    protected $grantType;
    protected $tokenUrl;
    protected $apiBaseUrl;
    protected $accessToken;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Trestle Core IDX API process...');

        // Load credentials from environment variables
        $this->clientId = config('services.trestle.client_id');
        $this->clientSecret = config('services.trestle.client_secret');
        $this->scope = config('services.trestle.scope', 'api');
        $this->grantType = config('services.trestle.grant_type', 'client_credentials');
        $this->tokenUrl = 'https://api-trestle.corelogic.com/trestle/oidc/connect/token';
        $this->apiBaseUrl = 'https://api-trestle.corelogic.com/trestle';

        // Check if credentials are set
        if (!$this->clientId || !$this->clientSecret) {
            $this->error('Trestle API credentials are not configured. Please set TRESTLE_CLIENT_ID and TRESTLE_CLIENT_SECRET in your .env file.');
            return 1;
        }

        $requestedLimit = $this->option('limit');
        $startOffset = $this->option('offset');
        $force = $this->option('force');
        $debug = $this->option('debug');

        try {
            // Get authentication token
            $this->accessToken = $this->authenticate();
            if (!$this->accessToken) {
                $this->error('Failed to authenticate with Trestle API.');
                return 1;
            }
            $this->info('Successfully authenticated with Trestle API.');

            // Initialize counters for pagination
            $totalProcessed = 0;
            $totalErrors = 0;
            $currentOffset = $startOffset;
            $batchSize = 200; // API seems to limit to 200 per request
            $remainingToProcess = $requestedLimit;
            $batchNumber = 1;

            // Continue fetching until we've processed the requested number or no more properties
            while ($remainingToProcess > 0) {
                // Calculate how many to fetch in this batch
                $currentBatchSize = min($batchSize, $remainingToProcess);

                $this->info("Fetching properties from Trestle API (limit: {$currentBatchSize}, offset: {$currentOffset})...");

                // Fetch properties for current batch
                $properties = $this->fetchProperties($currentBatchSize, $currentOffset);

                if (empty($properties)) {
                    $this->info('No more properties found.');
                    break;
                }

                $fetchedCount = count($properties);
                $this->info("Found {$fetchedCount} properties. Processing...");

                // Process synchronously
                $processed = 0;
                $errors = 0;

                // Process each property
                foreach ($properties as $propertyData) {
                    try {
                        DB::beginTransaction();
                        // Process and save property
                        $this->processProperty($propertyData, $force);
                        DB::commit();
                        $processed++;
                        $totalProcessed++;
                        $this->info("Successfully processed property: " . ($propertyData['ListingKey'] ?? 'unknown'));
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errors++;
                        $totalErrors++;
                        if ($debug) {
                            $this->error('Error processing property: ' . $e->getMessage());
                            $this->error('Property: ' . ($propertyData['ListingKey'] ?? 'unknown'));
                        }
                        Log::error('Error processing property: ' . $e->getMessage(), [
                            'property' => $propertyData['ListingKey'] ?? 'unknown',
                            'exception' => $e
                        ]);
                    }
                }

                $this->info("Batch #{$batchNumber} completed:");
                $this->info("- Successfully processed: $processed");
                $this->info("- Errors: $errors");

                // Update counters for next iteration
                $currentOffset += $fetchedCount;
                $remainingToProcess -= $fetchedCount;
                $batchNumber++;

                // If we got fewer properties than requested, we've reached the end
                if ($fetchedCount < $currentBatchSize) {
                    $this->info("Reached the end of available properties.");
                    break;
                }

                // Add a small delay to avoid hitting API rate limits
                sleep(1);
            }

            $this->info("Completed processing properties:");
            $this->info("- Successfully processed: $totalProcessed");
            $this->info("- Errors: $totalErrors");

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Error in FetchDataFromTrestle command: ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }
    }

    /**
     * Authenticate with Trestle API and get access token
     * 
     * @return string|null The access token or null if authentication failed
     */
    protected function authenticate()
    {
        $this->info('Authenticating with Trestle API...');

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->asForm()->post($this->tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => $this->grantType,
                'scope' => $this->scope
            ]);

            if (!$response->successful()) {
                $this->error('Authentication failed: ' . $response->status() . ' ' . $response->body());
                Log::error('Trestle API authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                $this->error('Authentication response did not contain an access token.');
                Log::error('Trestle API authentication response missing access_token', [
                    'response' => $data
                ]);
                return null;
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            $this->error('Authentication exception: ' . $e->getMessage());
            Log::error('Trestle API authentication exception', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch properties from Trestle API
     * 
     * @param int $limit Maximum number of properties to fetch
     * @param int $offset Starting offset for pagination
     * @return array Array of property data
     */
    // protected function fetchProperties($limit, $offset)
    // {
    //     $this->info("Fetching properties from Trestle API (limit: $limit, offset: $offset)...");
    //     $maxRetries = 3;
    //     $retryCount = 0;

    //     while ($retryCount <= $maxRetries) {
    //         try {
    //             // Build the OData query
    //             $url = $this->apiBaseUrl . '/odata/Property';
    //             $query = [
    //                 '$top' => $limit,
    //                 '$skip' => $offset,
    //                 '$expand' => 'Media', // Include media (photos, etc.)
    //                 '$orderby' => 'ListingKey asc'
    //             ];

    //             $response = Http::withOptions([
    //                 'verify' => false,  // Disable SSL verification
    //             ])->timeout(120)
    //             ->withToken($this->accessToken)
    //             ->withHeaders([
    //                 'Accept' => 'application/json'
    //             ])
    //             ->get($url, $query);

    //             if (!$response->successful()) {
    //                 $this->error('API request failed: ' . $response->status() . ' ' . $response->body());
    //                 Log::error('Trestle API request failed', [
    //                     'status' => $response->status(),
    //                     'body' => $response->body()
    //                 ]);
    //                 return [];
    //             }

    //             $data = $response->json();

    //             if (!isset($data['value']) || !is_array($data['value'])) {
    //                 $this->error('API response does not contain property data.');
    //                 Log::error('Trestle API response missing property data', [
    //                     'response' => $data
    //                 ]);
    //                 return [];
    //             }

    //             return $data['value'];
    //         } catch (\Exception $e) {
    //             $retryCount++;

    //             if ($retryCount > $maxRetries) {
    //                 $this->error('Exception fetching properties: ' . $e->getMessage());
    //                 Log::error('Exception fetching properties from Trestle API', [
    //                     'exception' => $e->getMessage()
    //                 ]);
    //                 return [];
    //             }

    //             $this->warn("Request timed out, retrying ({$retryCount}/{$maxRetries})...");
    //             sleep(5); // Wait 5 seconds before retrying
    //         }
    //     }
    // }

    protected function fetchProperties($limit, $offset)
    {
        $this->info("Fetching properties from Trestle API (limit: $limit, offset: $offset)...");
        $maxRetries = 3;
        $retryCount = 0;
    
        while ($retryCount <= $maxRetries) {
            try {
                // Build the OData query
                $url = $this->apiBaseUrl . '/odata/Property';
                $query = [
                    '$top' => $limit,
                    '$skip' => $offset,
                    '$expand' => 'Media', // Include media (photos, etc.)
                    '$orderby' => 'ListingKey asc'
                ];
    
                $response = Http::withOptions([
                    'verify' => false,  // Disable SSL verification
                ])->timeout(120)
                ->withToken($this->accessToken)
                ->withHeaders([
                    'Accept' => 'application/json'
                ])
                ->get($url, $query);
    
                if (!$response->successful()) {
                    $this->error('API request failed: ' . $response->status() . ' ' . $response->body());
                    Log::error('Trestle API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return [];
                }
    
                $data = $response->json();
                
                // Clear response to free memory
                $response = null;
                
                if (!isset($data['value']) || !is_array($data['value'])) {
                    $this->error('API response does not contain property data.');
                    Log::error('Trestle API response missing property data', [
                        'response' => $data
                    ]);
                    return [];
                }
    
                $result = $data['value'];
                
                // Clear data to free memory
                $data = null;
                
                // Force garbage collection
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                return $result;
            } catch (\Exception $e) {
                $retryCount++;
    
                if ($retryCount > $maxRetries) {
                    $this->error('Exception fetching properties: ' . $e->getMessage());
                    Log::error('Exception fetching properties from Trestle API', [
                        'exception' => $e->getMessage()
                    ]);
                    return [];
                }
    
                $this->warn("Request timed out, retrying ({$retryCount}/{$maxRetries})...");
                sleep(1); // Wait 5 seconds before retrying
            }
        }
    }
    

    /**
     * Process and save a property
     * 
     * @param array $propertyData Property data from API
     * @param bool $force Whether to force update existing properties
     */
    protected function processProperty($propertyData, $force)
    {
        $listingKey = $propertyData['ListingKey'] ?? null;

        if (!$listingKey) {
            throw new \Exception('Property data missing ListingKey');
        }

        // Check if property already exists
        $property = Property::where('ListingKey', $listingKey)->first();
        $exists = $property !== null;

        // If property exists and we're not forcing an update, skip it
        if ($exists && !$force) {
            return;
        }

        // If property doesn't exist, create a new one
        if (!$exists) {
            $property = new Property();
        }

        // Map property data to model
        $this->mapPropertyData($property, $propertyData);

        // Save the property
        $property->save();

        $this->savePropertyDetails($property, $propertyData);
        $this->savePropertyAmenities($property, $propertyData);
        $this->savePropertyMedia($property, $propertyData['Media'] ?? []);
        $this->savePropertySchools($property, $propertyData);
        $this->savePropertyFinancialDetails($property, $propertyData);
    }

    /**
     * Map property data to the Property model
     * 
     * @param Property $property Property model instance
     * @param array $data Property data from API
     */
    protected function mapPropertyData(Property $property, array $data)
    {
        // Map basic property fields
        $fieldsToMap = [
            'ListingId', 'ListingKey', 'ListingKeyNumeric', 'PropertyType', 'PropertySubType',
            'StandardStatus', 'MlsStatus', 'ListPrice', 'ClosePrice', 'OriginalListPrice',
            'PreviousListPrice', 'StreetNumber', 'StreetNumberNumeric', 'StreetDirPrefix',
            'StreetName', 'StreetSuffix', 'StreetDirSuffix', 'UnitNumber', 'City',
            'StateOrProvince', 'PostalCode', 'PostalCodePlus4', 'CountyOrParish',
            'SubdivisionName', 'BedroomsTotal', 'BathroomsFull', 'BathroomsHalf',
            'BathroomsTotalInteger', 'RoomsTotal', 'LivingArea', 'LotSizeAcres',
            'LotSizeSquareFeet', 'YearBuilt', 'DaysOnMarket', 'PublicRemarks',
            'SyndicationRemarks', 'Directions', 'PrivateRemarks'
        ];

        foreach ($fieldsToMap as $field) {
            if (isset($data[$field])) {
                $property->$field = $data[$field];
            }
        }

        // Handle date fields
        $dateFields = [
            'ListingContractDate', 'OnMarketDate', 'OffMarketDate', 'CloseDate', 'ContingentDate'
        ];

        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                // Convert from ISO 8601 format to Y-m-d
                $date = date('Y-m-d', strtotime($data[$field]));
                $property->$field = $date;
            }
        }

        // Build UnparsedAddress if not provided
        if (!isset($data['UnparsedAddress']) && isset($data['StreetNumber']) && isset($data['StreetName'])) {
            $address = $data['StreetNumber'];
            if (isset($data['StreetDirPrefix'])) $address .= ' ' . $data['StreetDirPrefix'];
            $address .= ' ' . $data['StreetName'];
            if (isset($data['StreetSuffix'])) $address .= ' ' . $data['StreetSuffix'];
            if (isset($data['StreetDirSuffix'])) $address .= ' ' . $data['StreetDirSuffix'];
            if (isset($data['UnitNumber'])) $address .= ' #' . $data['UnitNumber'];
            if (isset($data['City'])) $address .= ', ' . $data['City'];
            if (isset($data['StateOrProvince'])) $address .= ', ' . $data['StateOrProvince'];
            if (isset($data['PostalCode'])) $address .= ' ' . $data['PostalCode'];

            $property->UnparsedAddress = $address;
        } else if (isset($data['UnparsedAddress'])) {
            $property->UnparsedAddress = $data['UnparsedAddress'];
        }

        // Handle timestamps
        $timestampFields = [
            'OriginalEntryTimestamp', 'ModificationTimestamp', 'StatusChangeTimestamp',
            'PriceChangeTimestamp', 'PhotosChangeTimestamp', 'PendingTimestamp',
            'MajorChangeTimestamp', 'OffMarketTimestamp'
        ];

        foreach ($timestampFields as $field) {
            if (isset($data[$field])) {
                // Convert from ISO 8601 format to MySQL datetime
                $timestamp = date('Y-m-d H:i:s', strtotime($data[$field]));
                $property->$field = $timestamp;
            }
        }

        // Handle agent/office information
        $agentFields = [
            'ListAgentFullName', 'ListAgentKey', 'ListAgentMlsId', 'ListAgentEmail',
            'ListAgentDirectPhone', 'ListOfficeName', 'ListOfficeKey', 'ListOfficeMlsId',
            'ListOfficePhone'
        ];

        foreach ($agentFields as $field) {
            if (isset($data[$field])) {
                $property->$field = $data[$field];
            }
        }

        // Source information
        if (isset($data['SourceSystemKey'])) $property->SourceSystemKey = $data['SourceSystemKey'];
        if (isset($data['ListingService'])) $property->ListingService = $data['ListingService'];
    }

    /**
     * Save property details to the property_details table
     * 
     * @param Property $property The property model
     * @param array $data Property data from API
     */
    protected function savePropertyDetails(Property $property, array $data)
    {
        // Find or create property details
        $details = \App\Models\PropertyDetail::firstOrNew(['property_id' => $property->id]);

        // Map fields from API data to property details based on your migration
        $fieldsToMap = [
            'BuildingAreaTotal', 'BuildingAreaSource', 'StructureType', 'ArchitecturalStyle',
            'Stories', 'StoriesTotal', 'Levels', 'EntryLevel', 'EntryLocation',
            'CommonWalls', 'ConstructionMaterials', 'Roof', 'PropertyCondition',
            'Ownership', 'OwnershipType', 'NewConstructionYN', 'PropertyAttachedYN',
            'HabitableResidenceYN', 'GreenEnergyEfficient', 'YearEstablished',
            'DevelopmentStatus', 'DirectionFaces', 'Heating', 'HeatingYN', 'Cooling',
            'CoolingYN', 'Electric', 'ElectricOnPropertyYN', 'WaterSource', 'Sewer',
            'LotFeatures', 'Vegetation', 'View', 'ViewYN', 'RoadSurfaceType',
            'RoadFrontageType', 'RoadResponsibility', 'ParcelNumber', 'TaxLot',
            'Zoning', 'TaxLegalDescription', 'TaxAnnualAmount', 'TaxYear',
            'PublicSurveySection', 'PublicSurveyTownship', 'PublicSurveyRange',
            'Possession', 'CurrentUse', 'PossibleUse', 'AvailabilityDate',
            'WaterfrontYN', 'WaterfrontFeatures', 'Disclosures',
            'SpecialListingConditions', 'Contingency', 'MajorChangeType'
        ];

        foreach ($fieldsToMap as $field) {
            if (isset($data[$field])) {
                $details->$field = $data[$field];
            }
        }

        // Handle date fields
        if (isset($data['AvailabilityDate'])) {
            $details->AvailabilityDate = date('Y-m-d', strtotime($data['AvailabilityDate']));
        }

        // Save the details
        $property->details()->save($details);

        // $this->info("Saved property details for property ID: {$property->id}");
    }

    /**
     * Save property amenities to the property_amenities table
     * 
     * @param Property $property The property model
     * @param array $data Property data from API
     */
    protected function savePropertyAmenities(Property $property, array $data)
    {
        // Find or create property amenities
        $amenities = \App\Models\PropertyAmenity::firstOrNew(['property_id' => $property->id]);

        // Map fields from API data to property amenities based on your migration
        $fieldsToMap = [
            'InteriorFeatures', 'Appliances', 'Flooring', 'WindowFeatures',
            'DoorFeatures', 'LaundryFeatures', 'AccessibilityFeatures',
            'FireplaceYN', 'FireplaceFeatures', 'ExteriorFeatures',
            'PatioAndPorchFeatures', 'Fencing', 'OtherStructures',
            'BuildingFeatures', 'GarageYN', 'AttachedGarageYN',
            'GarageSpaces', 'CoveredSpaces', 'ParkingTotal',
            'OpenParkingYN', 'ParkingFeatures', 'PoolPrivateYN',
            'PoolFeatures', 'SpaYN', 'SpaFeatures', 'AssociationYN',
            'AssociationFee', 'AssociationFeeFrequency', 'AssociationAmenities',
            'CommunityFeatures', 'SeniorCommunityYN', 'NumberOfUnitsInCommunity',
            'HorseYN', 'HorseAmenities', 'Utilities', 'OtherEquipment',
            'Furnished', 'Inclusions'
        ];

        foreach ($fieldsToMap as $field) {
            if (isset($data[$field])) {
                $amenities->$field = $data[$field];
            }
        }

        // Save the amenities
        $property->amenities()->save($amenities);

        // $this->info("Saved property amenities for property ID: {$property->id}");
    }

    /**
     * Save property media to the property_media table
     * 
     * @param Property $property The property model
     * @param array $mediaData Media data from API
     */
    protected function savePropertyMedia(Property $property, array $mediaData)
    {
        if (empty($mediaData)) {
            $this->info("No media data available for property ID: {$property->id}");
            return;
        }

        // Delete existing media for this property to avoid duplicates
        $property->media()->delete();

        $count = 0;

        foreach ($mediaData as $index => $media) {
            if (!isset($media['MediaURL'])) {
                continue;
            }

            $mediaModel = new \App\Models\PropertyMedia();
            $mediaModel->property_id = $property->id;
            $mediaModel->url = $media['MediaURL'];
            $mediaModel->media_type = $media['MediaCategory'] ?? 'Image';
            $mediaModel->title = $media['MediaObjectID'] ?? null;
            $mediaModel->description = $media['MediaDescription'] ?? null;
            $mediaModel->order = $index + 1;
            $mediaModel->is_primary = $index === 0; // First image is primary
            $mediaModel->mime_type = $this->getMimeTypeFromUrl($media['MediaURL']);
            $mediaModel->save();

            $count++;
        }

        // $this->info("Saved {$count} media items for property ID: {$property->id}");
    }

    /**
     * Save property schools information
     * 
     * @param Property $property The property model
     * @param array $data Property data from API
     */
    protected function savePropertySchools(Property $property, array $data)
    {
        // Find or create property schools
        $schools = \App\Models\PropertySchool::firstOrNew(['property_id' => $property->id]);

        // Map school names from API data to property schools
        $schoolFields = [
            'ElementarySchool',
            'MiddleOrJuniorSchool',
            'HighSchool'
        ];

        foreach ($schoolFields as $field) {
            if (isset($data[$field])) {
                $schools->$field = $data[$field];
            }
        }

        // Map school district fields
        if (isset($data['ElementarySchoolDistrict'])) {
            $schools->ElementarySchoolDistrict = $data['ElementarySchoolDistrict'];
        }

        if (isset($data['MiddleOrJuniorSchoolDistrict'])) {
            $schools->MiddleOrJuniorSchoolDistrict = $data['MiddleOrJuniorSchoolDistrict'];
        }

        if (isset($data['HighSchoolDistrict'])) {
            $schools->HighSchoolDistrict = $data['HighSchoolDistrict'];
        }

        // Save the schools
        $property->schools()->save($schools);

        // $this->info("Saved property schools for property ID: {$property->id}");
    }

    /**
     * Save property financial details
     * 
     * @param Property $property The property model
     * @param array $data Property data from API
     */
    protected function savePropertyFinancialDetails(Property $property, array $data)
    {
        // Find or create property financial details
        $financialDetails = \App\Models\PropertyFinancialDetail::firstOrNew(['property_id' => $property->id]);

        // Map fields from API data to property financial details
        $fieldsToMap = [
            'FinancialDataSource', 'GrossIncome', 'GrossScheduledIncome',
            'NetOperatingIncome', 'TotalActualRent', 'OperatingExpense',
            'OperatingExpenseIncludes', 'InsuranceExpense', 'MaintenanceExpense',
            'ManagerExpense', 'NewTaxesExpense', 'OtherExpense', 'SuppliesExpense',
            'TrashExpense', 'LeaseAmount', 'LeaseAmountFrequency', 'LeaseTerm',
            'LeaseRenewalOptionYN', 'LeaseAssignableYN', 'ExistingLeaseType',
            'LeaseConsideredYN', 'LandLeaseAmount', 'LandLeaseAmountFrequency',
            'LandLeaseYN', 'RentIncludes', 'TenantPays', 'BusinessName',
            'BusinessType', 'NumberOfFullTimeEmployees', 'CurrentFinancing',
            'SpecialLicenses', 'ListingTerms', 'DocumentsAvailable',
            'DocumentsCount', 'HomeWarrantyYN'
        ];

        foreach ($fieldsToMap as $field) {
            if (isset($data[$field])) {
                $financialDetails->$field = $data[$field];
            }
        }

        // Save the financial details
        $property->financialDetails()->save($financialDetails);

        // $this->info("Saved property financial details for property ID: {$property->id}");
    }

    /**
     * Get mime type from URL
     * 
     * @param string $url URL of the media
     * @return string Mime type
     */
    protected function getMimeTypeFromUrl($url)
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}