<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\PropertyMedia;

class UpdatePropertyMediaImageOf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:property-media-imageof {--debug : Show detailed debug messages} {--limit= : Limit the number of properties to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update property media with ImageOf field from Trestle API';

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
        $debug = $this->option('debug');
        $limit = $this->option('limit');
        
        $this->info('Starting property media ImageOf update...');

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

        try {
            // Get authentication token
            $this->accessToken = $this->authenticate();
            if (!$this->accessToken) {
                $this->error('Failed to authenticate with Trestle API.');
                return 1;
            }
            $this->info('Successfully authenticated with Trestle API.');

            // Get properties with media that need updating
            $query = Property::whereHas('media')
                ->whereNotNull('ListingId');
            
            if ($limit) {
                $query->limit($limit);
            }
            
            $properties = $query->get();
            
            $totalProperties = $properties->count();
            $this->info("Found $totalProperties properties with media to process");
            
            $totalMediaUpdated = 0;
            $totalErrors = 0;
            
            foreach ($properties as $index => $property) {
                if ($debug) {
                    // $this->info("Processing property {$index+1}/{$totalProperties}: {$property->ListingKey} (ResourceRecordID: {$property->ResourceRecordID})");
                }
                
                try {
                    // Get media data from Trestle API
                    $mediaData = $this->fetchMediaFromTrestle($property->ListingId);
                    
                    if (empty($mediaData)) {
                        if ($debug) {
                            $this->warn("No media data found for property: {$property->ListingKey}");
                        }
                        continue;
                    }
                    
                    // Get existing media for this property
                    $existingMedia = $property->media;
                    
                    foreach ($existingMedia as $media) {
                        // Find matching media item in the API data
                        $matchingItem = $this->findMatchingMediaItem($media, $mediaData);
                        
                        if ($matchingItem && isset($matchingItem['ImageOf'])) {
                            // Update the image_of field
                            $media->image_of = $matchingItem['ImageOf'];
                            $media->save();
                            $totalMediaUpdated++;
                            
                            if ($debug) {
                                $this->info("Updated media ID: {$media->id} with ImageOf: {$matchingItem['ImageOf']}");
                            }
                        } else if ($debug) {
                            $this->warn("No matching media found for media ID: {$media->id} or ImageOf field not present");
                        }
                    }
                    
                    // Small delay to avoid API rate limits
                    usleep(500000); // 0.5 seconds
                    
                } catch (\Exception $e) {
                    $totalErrors++;
                    $this->error("Error processing property {$property->ListingKey}: {$e->getMessage()}");
                    Log::error("Error updating property media", [
                        'property_id' => $property->id,
                        'ResourceRecordID' => $property->ListingId,
                        'exception' => $e->getMessage()
                    ]);
                }
            }
            
            $this->info("Property media update completed:");
            $this->info("- Total Properties Processed: $totalProperties");
            $this->info("- Total Media Records Updated: $totalMediaUpdated");
            $this->info("- Total Errors: $totalErrors");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Error in UpdatePropertyMediaImageOf command: ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }
    }
    
    /**
     * Authenticate with Trestle API and get access token
     * 
     * @return string|null The access token or null if authentication failed
     */
    public function authenticate()
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
     * Fetch media data from Trestle API for a specific property
     * 
     * @param string $resourceRecordID The ResourceRecordID of the property
     * @return array Media data from API
     */
    public function fetchMediaFromTrestle($resourceRecordID)
    {
        $this->info("Fetching media for ResourceRecordID: $resourceRecordID");
        
        try {
            // Build the OData query
            $url = $this->apiBaseUrl . '/odata/Media';
            $query = [
                '$filter' => "ResourceRecordID eq '$resourceRecordID'",
                '$top' => 1000
            ];

            $response = Http::timeout(60)
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

            if (!isset($data['value']) || !is_array($data['value'])) {
                $this->error('API response does not contain media data.');
                Log::error('Trestle API response missing media data', [
                    'response' => $data
                ]);
                return [];
            }

            return $data['value'];
        } catch (\Exception $e) {
            $this->error('Exception fetching media: ' . $e->getMessage());
            Log::error('Exception fetching media from Trestle API', [
                'ResourceRecordID' => $resourceRecordID,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    public function findMatchingMediaItem(PropertyMedia $media, array $mediaData)
    {
        foreach ($mediaData as $item) {
            // Try to match by URL first (most reliable)
            if (isset($item['MediaURL']) && $item['MediaURL'] === $media->url) {
                return $item;
            }
            
            // If URL doesn't match, try matching by order
            if (isset($item['Order']) && $item['Order'] == $media->order) {
                return $item;
            }
            
            // You could also try matching by title if it's unique
            if (isset($item['MediaObjectID']) && $media->title && $item['MediaObjectID'] === $media->title) {
                return $item;
            }
        }
        
        return null;
    }
}
