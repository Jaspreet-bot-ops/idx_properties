<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\PropertyMedia;

class FetchPropertyMedia extends Command
{
    protected $signature = 'fetch:property-media {--limit=1000} {--chunk=1000} {--force} {--debug}';
    protected $description = 'Fetch and save property media from Trestle API in chunks';

    protected $tokenUrl = 'https://api-trestle.corelogic.com/trestle/oidc/connect/token';
    protected $apiBaseUrl = 'https://api-trestle.corelogic.com/trestle';
    protected $accessToken;

    public function handle()
    {
        $this->info('🔄 Starting media fetch from Trestle API...');

        $this->accessToken = $this->authenticate();
        if (!$this->accessToken) {
            $this->error('❌ Failed to authenticate with Trestle API.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');
        $debug = $this->option('debug');

        $query = Property::query()
            ->whereNotNull('ListingId')
            ->when(!$force, fn($q) => $q->whereDoesntHave('media'))
            ->limit($limit);

        $totalProcessed = 0;
        $totalErrors = 0;

        $query->chunk($chunkSize, function ($properties) use (&$totalProcessed, &$totalErrors, $debug) {
            $listingIdMap = $properties->pluck('id', 'ListingId');

            if ($listingIdMap->isEmpty()) return;

            if ($debug) {
                $this->info("📦 Processing " . count($listingIdMap) . " properties...");
            }

            $mediaData = $this->fetchAllMediaForListings($listingIdMap);
            $bulkInsert = [];
            $orderCount = [];

            foreach ($mediaData as $media) {
                $listingId = $media['ResourceRecordID'] ?? null;
                $propertyId = $listingIdMap[$listingId] ?? null;

                if (!$propertyId || empty($media['MediaURL'])) {
                    if ($debug) {
                        $this->warn("⚠️ Skipping media (missing propertyId or URL): $listingId");
                    }
                    continue;
                }

                $order = isset($orderCount[$propertyId]) ? ++$orderCount[$propertyId] : ($orderCount[$propertyId] = 1);

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

            try {
                DB::transaction(function () use ($listingIdMap, $bulkInsert) {
                    PropertyMedia::whereIn('property_id', $listingIdMap->values())->delete();
                    if (!empty($bulkInsert)) {
                        PropertyMedia::insert($bulkInsert);
                    }
                });

                $totalProcessed += count($listingIdMap);
                $this->info("✅ Processed chunk of " . count($listingIdMap));
            } catch (\Exception $e) {
                $totalErrors += count($listingIdMap);
                Log::error('❌ Media insert failed', ['message' => $e->getMessage()]);
                $this->error('Insert error: ' . $e->getMessage());
            }
        });

        $this->info("🏁 Done. Total processed: {$totalProcessed}, Errors: {$totalErrors}");
        return 0;
    }

    protected function authenticate()
    {
        $response = Http::withOptions(['verify' => false])
            ->asForm()
            ->post($this->tokenUrl, [
                'client_id' => config('services.trestle.client_id'),
                'client_secret' => config('services.trestle.client_secret'),
                'grant_type' => 'client_credentials',
                'scope' => 'api',
            ]);

        if ($response->successful() && isset($response['access_token'])) {
            return $response['access_token'];
        }

        Log::error('Trestle API authentication failed', ['response' => $response->body()]);
        return null;
    }

    protected function fetchAllMediaForListings($listingIdMap)
    {
        $allMedia = collect();

        foreach ($listingIdMap->keys() as $listingId) {
            sleep(1); // avoid hammering the API
            $media = $this->fetchMediaForListing($listingId);
            if (!empty($media)) {
                $allMedia = $allMedia->merge($media);
            }
        }

        return $allMedia;
    }

    protected function fetchMediaForListing($listingId)
    {
        $url = "{$this->apiBaseUrl}/odata/Media?\$filter=" . urlencode("ResourceRecordID eq '{$listingId}'");

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(30)
                ->withToken($this->accessToken)
                ->get($url);

            if (!$response->successful()) {
                Log::error('Media fetch failed', [
                    'listingId' => $listingId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            return $response->json()['value'] ?? [];
        } catch (\Exception $e) {
            Log::error("Exception while fetching media for listing $listingId", ['error' => $e->getMessage()]);
            return [];
        }
    }

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
