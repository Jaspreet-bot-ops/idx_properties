<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyDetail;
use App\Models\PropertyMedia;
use App\Models\PropertySchool;
use App\Models\PropertyFinancialDetail;
use Illuminate\Support\Facades\Http;

class ProcessTrestleProperties implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $properties;
    protected $force;
    protected $debug;
    protected $chunkNumber;
    protected $totalChunks;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $properties, bool $force = false, bool $debug = false, int $chunkNumber = 1, int $totalChunks = 1)
    {
        $this->properties = $properties;
        $this->force = $force;
        $this->debug = $debug;
        $this->chunkNumber = $chunkNumber;
        $this->totalChunks = $totalChunks;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $processed = 0;
        $errors = 0;
        $count = count($this->properties);

        Log::info("Starting to process chunk {$this->chunkNumber}/{$this->totalChunks} with {$count} properties");

        foreach ($this->properties as $propertyData) {
            try {
                DB::beginTransaction();

                $listingKey = $propertyData['ListingKey'] ?? 'unknown';

                // Check if property already exists
                $property = Property::where('ListingKey', $listingKey)->first();
                $exists = $property !== null;

                // If property exists and we're not forcing an update, skip it
                if ($exists && !$this->force) {
                    DB::rollBack();
                    continue;
                }

                // If property doesn't exist, create a new one
                if (!$exists) {
                    $property = new Property();
                }

                // Map property data to model
                $this->mapPropertyData($property, $propertyData);

                if ($property->UnparsedAddress) {
                    // Call the geocoding function (Mapbox)
                    $this->geocodeProperty($property);
                }

                $property->save();

                $this->savePropertyDetails($property, $propertyData);
                $this->savePropertyAmenities($property, $propertyData);
                $this->savePropertyMedia($property, $propertyData['Media'] ?? []);
                $this->savePropertySchools($property, $propertyData);
                $this->savePropertyFinancialDetails($property, $propertyData);

                DB::commit();
                $processed++;

                Log::info("Successfully processed property: {$listingKey}");
            } catch (\Exception $e) {
                DB::rollBack();
                $errors++;

                Log::error('Error processing property: ' . $e->getMessage(), [
                    'property' => $propertyData['ListingKey'] ?? 'unknown',
                    'exception' => $e
                ]);
            }
        }

        Log::info("Completed processing chunk {$this->chunkNumber}/{$this->totalChunks}: {$processed} processed, {$errors} errors");
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
            $mediaModel->description = $media['MediaDescription'] ?? null;
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

    protected function geocodeProperty($property)
    {

        Log::info("Starting geocoding for property", [
            'property_id' => $property->id,
            'address' => $property->UnparsedAddress
        ]);
        
        // Ensure we have an address to geocode
        if (!$property->UnparsedAddress) {
            Log::warning("No address to geocode for property", ['property_id' => $property->id]);
            return;
        }
    
        // Make the API call to Mapbox Geocoding API
        $address = urlencode($property->UnparsedAddress);
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json?access_token=pk.eyJ1IjoiamFzcC1yZWV0IiwiYSI6ImNtOWxiaXluczAyeHUybHIxc2sycHVsNjQifQ.NW350JyVU-z-cMkzgdCrNw";
    
        try {
            $response = Http::get($url);
            $data = $response->json();
            
            Log::info("Mapbox API response", [
                'status' => $response->status(),
                'has_features' => isset($data['features']) && !empty($data['features']),
                'feature_count' => isset($data['features']) ? count($data['features']) : 0
            ]);
    
            // Check if we got a valid response with results
            if (isset($data['features'][0])) {
                $location = $data['features'][0]['geometry']['coordinates'];
                $country = null;
    
                // Extract country from the address components
                if (isset($data['features'][0]['context'])) {
                    foreach ($data['features'][0]['context'] as $component) {
                        if (isset($component['id']) && strpos($component['id'], 'country') !== false) {
                            $country = $component['text'];
                            break;
                        }
                    }
                }
    
                // Update property with geocode data
                $property->Latitude = $location[1]; // Latitude
                $property->Longitude = $location[0]; // Longitude
                $property->Country = $country;
                
                Log::info("Geocoded property successfully", [
                    'property_id' => $property->id,
                    'latitude' => $property->Latitude,
                    'longitude' => $property->Longitude,
                    'country' => $property->Country
                ]);
                
                // Verify the property object has the values set
                Log::info("Property object after geocoding", [
                    'property_id' => $property->id,
                    'latitude' => $property->Latitude,
                    'longitude' => $property->Longitude,
                    'country' => $property->Country
                ]);
            } else {
                Log::warning("No features found in Mapbox response for address", [
                    'property_id' => $property->id,
                    'address' => $property->UnparsedAddress
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception during geocoding", [
                'property_id' => $property->id,
                'address' => $property->UnparsedAddress,
                'exception' => $e->getMessage()
            ]);
        }
    }
}