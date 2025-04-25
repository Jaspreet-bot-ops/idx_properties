<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyDetail;
use App\Models\PropertyFinancialDetail;
use App\Models\PropertyMedia;
use App\Models\PropertySchool;
use Carbon\Carbon;

class UpdateTrestleProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:trestle-properties {--hours=1 : Hours to look back for updates} {--debug : Show detailed error messages} {--geocode : Geocode property addresses}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update properties from Trestle API that have been modified recently';

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

    public function handle()
    {
        // Increase memory limit if possible
        ini_set('memory_limit', '1G');

        $this->info('Starting Trestle property update process...');

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

        $hours = $this->option('hours');
        $debug = $this->option('debug');
        $geocode = $this->option('geocode');

        try {
            // Get authentication token
            $this->accessToken = $this->authenticate();
            if (!$this->accessToken) {
                $this->error('Failed to authenticate with Trestle API.');
                return 1;
            }
            $this->info('Successfully authenticated with Trestle API.');

            // Calculate the timestamp for filtering
            $lookbackTime = Carbon::now()->subHours($hours)->format('Y-m-d\TH:i:s\Z');
            // $lookbackTime = Carbon::parse('2025-04-22 00:00:00')->format('Y-m-d\TH:i:s\Z');
            $this->info("Fetching properties modified since: {$lookbackTime}");

            // Process properties in batches
            $this->processBatches($lookbackTime, $debug, $geocode);

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Error in UpdateTrestleProperties command: ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }
    }

    /**
     * Process properties in batches to avoid memory issues
     */
    protected function processBatches($timestamp, $debug, $geocode)
    {
        $maxRetries = 3;
        $skip = 0;
        $batchSize = 200;
        $morePropertiesAvailable = true;
        $totalProcessed = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalGeocoded = 0;
        $totalErrors = 0;
        $totalVerified = 0;
        $totalDiscrepancies = 0;

        // Create arrays to store listing keys for logging
        $insertedListingKeys = [];
        $updatedListingKeys = [];

        while ($morePropertiesAvailable) {
            try {
                // Build the OData query
                $url = $this->apiBaseUrl . '/odata/Property';
                $query = [
                    '$top' => $batchSize,
                    '$skip' => $skip,
                    '$expand' => 'Media',
                    '$filter' => "ModificationTimestamp gt {$timestamp}",
                    '$orderby' => 'ModificationTimestamp asc',
                    '$count' => 'true'
                ];

                $response = Http::timeout(120)
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
                    break;
                }

                $data = $response->json();

                if (!isset($data['value']) || !is_array($data['value'])) {
                    $this->error('API response does not contain property data.');
                    Log::error('Trestle API response missing property data', [
                        'response' => $data
                    ]);
                    break;
                }

                $properties = $data['value'];
                $fetchedCount = count($properties);

                if ($fetchedCount > 0) {
                    $skip += $fetchedCount;
                    $this->info("Fetched {$fetchedCount} properties (total: {$skip})");

                    // Process this batch
                    $processed = 0;
                    $errors = 0;
                    $inserted = 0;
                    $updated = 0;
                    $geocoded = 0;
                    $verifiedCount = 0;
                    $discrepancyCount = 0;

                    // Process each property in the batch
                    foreach ($properties as $propertyData) {
                        try {
                            DB::beginTransaction();

                            $listingKey = $propertyData['ListingKey'] ?? null;

                            if (!$listingKey) {
                                throw new \Exception('Property data missing ListingKey');
                            }

                            // Check if property already exists
                            $property = Property::where('ListingKey', $listingKey)->first();
                            $exists = $property !== null;

                            // If property doesn't exist, create a new one
                            if (!$exists) {
                                $property = new Property();
                                $inserted++;
                                // Log the inserted listing key
                                $insertedListingKeys[] = $listingKey;
                                Log::info("Inserting new property", ['ListingKey' => $listingKey]);
                            } else {
                                $updated++;
                                // Log the updated listing key
                                $updatedListingKeys[] = $listingKey;
                                Log::info("Updating existing property", ['ListingKey' => $listingKey]);
                            }

                            // Map property data to model
                            $this->mapPropertyData($property, $propertyData);
                            $property->updated = 1;

                            // Geocode the property address if option is enabled
                            if ($geocode && $property->UnparsedAddress) {
                                $geocodeData = $this->getGeocodeData($property);
                                if ($geocodeData) {
                                    $property->Latitude = $geocodeData['lat'];
                                    $property->Longitude = $geocodeData['lng'];
                                    $property->Country = $geocodeData['country'];
                                    $geocoded++;

                                    if ($debug) {
                                        $this->info("Geocoded address for property: " . $listingKey);
                                    }
                                }
                            }

                            $property->save();

                            // Delete existing related data to avoid duplicates or stale data
                            if ($exists) {
                                $property->details()->delete();
                                $property->amenities()->delete();
                                $property->media()->delete();
                                $property->schools()->delete();
                                $property->financialDetails()->delete();
                            }

                            $this->savePropertyDetails($property, $propertyData);
                            $this->savePropertyAmenities($property, $propertyData);
                            $this->savePropertyMedia($property, $propertyData['Media'] ?? []);
                            $this->savePropertySchools($property, $propertyData);
                            $this->savePropertyFinancialDetails($property, $propertyData);

                            DB::commit();
                            $processed++;

                            if ($debug) {
                                $this->info("Successfully processed property: " . $listingKey);
                            }

                            // Verify the property data
                            $discrepancies = $this->verifyPropertyData($property, $propertyData);

                            if (empty($discrepancies)) {
                                $verifiedCount++;
                            } else {
                                $discrepancyCount++;
                                if ($debug) {
                                    $this->warn("Discrepancies found for property {$listingKey}:");
                                    foreach ($discrepancies as $field => $values) {
                                        $this->line("  - {$field}: Expected '{$values['expected']}', Got '{$values['actual']}'");
                                    }
                                }

                                Log::warning("Data discrepancies found for property", [
                                    'property' => $listingKey,
                                    'discrepancies' => $discrepancies
                                ]);
                            }
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $errors++;

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

                    // Update totals
                    $totalProcessed += $processed;
                    $totalInserted += $inserted;
                    $totalUpdated += $updated;
                    $totalGeocoded += $geocoded;
                    $totalErrors += $errors;
                    $totalVerified += $verifiedCount;
                    $totalDiscrepancies += $discrepancyCount;

                    $this->info("Batch processing completed:");
                    $this->info("- Processed: $processed");
                    $this->info("- Inserted: $inserted");
                    $this->info("- Updated: $updated");
                    $this->info("- Geocoded: $geocoded");
                    $this->info("- Verified: $verifiedCount");
                    $this->info("- Discrepancies: $discrepancyCount");
                    $this->info("- Errors: $errors");

                    // Free up memory
                    unset($properties);
                    unset($data);
                    gc_collect_cycles();
                }

                // If we got fewer properties than requested, we've reached the end
                if ($fetchedCount < $batchSize) {
                    $morePropertiesAvailable = false;
                }

                // Small delay between requests
                if ($morePropertiesAvailable) {
                    sleep(1);
                }
            } catch (\Exception $e) {
                $this->error('Exception fetching properties: ' . $e->getMessage());
                Log::error('Exception fetching properties from Trestle API', [
                    'exception' => $e->getMessage()
                ]);
                break;
            }
        }

        // Log summary of inserted and updated listing keys
        Log::info("Properties inserted", [
            'count' => count($insertedListingKeys),
            'listing_keys' => $insertedListingKeys
        ]);

        Log::info("Properties updated", [
            'count' => count($updatedListingKeys),
            'listing_keys' => $updatedListingKeys
        ]);

        $this->info("Overall processing completed:");
        $this->info("- Total processed: $totalProcessed");
        $this->info("- Newly inserted: $totalInserted");
        $this->info("- Updated: $totalUpdated");
        $this->info("- Geocoded: $totalGeocoded");
        $this->info("- Verified: $totalVerified");
        $this->info("- Discrepancies: $totalDiscrepancies");
        $this->info("- Errors: $totalErrors");
    }


    protected function verifyPropertyData(Property $property, array $expectedData)
    {
        $discrepancies = [];

        // List of important fields to verify
        $fieldsToVerify = [
            'ListingId', 'ListPrice', 'StandardStatus', 'BedroomsTotal',
            'BathroomsTotalInteger', 'LivingArea', 'YearBuilt', 'PostalCode'
        ];

        foreach ($fieldsToVerify as $field) {
            if (isset($expectedData[$field]) && $property->$field != $expectedData[$field]) {
                $discrepancies[$field] = [
                    'expected' => $expectedData[$field],
                    'actual' => $property->$field
                ];
            }
        }

        // Verify related data if needed
        if (!empty($expectedData['Media']) && $property->media()->count() != count($expectedData['Media'])) {
            $discrepancies['MediaCount'] = [
                'expected' => count($expectedData['Media']),
                'actual' => $property->media()->count()
            ];
        }

        // Verify property details
        $details = $property->details;
        if ($details) {
            $detailsFieldsToVerify = [
                'BuildingAreaTotal', 'StructureType', 'PropertyCondition'
            ];

            foreach ($detailsFieldsToVerify as $field) {
                if (isset($expectedData[$field]) && $details->$field != $expectedData[$field]) {
                    $discrepancies["details.{$field}"] = [
                        'expected' => $expectedData[$field],
                        'actual' => $details->$field
                    ];
                }
            }
        }

        return $discrepancies;
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
            $response = Http::asForm()->post($this->tokenUrl, [
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
     * Fetch properties updated since the given timestamp
     * 
     * @param string $timestamp ISO 8601 timestamp
     * @return array Array of property data
     */
    // protected function fetchUpdatedProperties($timestamp)
    // {
    //     $this->info("Fetching properties updated since {$timestamp}...");
    //     $maxRetries = 3;
    //     $retryCount = 0;
    //     $allProperties = [];
    //     $skip = 0;
    //     $batchSize = 200;
    //     $morePropertiesAvailable = true;

    //     while ($morePropertiesAvailable && $retryCount <= $maxRetries) {
    //         try {
    //             // Build the OData query
    //             $url = $this->apiBaseUrl . '/odata/Property';
    //             $query = [
    //                 '$top' => $batchSize,
    //                 '$skip' => $skip,
    //                 '$expand' => 'Media',
    //                 '$filter' => "ModificationTimestamp gt {$timestamp}",
    //                 '$orderby' => 'ModificationTimestamp asc',
    //                 '$count' => 'true'
    //             ];

    //             $response = Http::timeout(120)
    //                 ->withToken($this->accessToken)
    //                 ->withHeaders([
    //                     'Accept' => 'application/json'
    //                 ])
    //                 ->get($url, $query);

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

    //             $properties = $data['value'];
    //             $fetchedCount = count($properties);

    //             if ($fetchedCount > 0) {
    //                 $allProperties = array_merge($allProperties, $properties);
    //                 $skip += $fetchedCount;
    //                 $this->info("Fetched {$fetchedCount} properties (total: " . count($allProperties) . ")");
    //             }

    //             // If we got fewer properties than requested, we've reached the end
    //             if ($fetchedCount < $batchSize) {
    //                 $morePropertiesAvailable = false;
    //             }

    //             // Small delay between requests
    //             if ($morePropertiesAvailable) {
    //                 sleep(1);
    //             }
    //         } catch (\Exception $e) {
    //             $retryCount++;

    //             if ($retryCount > $maxRetries) {
    //                 $this->error('Exception fetching properties: ' . $e->getMessage());
    //                 Log::error('Exception fetching properties from Trestle API', [
    //                     'exception' => $e->getMessage()
    //                 ]);
    //                 break;
    //             }

    //             $this->warn("Request failed, retrying ({$retryCount}/{$maxRetries})...");
    //             sleep(5); // Wait 5 seconds before retrying
    //         }
    //     }

    //     return $allProperties;
    // }


    // protected function fetchUpdatedProperties($timestamp)
    // {
    //     $this->info("Fetching properties updated since {$timestamp}...");
    //     $maxRetries = 3;
    //     $skip = 0;
    //     $batchSize = 200;
    //     $morePropertiesAvailable = true;
    //     $totalProcessed = 0;
    //     $totalInserted = 0;
    //     $totalUpdated = 0;
    //     $totalGeocoded = 0;
    //     $totalErrors = 0;
    //     $debug = $this->option('debug');
    //     $geocode = $this->option('geocode');

    //     while ($morePropertiesAvailable) {
    //         try {
    //             // Build the OData query
    //             $url = $this->apiBaseUrl . '/odata/Property';
    //             $query = [
    //                 '$top' => $batchSize,
    //                 '$skip' => $skip,
    //                 '$expand' => 'Media',
    //                 '$filter' => "ModificationTimestamp gt {$timestamp}",
    //                 '$orderby' => 'ModificationTimestamp asc',
    //                 '$count' => 'true'
    //             ];

    //             $response = Http::timeout(120)
    //                 ->withToken($this->accessToken)
    //                 ->withHeaders([
    //                     'Accept' => 'application/json'
    //                 ])
    //                 ->get($url, $query);

    //             if (!$response->successful()) {
    //                 $this->error('API request failed: ' . $response->status() . ' ' . $response->body());
    //                 Log::error('Trestle API request failed', [
    //                     'status' => $response->status(),
    //                     'body' => $response->body()
    //                 ]);
    //                 break;
    //             }

    //             $data = $response->json();

    //             if (!isset($data['value']) || !is_array($data['value'])) {
    //                 $this->error('API response does not contain property data.');
    //                 Log::error('Trestle API response missing property data', [
    //                     'response' => $data
    //                 ]);
    //                 break;
    //             }

    //             $properties = $data['value'];
    //             $fetchedCount = count($properties);

    //             if ($fetchedCount > 0) {
    //                 $skip += $fetchedCount;
    //                 $this->info("Fetched {$fetchedCount} properties (total: {$skip})");

    //                 // Process this batch immediately
    //                 $batchResults = $this->processPropertyBatch($properties, $debug, $geocode);
    //                 $totalProcessed += $batchResults['processed'];
    //                 $totalInserted += $batchResults['inserted'];
    //                 $totalUpdated += $batchResults['updated'];
    //                 $totalGeocoded += $batchResults['geocoded'];
    //                 $totalErrors += $batchResults['errors'];

    //                 // Free up memory
    //                 unset($properties);
    //                 unset($data);
    //                 gc_collect_cycles();
    //             }

    //             // If we got fewer properties than requested, we've reached the end
    //             if ($fetchedCount < $batchSize) {
    //                 $morePropertiesAvailable = false;
    //             }

    //             // Small delay between requests
    //             if ($morePropertiesAvailable) {
    //                 sleep(1);
    //             }
    //         } catch (\Exception $e) {
    //             $this->error('Exception fetching properties: ' . $e->getMessage());
    //             Log::error('Exception fetching properties from Trestle API', [
    //                 'exception' => $e->getMessage()
    //             ]);
    //             break;
    //         }
    //     }

    //     $this->info("Processing completed:");
    //     $this->info("- Total processed: $totalProcessed");
    //     $this->info("- Newly inserted: $totalInserted");
    //     $this->info("- Updated: $totalUpdated");
    //     $this->info("- Geocoded: $totalGeocoded");
    //     $this->info("- Errors: $totalErrors");

    //     return true;
    // }

    // Add this new method to process each batch
    // protected function processPropertyBatch($properties, $debug, $geocode)
    // {
    //     $processed = 0;
    //     $errors = 0;
    //     $inserted = 0;
    //     $updated = 0;
    //     $geocoded = 0;

    //     foreach ($properties as $propertyData) {
    //         try {
    //             DB::beginTransaction();

    //             $listingKey = $propertyData['ListingKey'] ?? null;

    //             if (!$listingKey) {
    //                 throw new \Exception('Property data missing ListingKey');
    //             }

    //             // Check if property already exists
    //             $property = Property::where('ListingKey', $listingKey)->first();
    //             $exists = $property !== null;

    //             // If property doesn't exist, create a new one
    //             if (!$exists) {
    //                 $property = new Property();
    //                 $inserted++;
    //             } else {
    //                 $updated++;
    //             }

    //             // Map property data to model
    //             $this->mapPropertyData($property, $propertyData);

    //             // Increment the updated counter for the property
    //             $property->updated = ($property->updated ?? 0) + 1;

    //             // Geocode the property address if option is enabled
    //             if ($geocode && $property->UnparsedAddress) {
    //                 $geocodeData = $this->getGeocodeData($property);
    //                 if ($geocodeData) {
    //                     $property->Latitude = $geocodeData['lat'];
    //                     $property->Longitude = $geocodeData['lng'];
    //                     $property->Country = $geocodeData['country'];
    //                     $geocoded++;

    //                     if ($debug) {
    //                         $this->info("Geocoded address for property: " . $listingKey);
    //                     }
    //                 }
    //             }

    //             $property->save();

    //             // Delete existing related data to avoid duplicates or stale data
    //             if ($exists) {
    //                 $property->details()->delete();
    //                 $property->amenities()->delete();
    //                 $property->media()->delete();
    //                 $property->schools()->delete();
    //                 $property->financialDetails()->delete();
    //             }

    //             $this->savePropertyDetails($property, $propertyData);
    //             $this->savePropertyAmenities($property, $propertyData);
    //             $this->savePropertyMedia($property, $propertyData['Media'] ?? []);
    //             $this->savePropertySchools($property, $propertyData);
    //             $this->savePropertyFinancialDetails($property, $propertyData);

    //             DB::commit();
    //             $processed++;

    //             if ($debug) {
    //                 $this->info("Successfully processed property: " . $listingKey);
    //             }
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             $errors++;

    //             if ($debug) {
    //                 $this->error('Error processing property: ' . $e->getMessage());
    //                 $this->error('Property: ' . ($propertyData['ListingKey'] ?? 'unknown'));
    //             }

    //             Log::error('Error processing property: ' . $e->getMessage(), [
    //                 'property' => $propertyData['ListingKey'] ?? 'unknown',
    //                 'exception' => $e
    //             ]);
    //         }
    //     }

    //     $this->info("Batch processing completed:");
    //     $this->info("- Processed: $processed");
    //     $this->info("- Inserted: $inserted");
    //     $this->info("- Updated: $updated");
    //     $this->info("- Geocoded: $geocoded");
    //     $this->info("- Errors: $errors");

    //     return [
    //         'processed' => $processed,
    //         'inserted' => $inserted,
    //         'updated' => $updated,
    //         'geocoded' => $geocoded,
    //         'errors' => $errors
    //     ];
    // }

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
            'ListingId', 'ListingKey', 'ListingKeyNumeric', 'PropertyType', 'PropertySubType', 'BuildingName',
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
        $details = PropertyDetail::firstOrNew(['property_id' => $property->id]);

        // Map fields from API data to property details based on your migration
        $fieldsToMap = [
            'BuildingAreaTotal', 'BuildingAreaSource', 'StructureType', 'ArchitecturalStyle',
            'Stories', 'StoriesTotal', 'Levels', 'EntryLevel', 'EntryLocation',
            'CommonWalls', 'ConstructionMaterials', 'Roof', 'PropertyCondition',
            'Ownership', 'OwnershipType', 'NewConstructionYN', 'PropertyAttachedYN',
            'HabitableResidenceYN', 'YearEstablished', 'DevelopmentStatus', 'DirectionFaces',
            'Heating', 'HeatingYN', 'Cooling', 'CoolingYN', 'Electric',
            'ElectricOnPropertyYN', 'WaterSource', 'Sewer', 'LotFeatures',
            'Vegetation', 'View', 'ViewYN', 'RoadSurfaceType',
            'RoadFrontageType', 'RoadResponsibility', 'ParcelNumber', 'TaxLot',
            'Zoning', 'TaxLegalDescription', 'TaxAnnualAmount', 'TaxYear',
            'PublicSurveySection', 'PublicSurveyTownship', 'PublicSurveyRange',
            'Possession', 'CurrentUse', 'PossibleUse', 'WaterfrontYN',
            'WaterfrontFeatures', 'Disclosures', 'SpecialListingConditions',
            'Contingency', 'MajorChangeType'
        ];

        foreach ($fieldsToMap as $field) {
            if (isset($data[$field])) {
                $details->$field = $data[$field];
            }
        }

        // Handle the GreenEnergyEfficient field specially - convert string to boolean
        if (isset($data['GreenEnergyEfficient'])) {
            // If it's already a boolean or 0/1, use it directly
            if (is_bool($data['GreenEnergyEfficient']) || in_array($data['GreenEnergyEfficient'], [0, 1, '0', '1'])) {
                $details->GreenEnergyEfficient = $data['GreenEnergyEfficient'];
            } else {
                // Otherwise, just set it to true if there's any value
                $details->GreenEnergyEfficient = 1;
            }
        }

        // Handle date fields
        if (isset($data['AvailabilityDate'])) {
            $details->AvailabilityDate = date('Y-m-d', strtotime($data['AvailabilityDate']));
        }

        // Save the details
        $property->details()->save($details);
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
        $amenities = PropertyAmenity::firstOrNew(['property_id' => $property->id]);

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
            return;
        }

        // Delete existing media for this property to avoid duplicates
        $property->media()->delete();

        foreach ($mediaData as $index => $media) {
            if (!isset($media['MediaURL'])) {
                continue;
            }

            $mediaModel = new PropertyMedia();
            $mediaModel->property_id = $property->id;
            $mediaModel->url = $media['MediaURL'];
            $mediaModel->media_type = $media['MediaCategory'] ?? 'Image';
            $mediaModel->title = $media['MediaObjectID'] ?? null;
            $mediaModel->description = $media['ShortDescription'] ?? null;
            $mediaModel->image_of = $media['ImageOf'] ?? null;
            $mediaModel->order = $index + 1;
            $mediaModel->is_primary = $index === 0; // First image is primary
            $mediaModel->mime_type = $this->getMimeTypeFromUrl($media['MediaURL']);
            $mediaModel->save();
        }
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
        $schools = PropertySchool::firstOrNew(['property_id' => $property->id]);

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
        $financialDetails = PropertyFinancialDetail::firstOrNew(['property_id' => $property->id]);

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

    /**
     * Get geocode data for a property
     * 
     * @param Property $property The property model
     * @return array|null Geocode data with lat, lng, and country
     */
    protected function getGeocodeData($property)
    {
        if (!$property->UnparsedAddress) {
            return null;
        }

        $address = urlencode($property->UnparsedAddress);
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json?access_token=pk.eyJ1IjoiamFzcC1yZWV0IiwiYSI6ImNtOWxiaXluczAyeHUybHIxc2sycHVsNjQifQ.NW350JyVU-z-cMkzgdCrNw";

        $response = Http::withOptions(['verify' => false])->get($url);

        $data = $response->json();

        if (!isset($data['features'][0])) {
            return null;
        }

        $location = $data['features'][0]['geometry']['coordinates'];
        $country = null;

        foreach ($data['features'][0]['context'] ?? [] as $component) {
            if (strpos($component['id'], 'country') !== false) {
                $country = $component['text'];
                break;
            }
        }

        return [
            'lat' => $location[1],
            'lng' => $location[0],
            'country' => $country,
        ];
    }
}
