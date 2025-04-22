<?php

namespace App\Jobs;

use App\Models\PropertyMedia;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class FetchPropertyMediaJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $propertyIds;
    protected $signature = 'fetch:property-media {--limit=1000} {--chunk=1000} {--force} {--debug}';
    protected $description = 'Fetch and save property media from Trestle API in chunks';
    protected $clientId;
    protected $clientSecret;
    protected $scope;
    protected $grantType;
    protected $tokenUrl;
    protected $apiBaseUrl;
    protected $accessToken;

    public function __construct($propertyIds)
    {
        $this->propertyIds = $propertyIds;
        $this->clientId = config('services.trestle.client_id');
        $this->clientSecret = config('services.trestle.client_secret');
        $this->scope = config('services.trestle.scope', 'api');
        $this->grantType = config('services.trestle.grant_type', 'client_credentials');
        $this->tokenUrl = 'https://api-trestle.corelogic.com/trestle/oidc/connect/token';
        $this->apiBaseUrl = 'https://api-trestle.corelogic.com/trestle';
    }

    public function handle()
    {
        $this->accessToken = $this->authenticate();

        if (!$this->accessToken) {
            Log::error('Failed to authenticate with Trestle API.');
            return;
        }

        // Get the property IDs with valid Listing IDs
        $listingIdMap = Property::whereIn('id', $this->propertyIds)
            ->whereNotNull('ListingId')
            ->pluck('id', 'ListingId');

        if ($listingIdMap->isEmpty()) {
            Log::info('No valid listings found for the provided property IDs.');
            return;
        }

        // Fetch all media for listings in parallel
        $mediaData = $this->fetchAllMediaForListings($listingIdMap);

        if ($mediaData->isEmpty()) {
            Log::info('No media found for the given listings.');
            return;
        }

        // Prepare bulk insert data
        $bulkInsert = [];
        $orderCount = [];

        foreach ($mediaData as $media) {
            $listingId = $media['ResourceRecordID'] ?? null;
            $propertyId = $listingIdMap[$listingId] ?? null;

            if (!$propertyId || !isset($media['MediaURL'])) {
                continue;
            }

            // Assign order for the media based on insertion sequence
            $order = isset($orderCount[$propertyId]) ? ++$orderCount[$propertyId] : $orderCount[$propertyId] = 1;

            $bulkInsert[] = [
                'property_id' => $propertyId,
                'url' => $media['MediaURL'],
                'media_type' => $media['MediaCategory'] ?? 'Image',
                'title' => $media['MediaObjectID'] ?? null,
                'description' => $media['MediaDescription'] ?? null,
                'order' => $order,
                'is_primary' => $order === 1,
                'mime_type' => $this->getMimeTypeFromUrl($media['MediaURL']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert data in bulk using a transaction for consistency
        try {
            DB::transaction(function () use ($listingIdMap, $bulkInsert) {
                // Delete existing media for these properties
                PropertyMedia::whereIn('property_id', $listingIdMap->values())->delete();

                // Bulk insert in chunks (1000 at a time)
                foreach (array_chunk($bulkInsert, 1000) as $chunk) {
                    PropertyMedia::insert($chunk);
                }
            });

            Log::info('Property media data inserted successfully.');
        } catch (\Exception $e) {
            Log::error('Media insert failed', ['message' => $e->getMessage()]);
        }
    }

    // Authenticate with Trestle API to get the access token
    protected function authenticate()
    {
        $response = Http::withOptions(['verify' => false])
            ->asForm()
            ->post($this->tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => $this->grantType,
                'scope' => $this->scope,
            ]);

        if ($response->successful() && isset($response['access_token'])) {
            return $response['access_token'];
        }

        Log::error('Trestle API authentication failed', ['response' => $response->body()]);
        return null;
    }

    // Fetch media data for listings using parallel requests
    protected function fetchAllMediaForListings($listingIdMap)
    {
        $client = new Client();  // Guzzle client to handle parallel requests
        $promises = [];

        // Create promises for parallel requests
        foreach ($listingIdMap->keys() as $listingId) {
            $promises[] = $client->getAsync("{$this->apiBaseUrl}/odata/Media?\$filter=" . urlencode("ResourceRecordID eq '{$listingId}'"))
                ->then(
                    function ($response) use ($listingId) {
                        return $this->processMediaResponse($response, $listingId);
                    },
                    function ($exception) use ($listingId) {
                        Log::error('Error fetching media for listing ' . $listingId, ['exception' => $exception->getMessage()]);
                        return [];
                    }
                );
        }

        // Wait for all requests to complete and gather the responses
        $responses = Promise\settle($promises)->wait();

        // Combine the results and return
        $allMedia = collect();
        foreach ($responses as $response) {
            if ($response['state'] === 'fulfilled') {
                $allMedia = $allMedia->merge($response['value']);
            }
        }

        return $allMedia;
    }

    // Process the media response
    protected function processMediaResponse($response, $listingId)
    {
        if (!$response->successful()) {
            Log::error('Failed to fetch media for listing ' . $listingId, ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        }

        $mediaData = $response->json()['value'] ?? [];
        return collect($mediaData);
    }

    // Get MIME type from URL based on file extension
    protected function getMimeTypeFromUrl($url)
    {
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            default => 'application/octet-stream',
        };
    }
}
