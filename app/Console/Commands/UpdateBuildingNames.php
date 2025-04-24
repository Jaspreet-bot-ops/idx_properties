<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;

class UpdateBuildingNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:building-names {--limit=100} {--debug : Show detailed error messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update BuildingName for properties from Trestle Core IDX API';

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
        $this->info('Starting Trestle Core IDX API process to update BuildingName...');

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
        $debug = $this->option('debug');

        try {
            // Get authentication token
            $this->accessToken = $this->authenticate();
            if (!$this->accessToken) {
                $this->error('Failed to authenticate with Trestle API.');
                return 1;
            }
            $this->info('Successfully authenticated with Trestle API.');

            // Fetch properties with non-null BuildingName from Trestle API
            $propertiesWithBuildingName = $this->fetchPropertiesWithBuildingName($requestedLimit);
            
            if (empty($propertiesWithBuildingName)) {
                $this->info("No properties with BuildingName found in Trestle API.");
                return 0;
            }
            
            $this->info("Found " . count($propertiesWithBuildingName) . " properties with BuildingName in Trestle API.");
            
            $updated = 0;
            $notFound = 0;
            $errors = 0;
            
            foreach ($propertiesWithBuildingName as $propertyData) {
                try {
                    $listingKey = $propertyData['ListingKey'] ?? null;
                    $buildingName = $propertyData['BuildingName'] ?? null;
                    
                    if (!$listingKey || !$buildingName) {
                        $this->warn("Missing ListingKey or BuildingName in API response. Skipping.");
                        continue;
                    }
                    
                    // Find property in our database
                    $property = Property::where('ListingKey', $listingKey)->first();
                    
                    if (!$property) {
                        $this->info("Property with ListingKey {$listingKey} not found in database. Skipping.");
                        $notFound++;
                        continue;
                    }
                    
                    // Update BuildingName
                    $property->BuildingName = $buildingName;
                    $property->save();
                    
                    $this->info("Updated BuildingName to '{$buildingName}' for ListingKey: {$listingKey}");
                    $updated++;
                } catch (\Exception $e) {
                    $errors++;
                    if ($debug) {
                        $this->error('Error processing property: ' . $e->getMessage());
                        $this->error('Property: ' . ($propertyData['ListingKey'] ?? 'unknown'));
                    }
                    Log::error('Error updating BuildingName: ' . $e->getMessage(), [
                        'property' => $propertyData['ListingKey'] ?? 'unknown',
                        'exception' => $e
                    ]);
                }
            }
            
            $this->info("Completed updating BuildingName:");
            $this->info("- Successfully updated: $updated");
            $this->info("- Not found in database: $notFound");
            $this->info("- Errors: $errors");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Error in UpdateBuildingNames command: ' . $e->getMessage(), ['exception' => $e]);
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
     * Fetch properties with non-null BuildingName from Trestle API
     * 
     * @param int $limit Maximum number of properties to fetch
     * @return array Array of property data
     */
    protected function fetchPropertiesWithBuildingName($limit)
    {
        $this->info("Fetching properties with non-null BuildingName from Trestle API (limit: $limit)...");
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                // Build the OData query to filter properties with non-null BuildingName
                $url = $this->apiBaseUrl . '/odata/Property';
                $query = [
                    '$filter' => "BuildingName ne null",
                    '$top' => $limit,
                    '$select' => "ListingKey,BuildingName", // Only select the fields we need
                    '$count' => 'true' // Get total count
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

                // Log the total count if available
                if (isset($data['@odata.count'])) {
                    $this->info("Total properties with BuildingName in Trestle API: " . $data['@odata.count']);
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
                sleep(2); // Wait 2 seconds before retrying
            }
        }
        
        return [];
    }
}
