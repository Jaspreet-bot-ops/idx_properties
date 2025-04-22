<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Property;

class GeocodeProperties extends Command
{
    protected $signature = 'geocode:properties {--limit=100} {--start=} {--force : Force refresh all geocoding} {--debug : Show detailed error messages}';
    
    protected $description = 'Geocode property addresses using Mapbox API';
    
    // public function handle()
    // {
    //     $this->info('Starting property geocoding process...');
        
    //     $limit = $this->option('limit');
    //     $force = $this->option('force');
    //     $debug = $this->option('debug');
        
    //     try {
    //         // Get properties that need geocoding
    //         $query = Property::query()->whereNotNull('UnparsedAddress');
            
    //         // If not forcing update, only get properties without geocoding
    //         if (!$force) {
    //             $query->whereNull('Latitude')->orWhereNull('Longitude');
    //         }
            
    //         $properties = $query->take($limit)->get();
            
    //         $this->info("Found {$properties->count()} properties to geocode.");
            
    //         $processed = 0;
    //         $errors = 0;
            
    //         foreach ($properties as $property) {
    //             try {
    //                 $this->info("Geocoding property {$property->ListingKey}...");
                    
    //                 DB::beginTransaction();
                    
    //                 // Geocode the property
    //                 $this->geocodeProperty($property);
                    
    //                 // Save the property with geocoding data
    //                 $property->save();
                    
    //                 DB::commit();
    //                 $processed++;
                    
    //                 $this->info("Successfully geocoded property {$property->ListingKey}");
    //             } catch (\Exception $e) {
    //                 DB::rollBack();
    //                 $errors++;
                    
    //                 if ($debug) {
    //                     $this->error("Error geocoding property {$property->ListingKey}: " . $e->getMessage());
    //                 }
                    
    //                 Log::error("Error geocoding property {$property->ListingKey}: " . $e->getMessage(), [
    //                     'exception' => $e
    //                 ]);
    //             }
                
    //             // Add a small delay to avoid hitting API rate limits
    //             usleep(200000); // 200ms
    //         }
            
    //         $this->info("Completed geocoding properties:");
    //         $this->info("- Successfully processed: $processed");
    //         $this->info("- Errors: $errors");
            
    //         return 0;
    //     } catch (\Exception $e) {
    //         $this->error('An error occurred: ' . $e->getMessage());
    //         Log::error('Error in GeocodeProperties command: ' . $e->getMessage(), ['exception' => $e]);
    //         return 1;
    //     }
    // }

    public function handle()
    {
        $this->info('Starting property geocoding process...');
        
        $limit = (int) $this->option('limit') ?? 1000;
        $force = $this->option('force');
        $debug = $this->option('debug');
        $startId = (int) $this->option('start');
        
        $totalProcessed = 0;
        $totalErrors = 0;
        
        try {
            do {
                // Fetch a batch
                $query = Property::query()->whereNotNull('UnparsedAddress');
                
                // Add the startId condition if provided
                if ($startId > 0) {
                    $query->where('id', '>=', $startId);
                    // Only apply startId for the first batch
                    $startId = 0;
                }
                
                if (!$force) {
                    $query->where(function ($q) {
                        $q->whereNull('Latitude')->orWhereNull('Longitude');
                    });
                }
                
                $properties = $query->take($limit)->get();
                
                if ($properties->isEmpty()) {
                    break;
                }
                
                $updates = [];
                
                foreach ($properties as $property) {
                    try {
                        $geocodeData = $this->getGeocodeData($property);
                        
                        if ($geocodeData) {
                            $updates[] = [
                                'id' => $property->id,
                                'Latitude' => $geocodeData['lat'],
                                'Longitude' => $geocodeData['lng'],
                                'Country' => $geocodeData['country'],
                            ];
                            $totalProcessed++;
                            
                            if ($debug) {
                                $this->info("Geocoded: {$property->ListingKey}");
                            }
                        }
                    } catch (\Exception $e) {
                        $totalErrors++;
                        
                        if ($debug) {
                            $this->error("Error geocoding {$property->ListingKey}: " . $e->getMessage());
                        }
                        
                        Log::error("Error geocoding {$property->ListingKey}: " . $e->getMessage(), [
                            'exception' => $e
                        ]);
                    }
                }
                
                // Bulk update
                foreach ($updates as $update) {
                    Property::where('id', $update['id'])->update([
                        'Latitude' => $update['Latitude'],
                        'Longitude' => $update['Longitude'],
                        'Country' => $update['Country'],
                    ]);
                }
            } while ($properties->count() === $limit);
            
            $this->info("Geocoding completed:");
            $this->info("- Successfully processed: {$totalProcessed}");
            $this->info("- Errors: {$totalErrors}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Fatal error in geocoding command: ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }
    }
    
    
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
