<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportCheckpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncrementalBridgeImport extends ImportPropertiesFromBridge
{
    protected $signature = 'bridge:incremental-import 
                        {--batch-size=200 : Number of properties per batch} 
                        {--batches=3 : Number of batches to process per run (default 3 = 600 records)}
                        {--reset : Reset the import and start from the beginning}';

    protected $description = 'Import properties from Bridge API in controlled increments';

    public function handle()
    {
        $this->info('Starting incremental Bridge API property import...');

        if (empty($this->apiKey)) {
            $this->error('Bridge API key is not configured. Please set BRIDGE_API_KEY in your .env file.');
            return 1;
        }

        $batchSize = $this->option('batch-size');
        $batchesToProcess = $this->option('batches');
        $reset = $this->option('reset');

        // Get or create checkpoint
        $checkpoint = $this->getOrCreateCheckpoint($reset);
        
        $nextUrl = $checkpoint->next_url;
        $totalProcessed = $checkpoint->total_processed;

        if ($nextUrl) {
            $this->info("Resuming import from checkpoint. Already processed: {$totalProcessed}");
        } else {
            $this->info("Starting a new import process.");
        }

        try {
            // Create feature categories if they don't exist
            $this->createFeatureCategories();

            $batchesCompleted = 0;
            $processedThisRun = 0;

            while ($batchesCompleted < $batchesToProcess) {
                try {
                    $result = $this->fetchPropertiesFromAPI($batchSize, $nextUrl);
                    $properties = $result['properties'];
                    $nextUrl = $result['next']; // Get the next page URL

                    if (empty($properties)) {
                        $this->info('No more properties found to import.');
                        $checkpoint->is_completed = true;
                        $checkpoint->save();
                        break;
                    }

                    $fetchedCount = count($properties);
                    $this->info("Batch " . ($batchesCompleted + 1) . ": Processing {$fetchedCount} properties");

                    $this->withProgressBar($properties, function ($propertyData) {
                        try {
                            DB::beginTransaction();
                            $this->processProperty($propertyData, true); // Always update
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->stats['failed']++;
                            Log::error("Error processing property: " . $e->getMessage(), [
                                'property' => $propertyData['ListingKey'] ?? 'unknown',
                                'exception' => $e
                            ]);
                        }
                    });

                    $this->newLine();
                    $totalProcessed += $fetchedCount;
                    $processedThisRun += $fetchedCount;
                    $batchesCompleted++;

                    $this->info("Processed in this run: {$processedThisRun}, Total overall: {$totalProcessed}");

                    // Update checkpoint after each batch
                    $checkpoint->next_url = $nextUrl;
                    $checkpoint->total_processed = $totalProcessed;
                    $checkpoint->last_run_at = now();
                    $checkpoint->save();

                    if (!$nextUrl) {
                        $this->info("No more pages to fetch. Import complete.");
                        $checkpoint->is_completed = true;
                        $checkpoint->save();
                        break;
                    }

                    // If we've completed the requested number of batches, stop
                    if ($batchesCompleted >= $batchesToProcess) {
                        $this->info("Completed {$batchesCompleted} batches. Stopping for now.");
                        break;
                    }

                    sleep(2); // prevent rate limiting
                } catch (\Exception $e) {
                    $this->error("Error in batch processing: " . $e->getMessage());
                    Log::error("Bridge API batch error: " . $e->getMessage(), [
                        'exception' => $e,
                        'next_url' => $nextUrl
                    ]);
                    
                    // Save the current state before exiting
                    $checkpoint->next_url = $nextUrl;
                    $checkpoint->total_processed = $totalProcessed;
                    $checkpoint->last_run_at = now();
                    $checkpoint->save();
                    
                    return 1;
                }
            }
            
            $this->newLine(2);
            $this->displayStats();

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("Bridge API import error: " . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // Save the current state before exiting
            if (isset($nextUrl) && isset($totalProcessed)) {
                $checkpoint->next_url = $nextUrl;
                $checkpoint->total_processed = $totalProcessed;
                $checkpoint->last_run_at = now();
                $checkpoint->save();
            }
            
            return 1;
        }
    }

    /**
     * Get or create a checkpoint for this import
     */
    protected function getOrCreateCheckpoint($reset = false)
    {
        $importType = 'bridge_properties';
        
        if ($reset) {
            // Delete existing checkpoint if reset is requested
            ImportCheckpoint::where('import_type', $importType)->delete();
        }
        
        $checkpoint = ImportCheckpoint::firstOrCreate(
            ['import_type' => $importType],
            [
                'next_url' => null,
                'total_processed' => 0,
                'last_run_at' => now(),
                'is_completed' => false
            ]
        );
        
        if ($reset) {
            $checkpoint->next_url = null;
            $checkpoint->total_processed = 0;
            $checkpoint->is_completed = false;
            $checkpoint->save();
        }
        
        return $checkpoint;
    }
}
