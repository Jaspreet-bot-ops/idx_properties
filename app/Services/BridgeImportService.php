<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\BridgeProperty;
use App\Models\BridgePropertyDetail;
use App\Models\BridgePropertyMedia;
use App\Models\BridgePropertyBooleanFeature;
use App\Models\BridgePropertyTaxInformation;
use App\Models\BridgePropertyFinancialDetails;
use App\Models\BridgePropertyLeaseInformation;
use App\Models\BridgeFeature;
use App\Models\BridgeFeatureCategory;
use App\Models\BridgeAgent;
use App\Models\BridgeOffice;
use App\Models\BridgeSchool;
use Carbon\Carbon;


class BridgeImportService
{

    protected $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'media_imported' => 0,
        'features_imported' => 0,
        'agents_imported' => 0,
        'offices_imported' => 0,
        'schools_imported' => 0,
    ];

    /**
     * Feature categories mapping
     */
    protected $featureCategories = [
        'AccessibilityFeatures' => 'AccessibilityFeatures',
        'AssociationAmenities' => 'AssociationAmenities',
        'Appliances' => 'Appliances',
        'ArchitecturalStyle' => 'Architectural Style',
        'BuildingFeatures' => 'BuildingFeatures',
        'CommunityFeatures' => 'Community Features',
        'Cooling' => 'Cooling',
        'DoorFeatures' => 'DoorFeatures',
        'ExteriorFeatures' => 'Exterior Features',
        'FireplaceFeatures' => 'FireplaceFeatures',
        'Flooring' => 'Flooring',
        'HorseAmenities' => 'HorseAmenities',
        'Heating' => 'Heating',
        'InteriorFeatures' => 'Interior Features',
        'LaundryFeatures' => 'Laundry Features',
        'LotFeatures' => 'Lot Features',
        'ParkingFeatures' => 'Parking Features',
        'PatioAndPorchFeatures' => 'Patio and Porch Features',
        'RoofFeatures' => 'Roof',
        'SecurityFeatures' => 'Security Features',
        'StructureType' => 'Structure Type',
        'WaterSource' => 'Water Source',
        'Sewer' => 'Sewer',
        'Utilities' => 'Utilities',
        'WindowFeatures' => 'Window Features',
        'View' => 'View',
        'Fencing' => 'Fencing',
        'WaterfrontFeatures' => 'Waterfront Features',
        'ConstructionMaterials' => 'Construction Materials',
        'PoolFeatures' => 'Pool Features',
        'SpaFeatures' => 'Spa Features',
    ];

    /**
     * Boolean features to extract
     */
    protected $booleanFeatures = [
        'CoolingYN',
        'HeatingYN',
        'GarageYN',
        'AttachedGarageYN',
        'CarportYN',
        'OpenParkingYN',
        'WaterfrontYN',
        'ViewYN',
        'PoolPrivateYN',
        'SpaYN',
        'FireplaceYN',
        'HorseYN',
        'NewConstructionYN',
        'AssociationYN',
        'SignOnPropertyYN',
        'HomeWarrantyYN',
        'LeaseConsideredYN',
        'LandLeaseYN',
        'LeaseAssignableYN',
        'LeaseRenewalOptionYN',
        'SeniorCommunityYN',
        'PropertyAttachedYN',
        'ElectricOnPropertyYN',
        'HabitableResidenceYN',
        'AdditionalParcelsYN',
        'IDXParticipationYN',
        'InternetAddressDisplayYN',
        'InternetEntireListingDisplayYN',
        'InternetConsumerCommentYN'
    ];


    public function fetchPropertiesFromAPI($limit, $nextUrl = null)
    {
        $apiKey = config('services.bridge.key');

        if (strpos($apiKey, 'BRIDGE_API_KEY=') === 0) {
            $apiKey = substr($apiKey, 15);
        }

        // If it's the first request
        if (!$nextUrl) {
            $url = "https://api.bridgedataoutput.com/api/v2/OData/miamire/Property";
            $params = [
                'access_token' => 'f091fc0d25a293957350aa6a022ea4fb',
                '$top' => $limit,
            ];
            $response = Http::get($url, $params);
        } else {
            // nextUrl already contains token and top, call it as-is
            $response = Http::get($nextUrl);
        }

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        $data = $response->json();

        return [
            'properties' => $data['value'] ?? [],
            'next' => $data['@odata.nextLink'] ?? null,
        ];
    }

    protected function processProperty($propertyData, $update)
    {
        try {
            $listingKey = $propertyData['ListingKey'] ?? null;

            if (!$listingKey) {
                $this->stats['failed']++;
                $this->error("Missing ListingKey for property");
                return;
            }

            // Check if property already exists
            $property = BridgeProperty::with('details')->where('listing_key', $listingKey)->first();

            if ($property && !$update) {
                $this->stats['skipped']++;
                return;
            }

            // Process agents and offices first to get their IDs
            $agentOfficeIds = $this->processAgentsAndOffices($propertyData);

            // Process schools to get their IDs
            $schoolIds = $this->processSchools($propertyData);

            // Prepare property data
            $propertyAttributes = $this->mapPropertyAttributes($propertyData, $agentOfficeIds, $schoolIds);

            // Log the property attributes for debugging
            Log::info("Property attributes for $listingKey", $propertyAttributes);

            if ($property) {
                // Update existing property
                $property->update($propertyAttributes);
                if ($property->details) {
                    $property->details->update([
                        'rooms_description' => $propertyData['RoomsDescription'] ?? null,
                        'bedroom_description' => $propertyData['BedroomDescription'] ?? null,
                        'master_bathroom_description' => $propertyData['MasterBathroomDescription'] ?? null,
                        'master_bath_features' => $propertyData['MasterBathFeatures'] ?? null,
                        'dining_description' => $propertyData['DiningDescription'] ?? null,
                        'rooms_total' => $propertyData['RoomsTotal'] ?? null,
                    ]);
                    $this->stats['updated']++;
                    $this->info("Updated property: $listingKey");
                } else {
                    // Create new property
                    $property = BridgeProperty::create($propertyAttributes);
                    BridgePropertyDetail::create([
                        'property_id' => $property->id,
                        'rooms_description' => $propertyData['RoomsDescription'] ?? null,
                        'bedroom_description' => $propertyData['BedroomDescription'] ?? null,
                        'master_bathroom_description' => $propertyData['MasterBathroomDescription'] ?? null,
                        'master_bath_features' => $propertyData['MasterBathFeatures'] ?? null,
                        'dining_description' => $propertyData['DiningDescription'] ?? null,
                        'rooms_total' => $propertyData['RoomsTotal'] ?? null,
                    ]);
                }
                $this->stats['created']++;
                $this->info("Created property: $listingKey");
            }

            // Process property details
            $this->processPropertyDetails($property, $propertyData);

            // Process property media
            $this->processPropertyMedia($property, $propertyData);

            // Process property features
            $this->processPropertyFeatures($property, $propertyData);

            // Process boolean features
            $this->processPropertyBooleanFeatures($property, $propertyData);

            // Process tax information
            $this->processPropertyTaxInformation($property, $propertyData);

            // Process financial data
            $this->processPropertyFinancialData($property, $propertyData);

            // Process lease information
            $this->processPropertyLeaseInformation($property, $propertyData);
        } catch (\Exception $e) {
            $this->stats['failed']++;
            $this->error("Error processing property {$listingKey}: " . $e->getMessage());
            Log::error("Error processing property", [
                'property' => $listingKey ?? 'unknown',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function mapPropertyAttributes($propertyData, $agentOfficeIds, $schoolIds)
    {
        return [
            'listing_key' => $propertyData['ListingKey'] ?? null,
            'listing_id' => $propertyData['ListingId'] ?? null,
            'mls_status' => $propertyData['MlsStatus'] ?? null,
            'standard_status' => $propertyData['StandardStatus'] ?? null,
            'property_type' => $propertyData['PropertyType'] ?? null,
            'property_sub_type' => $propertyData['PropertySubType'] ?? null,

            // Address information
            'street_number' => $propertyData['StreetNumber'] ?? null,
            'street_number_numeric' => $propertyData['StreetNumberNumeric'] ?? null,
            'street_dir_prefix' => $propertyData['StreetDirPrefix'] ?? null,
            'street_name' => $propertyData['StreetName'] ?? null,
            'street_suffix' => $propertyData['StreetSuffix'] ?? null,
            'street_dir_suffix' => $propertyData['StreetDirSuffix'] ?? null,
            'unit_number' => $propertyData['UnitNumber'] ?? null,
            'city' => $propertyData['City'] ?? null,
            'state_or_province' => $propertyData['StateOrProvince'] ?? null,
            'postal_code' => $propertyData['PostalCode'] ?? null,
            'postal_code_plus4' => $propertyData['PostalCodePlus4'] ?? null,
            'county_or_parish' => $propertyData['CountyOrParish'] ?? null,
            'country' => $propertyData['Country'] ?? null,
            'country_region' => $propertyData['CountryRegion'] ?? null,
            'unparsed_address' => $propertyData['UnparsedAddress'] ?? null,

            // Listing details
            'list_price' => $propertyData['ListPrice'] ?? null,
            'original_list_price' => $propertyData['OriginalListPrice'] ?? null,
            'close_price' => $propertyData['ClosePrice'] ?? null,
            'days_on_market' => $propertyData['DaysOnMarket'] ?? null,
            'listing_contract_date' => $propertyData['ListingContractDate'] ?? null,
            'on_market_date' => $propertyData['OnMarketDate'] ?? null,
            'off_market_date' => $propertyData['OffMarketDate'] ?? null,
            'pending_timestamp' => $propertyData['PendingTimestamp'] ?? null,
            'close_date' => $propertyData['CloseDate'] ?? null,
            'close_date' => $propertyData['ContractStatusChangeDate'] ?? null,
            'listing_agreement' => $propertyData['ListingAgreement'] ?? null,
            'contingency' => $propertyData['Contingency'] ?? null,

            // Property specifications
            'bedrooms_total' => $propertyData['BedroomsTotal'] ?? null,
            'bathrooms_total_decimal' => $propertyData['BathroomsTotalDecimal'] ?? null,
            'bathrooms_full' => $propertyData['BathroomsFull'] ?? null,
            'bathrooms_half' => $propertyData['BathroomsHalf'] ?? null,
            'bathrooms_total_integer' => $propertyData['BathroomsTotalInteger'] ?? null,
            'living_area' => $propertyData['LivingArea'] ?? null,
            'living_area_units' => $propertyData['LivingAreaUnits'] ?? null,
            'lot_size_square_feet' => $propertyData['LotSizeSquareFeet'] ?? null,
            'lot_size_acres' => $propertyData['LotSizeAcres'] ?? null,
            'lot_size_units' => $propertyData['LotSizeUnits'] ?? null,
            'lot_size_dimensions' => $propertyData['LotSizeDimensions'] ?? null,
            'year_built' => $propertyData['YearBuilt'] ?? null,
            'year_built_details' => $propertyData['YearBuiltDetails'] ?? null,
            'stories_total' => $propertyData['StoriesTotal'] ?? null,

            // Parking information
            'garage_yn' => $propertyData['GarageYN'] ?? null,
            'attached_garage_yn' => $propertyData['AttachedGarageYN'] ?? null,
            'garage_spaces' => $propertyData['GarageSpaces'] ?? null,
            'carport_spaces' => $propertyData['CarportSpaces'] ?? null,
            'carport_yn' => $propertyData['CarportYN'] ?? null,
            'open_parking_yn' => $propertyData['OpenParkingYN'] ?? null,
            'covered_spaces' => $propertyData['CoveredSpaces'] ?? null,
            'parking_total' => $propertyData['ParkingTotal'] ?? null,

            // Pool/Spa information
            'pool_private_yn' => $propertyData['PoolPrivateYN'] ?? null,
            'spa_yn' => $propertyData['SpaYN'] ?? null,

            // Financial information
            'tax_annual_amount' => $propertyData['TaxAnnualAmount'] ?? null,
            'tax_year' => $propertyData['TaxYear'] ?? null,
            'tax_lot' => $propertyData['TaxLot'] ?? null,
            'parcel_number' => $propertyData['ParcelNumber'] ?? null,
            'association_fee' => $propertyData['AssociationFee'] ?? null,
            'association_fee_frequency' => $propertyData['AssociationFeeFrequency'] ?? null,

            // Geographic coordinates
            'latitude' => $propertyData['Latitude'] ?? null,
            'longitude' => $propertyData['Longitude'] ?? null,

            // Virtual tour
            'virtual_tour_url_unbranded' => $propertyData['VirtualTourURLUnbranded'] ?? null,

            // Public remarks
            'public_remarks' => $propertyData['PublicRemarks'] ?? null,
            'private_remarks' => $propertyData['PrivateRemarks'] ?? null,
            'syndication_remarks' => $propertyData['SyndicationRemarks'] ?? null,

            // Timestamps from API
            'original_entry_timestamp' => $propertyData['OriginalEntryTimestamp'] ?? null,
            'modification_timestamp' => $propertyData['ModificationTimestamp'] ?? null,
            'price_change_timestamp' => $propertyData['PriceChangeTimestamp'] ?? null,
            'status_change_timestamp' => $propertyData['StatusChangeTimestamp'] ?? null,
            'major_change_timestamp' => $propertyData['MajorChangeTimestamp'] ?? null,
            'photos_change_timestamp' => $propertyData['PhotosChangeTimestamp'] ?? null,
            'bridge_modification_timestamp' => $propertyData['BridgeModificationTimestamp'] ?? null,

            // Flags
            'new_construction_yn' => $propertyData['NewConstructionYN'] ?? null,
            'furnished' => $propertyData['Furnished'] ?? null,
            'waterfront_yn' => $propertyData['WaterfrontYN'] ?? null,
            'view_yn' => $propertyData['ViewYN'] ?? null,
            'horse_yn' => $propertyData['HorseYN'] ?? null,

            // Metadata
            'source_system_key' => $propertyData['SourceSystemKey'] ?? null,
            'originating_system_key' => $propertyData['OriginatingSystemKey'] ?? null,
            'originating_system_name' => $propertyData['OriginatingSystemName'] ?? null,
            'originating_system_id' => $propertyData['OriginatingSystemID'] ?? null,

            // Relationships (foreign keys)
            'list_agent_id' => $agentOfficeIds['list_agent_id'] ?? null,
            'co_list_agent_id' => $agentOfficeIds['co_list_agent_id'] ?? null,
            'buyer_agent_id' => $agentOfficeIds['buyer_agent_id'] ?? null,
            'co_buyer_agent_id' => $agentOfficeIds['co_buyer_agent_id'] ?? null,
            'list_office_id' => $agentOfficeIds['list_office_id'] ?? null,
            'co_list_office_id' => $agentOfficeIds['co_list_office_id'] ?? null,
            'buyer_office_id' => $agentOfficeIds['buyer_office_id'] ?? null,
            'co_buyer_office_id' => $agentOfficeIds['co_buyer_office_id'] ?? null,
            'elementary_school_id' => $schoolIds['elementary_school_id'] ?? null,
            'middle_school_id' => $schoolIds['middle_school_id'] ?? null,
            'high_school_id' => $schoolIds['high_school_id'] ?? null,

            // Timestamps
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function processAgentsAndOffices($propertyData)
    {
        $agentOfficeIds = [];

        // Process listing agent and office
        $listOffice = null;
        if (!empty($propertyData['ListOfficeKey'])) {
            $listOffice = $this->findOrCreateOffice($propertyData, 'List');
            $agentOfficeIds['list_office_id'] = $listOffice->id;
        }

        if (!empty($propertyData['ListAgentKey'])) {
            $listAgent = $this->findOrCreateAgent($propertyData, 'List', $listOffice);
            $agentOfficeIds['list_agent_id'] = $listAgent->id;
        }

        // Process co-listing agent and office
        $coListOffice = null;
        if (!empty($propertyData['CoListOfficeKey'])) {
            $coListOffice = $this->findOrCreateOffice($propertyData, 'CoList');
            $agentOfficeIds['co_list_office_id'] = $coListOffice->id;
        }

        if (!empty($propertyData['CoListAgentKey'])) {
            $coListAgent = $this->findOrCreateAgent($propertyData, 'CoList', $coListOffice);
            $agentOfficeIds['co_list_agent_id'] = $coListAgent->id;
        }

        // Process buyer agent and office
        $buyerOffice = null;
        if (!empty($propertyData['BuyerOfficeKey']) || !empty($propertyData['BuyerOfficeMlsId'])) {
            $buyerOffice = $this->findOrCreateOffice($propertyData, 'Buyer');
            if ($buyerOffice) {
                $agentOfficeIds['buyer_office_id'] = $buyerOffice->id;
            }
        }

        if (!empty($propertyData['BuyerAgentKey'])) {
            $buyerAgent = $this->findOrCreateAgent($propertyData, 'Buyer', $buyerOffice);
            $agentOfficeIds['buyer_agent_id'] = $buyerAgent->id;
        }

        // Process co-buyer agent and office
        $coBuyerOffice = null;
        if (!empty($propertyData['CoBuyerOfficeKey'])) {
            $coBuyerOffice = $this->findOrCreateOffice($propertyData, 'CoBuyer');
            $agentOfficeIds['co_buyer_office_id'] = $coBuyerOffice->id;
        }

        if (!empty($propertyData['CoBuyerAgentKey'])) {
            $coBuyerAgent = $this->findOrCreateAgent($propertyData, 'CoBuyer', $coBuyerOffice);
            $agentOfficeIds['co_buyer_agent_id'] = $coBuyerAgent->id;
        }

        return $agentOfficeIds;
    }

    protected function findOrCreateAgent($propertyData, $prefix, $office = null)
    {
        $agentKey = $propertyData["{$prefix}AgentKey"] ?? null;

        if (!$agentKey) {
            return null;
        }

        $agent = BridgeAgent::firstOrNew(['agent_key' => $agentKey]);

        if (!$agent->exists) {
            $agent->full_name = $propertyData["{$prefix}AgentFullName"] ??
                ($propertyData["{$prefix}AgentFirstName"] ?? '') . ' ' .
                ($propertyData["{$prefix}AgentLastName"] ?? '');
            $agent->email = $propertyData["{$prefix}AgentEmail"] ?? null;
            $agent->direct_phone = $propertyData["{$prefix}AgentDirectPhone"] ?? null;
            $agent->office_phone = $propertyData["{$prefix}AgentOfficePhone"] ?? null;
            $agent->state_license = $propertyData["{$prefix}AgentStateLicense"] ?? null;
            $agent->mls_id = $propertyData["{$prefix}AgentMlsId"] ?? null;

            // Set the office_id if an office was provided
            if ($office) {
                $agent->office_id = $office->id;
            }

            $agent->save();

            $this->stats['agents_imported']++;
        } else if ($office && !$agent->office_id) {
            // Update existing agent with office_id if it's not set
            $agent->office_id = $office->id;
            $agent->save();
        }

        return $agent;
    }

    protected function findOrCreateOffice($propertyData, $prefix)
    {
        $officeKey = $propertyData["{$prefix}OfficeKey"] ?? null;
        $officeMlsId = $propertyData["{$prefix}OfficeMlsId"] ?? null;

        if (!$officeKey && !$officeMlsId) {
            return null; // No identifier to search
        }

        // Use OfficeKey if available, otherwise use MlsId as fallback
        $office = null;

        if ($officeKey) {
            $office = BridgeOffice::firstOrNew(['office_key' => $officeKey]);
        } elseif ($officeMlsId) {
            $office = BridgeOffice::firstOrNew(['mls_id' => $officeMlsId]);
        }

        if (!$office->exists) {
            $office->office_key = $officeKey; // still set key even if it's null
            $office->mls_id = $officeMlsId;
            $office->name = $propertyData["{$prefix}OfficeName"] ?? null;
            $office->phone = $propertyData["{$prefix}OfficePhone"] ?? null;
            $office->fax = $propertyData["{$prefix}OfficeFax"] ?? null;
            $office->email = $propertyData["{$prefix}OfficeEmail"] ?? null;
            $office->website_url = $propertyData["{$prefix}OfficeWebsite"] ?? null;
            $office->save();

            $this->stats['offices_imported']++;
        }

        return $office;
    }

    protected function processSchools($propertyData)
    {
        $schoolIds = [];

        // Elementary School
        if (!empty($propertyData['ElementarySchool'])) {
            $elementarySchool = $this->findOrCreateSchool($propertyData['ElementarySchool'], 'Elementary');
            $schoolIds['elementary_school_id'] = $elementarySchool->id;
        }

        // Middle School
        if (!empty($propertyData['MiddleOrJuniorSchool'])) {
            $middleSchool = $this->findOrCreateSchool($propertyData['MiddleOrJuniorSchool'], 'Middle');
            $schoolIds['middle_school_id'] = $middleSchool->id;
        }

        // High School
        if (!empty($propertyData['HighSchool'])) {
            $highSchool = $this->findOrCreateSchool($propertyData['HighSchool'], 'High');
            $schoolIds['high_school_id'] = $highSchool->id;
        }

        return $schoolIds;
    }

    protected function findOrCreateSchool($schoolName, $schoolType)
    {
        if (empty($schoolName)) {
            return null;
        }

        // Look for a school with the same name and type
        $school = BridgeSchool::where('name', $schoolName)->first();

        if (!$school) {
            // Check if any school with this type already exists
            $existingSchoolWithType = BridgeSchool::where('type', $schoolType)->first();

            if ($existingSchoolWithType) {
                // If a school with this type already exists, use a composite type
                // For example: "Elementary-1", "Elementary-2", etc.
                $count = BridgeSchool::where('type', 'like', $schoolType . '-%')->count();
                $uniqueType = $schoolType . '-' . ($count + 1);

                $school = new BridgeSchool();
                $school->name = $schoolName;
                $school->type = $uniqueType;
                $school->district = null;
                $school->city = null;
                $school->state = null;
                $school->save();
            } else {
                // If no school with this type exists, use the original type
                $school = new BridgeSchool();
                $school->name = $schoolName;
                $school->type = $schoolType;
                $school->district = null;
                $school->city = null;
                $school->state = null;
                $school->save();
            }

            $this->stats['schools_imported']++;
        }

        return $school;
    }

    protected function processPropertyDetails($property, $propertyData)
    {
        Log::info($property->id);

        $details = BridgePropertyDetail::firstOrNew(['property_id' => $property->id]);

        // Helper function to safely convert any value to a string
        $safeToString = function ($value) {
            if (is_array($value)) {
                return json_encode($value);
            } elseif (is_null($value)) {
                return null;
            } else {
                return (string)$value;
            }
        };

        // Process all fields that might be arrays
        $processedData = [];

        // Map all fields from the API to our database columns
        $fieldMappings = [
            'building_name' => 'BuildingName',
            'builder_model' => 'BuilderModel',
            'buisness_name' => 'BusinessName',
            'buisness_type' => 'BusinessType',
            'subdivision_name' => 'SubdivisionName',
            'building_area_total' => 'BuildingAreaTotal',
            'building_area_units' => 'BuildingAreaUnits',
            'building_area_source' => 'BuildingAreaSource',
            'common_walls' => 'CommonWalls',
            'directions' => "Directions",
            'direction_faces' => 'DirectionFaces',
            'property_condition' => 'PropertyCondition',
            'zoning' => 'Zoning',
            'tax_legal_description' => 'TaxLegalDescription',
            'current_financing' => 'CurrentFinancing',
            'possession' => 'Possession',
            'showing_instructions' => 'ShowingInstructions',
            'showing_contact_type' => 'ShowingContactType',
            'availability_date' => 'AvailabilityDate',
            'development_status' => 'DevelopmentStatus',
            'ownership_type' => 'OwnershipType',
            'special_listing_conditions' => 'SpecialListingConditions',
            'listing_terms' => 'ListingTerms',
            'listing_service' => 'ListingService',
            'sign_on_property_yn' => 'SignOnPropertyYN',
            'association_yn' => 'AssociationYN',
            'disclosures' => 'Disclosures',
            'home_warranty_yn' => 'HomeWarrantyYN',

            'rooms_description' => 'RoomLivingRoomFeatures',
            'bedroom_description' => 'RoomBedroomFeatures',
            'master_bathroom_description' => 'RoomMasterBathroomFeatures',
            'master_bath_features' => 'RoomMasterBathroomFeatures',
            'dining_description' => 'RoomDiningRoomFeatures',
            'rooms_total' => 'RoomsTotal',

            // MIAMIRE specific fields
            'miamire_adjusted_area_sf' => 'MIAMIRE_AdjustedAreaSF',
            'miamire_lp_amt_sq_ft' => 'MIAMIRE_LPAmtSqFt',
            'miamire_ratio_current_price_by_sqft' => 'MIAMIRE_RATIO_CurrentPrice_By_SQFT',
            'miamire_area' => 'MIAMIRE_Area',
            'miamire_style' => 'MIAMIRE_Style',
            'miamire_internet_remarks' => 'MIAMIRE_InternetRemarks',
            'miamire_pool_yn' => 'MIAMIRE_PoolYN',
            'miamire_pool_dimensions' => 'MIAMIRE_PoolDimensions',
            'miamire_membership_purch_rqd_yn' => 'MIAMIRE_MembershipPurchRqdYN',
            'miamire_special_assessment_yn' => 'MIAMIRE_SpecialAssessmentYN',
            'miamire_type_of_association' => 'MIAMIRE_TypeofAssociation',
            'miamire_type_of_governing_bodies' => 'MIAMIRE_TypeofGoverningBodies',
            'miamire_restrictions' => 'MIAMIRE_Restrictions',
            'miamire_subdivision_information' => 'MIAMIRE_SubdivisionInformation',
            'miamire_buyer_country_of_residence' => 'MIAMIRE_BuyerCountryofResidence',
            'miamire_seller_contributions_yn' => 'MIAMIRE_SellerContributionsYN',
            'miamire_seller_contributions_amt' => 'MIAMIRE_SellerContributionsAmt',

            // Additional MIAMIRE fields
            'miamire_application_fee' => 'MIAMIRE_ApplicationFee',
            'miamire_approval_information' => 'MIAMIRE_ApprovalInformation',
            'miamire_attribution_contact' => 'MIAMIRE_AttributionContact',
            'miamire_buy_state' => 'MIAMIRE_BuyState',
            'miamire_for_lease_mls_number' => 'MIAMIRE_ForLeaseMLSNumber',
            'miamire_for_lease_yn' => 'MIAMIRE_ForLeaseYN',
            'miamire_for_sale_mls_number' => 'MIAMIRE_ForSaleMLSNumber',
            'miamire_for_sale_yn' => 'MIAMIRE_ForSaleYN',
            'miamire_global_city' => 'MIAMIRE_GlobalCity',
            'miamire_guest_house_description' => 'MIAMIRE_GuestHouseDescription',
            'miamire_length_of_rental' => 'MIAMIRE_LengthofRental',
            'miamire_maintenance_includes' => 'MIAMIRE_MaintenanceIncludes',
            'miamire_maximum_leasable_sqft' => 'MIAMIRE_MaximumLeasableSqft',
            'miamire_move_in_dollars' => 'MIAMIRE_MoveInDollars',
            'miamire_ok_to_advertise_list' => 'MIAMIRE_OkToAdvertiseList',
            'miamire_pet_fee' => 'MIAMIRE_PetFee',
            'miamire_pet_fee_desc' => 'MIAMIRE_PetFeeDesc',
            'miamire_pets_allowed_yn' => 'MIAMIRE_PetsAllowedYN',
            'miamire_rent_length_desc' => 'MIAMIRE_RentLengthDesc',
            'miamire_showing_time_flag' => 'MIAMIRE_ShowingTimeFlag',
            'miamire_temp_off_market_date' => 'MIAMIRE_TempOffMarketDate',
            'miamire_total_move_in_dollars' => 'MIAMIRE_TotalMoveInDollars',
            'miamire_type_of_business' => 'MIAMIRE_TypeofBusiness',
        ];

        // Process each field with safe conversion
        $data = ['property_id' => $property->id];

        foreach ($fieldMappings as $dbField => $apiField) {
            if (isset($propertyData[$apiField])) {
                $data[$dbField] = $safeToString($propertyData[$apiField]);
            } else {
                $data[$dbField] = null;
            }
        }

        // Special handling for boolean fields
        $booleanFields = [
            'sign_on_property_yn',
            'association_yn',
            'home_warranty_yn',
            'miamire_pool_yn',
            'miamire_membership_purch_rqd_yn',
            'miamire_special_assessment_yn',
            'miamire_seller_contributions_yn',
            'miamire_for_lease_yn',
            'miamire_for_sale_yn',
            'miamire_ok_to_advertise_list',
            'miamire_pets_allowed_yn',
            'miamire_showing_time_flag'
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        // Special handling for numeric fields
        $numericFields = [
            'building_area_total',
            'miamire_adjusted_area_sf',
            'miamire_lp_amt_sq_ft',
            'miamire_ratio_current_price_by_sqft',
            'miamire_seller_contributions_amt',
            'miamire_application_fee',
            'miamire_maximum_leasable_sqft',
            'miamire_move_in_dollars',
            'miamire_pet_fee',
            'miamire_total_move_in_dollars'
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && !is_null($data[$field])) {
                $data[$field] = (float)$data[$field];
            }
        }

        // Special handling for date fields
        $dateFields = [
            'miamire_temp_off_market_date'
        ];

        foreach ($dateFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    $data[$field] = date('Y-m-d', strtotime($data[$field]));
                } catch (\Exception $e) {
                    $data[$field] = null;
                }
            }
        }

        // Log the processed data for debugging
        Log::info("Processed property details data for property ID {$property->id}", [
            'raw_building_area_units' => $propertyData['BuildingAreaUnits'] ?? null,
            'processed_building_area_units' => $data['building_area_units'],
            'raw_property_condition' => $propertyData['PropertyCondition'] ?? null,
            'processed_property_condition' => $data['property_condition']
        ]);

        // Fill the model with processed data
        $details->fill($data);
        $details->save();
    }

    protected function processPropertyMedia($property, $propertyData)
    {
        if (empty($propertyData['Media'])) {
            return;
        }

        // Delete existing media if we're updating
        if ($property->media()->count() > 0) {
            $property->media()->delete();
        }

        foreach ($propertyData['Media'] as $index => $mediaItem) {
            if (empty($mediaItem['MediaURL'])) {
                continue;
            }

            $property->media()->create([
                'media_key' => $mediaItem['MediaKey'] ?? null,
                'media_url' => $mediaItem['MediaURL'],
                'mime_type' => $mediaItem['MimeType'] ?? 'Photo',
                'media_category' => $mediaItem['MediaCategory'] ?? null,
                'resoure_name' => $mediaItem['ResourceName'] ?? null,
                'class_name' => $mediaItem['ClassName'] ?? null,
                'short_description' => $mediaItem['ShortDescription'] ?? null,
                'order' => $mediaItem['Order'] ?? $index,
                'is_primary' => ($index === 1) ? true : false,
            ]);

            $this->stats['media_imported']++;
        }
    }

    protected function processPropertyFeatures($property, $propertyData)
    {
        // Clear existing features
        $property->features()->detach();

        foreach ($this->featureCategories as $apiField => $categoryName) {
            if (empty($propertyData[$apiField])) {
                continue;
            }

            $category = BridgeFeatureCategory::firstOrCreate(['name' => $categoryName]);

            // Handle both string and array formats
            $featureValues = is_array($propertyData[$apiField])
                ? $propertyData[$apiField]
                : explode(',', $propertyData[$apiField]);

            foreach ($featureValues as $featureValue) {
                $featureValue = trim($featureValue);
                if (empty($featureValue)) continue;

                $feature = BridgeFeature::firstOrCreate([
                    'name' => $featureValue,
                    'feature_category_id' => $category->id
                ]);

                $property->features()->attach($feature->id);
                $this->stats['features_imported']++;
            }
        }
    }

    protected function processPropertyBooleanFeatures($property, $propertyData)
    {
        // Clear existing boolean features
        $property->booleanFeatures()->delete();

        foreach ($this->booleanFeatures as $featureName) {
            if (isset($propertyData[$featureName])) {
                $property->booleanFeatures()->create([
                    'feature_name' => $featureName,
                    'value' => filter_var($propertyData[$featureName], FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
    }

    protected function processPropertyTaxInformation($property, $propertyData)
    {
        try {
            $taxInfo = BridgePropertyTaxInformation::firstOrNew(['property_id' => $property->id]);

            // Handle tax_exemptions field - convert array to JSON string if needed
            $taxExemptions = null;
            if (isset($propertyData['TaxExemptions'])) {
                if (is_array($propertyData['TaxExemptions'])) {
                    $taxExemptions = json_encode($propertyData['TaxExemptions']);
                } else {
                    $taxExemptions = $propertyData['TaxExemptions'];
                }
            }

            $taxInfo->fill([
                'property_id' => $property->id,
                'tax_exemptions' => $taxExemptions,
                'public_survey_township' => $propertyData['PublicSurveyTownship'] ?? null,
                'public_survey_range' => $propertyData['PublicSurveyRange'] ?? null,
                'public_survey_section' => $propertyData['PublicSurveySection'] ?? null,
            ]);

            $taxInfo->save();

            return true;
        } catch (\Exception $e) {
            $this->error("Error in processPropertyTaxInformation: " . $e->getMessage());
            Log::error("Error in processPropertyTaxInformation", [
                'property_id' => $property->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function processPropertyFinancialData($property, $propertyData)
    {
        $financialData = BridgePropertyFinancialDetails::firstOrNew(['property_id' => $property->id]);

        // Create a copy of the data to modify
        $processedData = [];

        // Fields that might be arrays and need to be converted to JSON
        $arrayFields = ['OperatingExpenseIncludes'];

        // Process fields that might be arrays
        foreach ($arrayFields as $field) {
            if (isset($propertyData[$field])) {
                $processedData[$field] = is_array($propertyData[$field])
                    ? json_encode($propertyData[$field])
                    : $propertyData[$field];
            } else {
                $processedData[$field] = null;
            }
        }

        $financialData->fill([
            'property_id' => $property->id,
            'gross_income' => $propertyData['GrossIncome'] ?? null,
            'gross_scheduled_income' => $propertyData['GrossScheduledIncome'] ?? null,
            'net_operating_income' => $propertyData['NetOperatingIncome'] ?? null,
            'total_actual_rent' => $propertyData['TotalActualRent'] ?? null,
            'operating_expense' => $propertyData['OperatingExpense'] ?? null,
            'operating_expense_includes' => $processedData['OperatingExpenseIncludes'] ?? null,
            'insurance_expense' => $propertyData['InsuranceExpense'] ?? null,
            'maintenance_expense' => $propertyData['MaintenanceExpense'] ?? null,
            'manager_expense' => $propertyData['ManagerExpense'] ?? null,
            'new_taxes_expense' => $propertyData['NewTaxesExpense'] ?? null,
            'other_expense' => $propertyData['OtherExpense'] ?? null,
            'supplies_expense' => $propertyData['SuppliesExpense'] ?? null,
            'trash_expense' => $propertyData['TrashExpense'] ?? null,
        ]);

        $financialData->save();
    }

    protected function processPropertyLeaseInformation($property, $propertyData)
    {
        $leaseInfo = BridgePropertyLeaseInformation::firstOrNew(['property_id' => $property->id]);

        // Create a copy of the data to modify
        $processedData = [];

        // Fields that might be arrays and need to be converted to JSON
        $arrayFields = [
            'LeaseTerm',
            'ExistingLeaseType',
            'MiamireLengthOfRental',
            'MiamirePetFeeDesc',
            'MiamireRentLengthDesc'
        ];

        // Process fields that might be arrays
        foreach ($arrayFields as $field) {
            if (isset($propertyData[$field])) {
                $processedData[$field] = is_array($propertyData[$field])
                    ? json_encode($propertyData[$field])
                    : $propertyData[$field];
            } else {
                $processedData[$field] = null;
            }
        }

        $leaseInfo->fill([
            'property_id' => $property->id,
            'lease_amount' => $propertyData['LeaseAmount'] ?? null,
            'lease_amount_frequency' => $propertyData['LeaseAmountFrequency'] ?? null,
            'lease_term' => $processedData['LeaseTerm'] ?? null,
            'lease_considered_yn' => $propertyData['LeaseConsideredYN'] ?? null,
            'lease_assignable_yn' => $propertyData['LeaseAssignableYN'] ?? null,
            'lease_renewal_option_yn' => $propertyData['LeaseRenewalOptionYN'] ?? null,
            'existing_lease_type' => $processedData['ExistingLeaseType'] ?? null,
            'land_lease_amount' => $propertyData['LandLeaseAmount'] ?? null,
            'land_lease_amount_frequency' => $propertyData['LandLeaseAmountFrequency'] ?? null,
            'land_lease_yn' => $propertyData['LandLeaseYN'] ?? null,
            'miamire_length_of_rental' => $processedData['MiamireLengthOfRental'] ?? null,
            'miamire_for_lease_yn' => $propertyData['MiamireForLeaseYN'] ?? null,
            'miamire_for_lease_mls_number' => $propertyData['MiamireForLeaseMlsNumber'] ?? null,
            'miamire_for_sale_yn' => $propertyData['MiamireForSaleYN'] ?? null,
            'miamire_for_sale_mls_number' => $propertyData['MiamireForSaleMlsNumber'] ?? null,
            'miamire_move_in_dollars' => $propertyData['MiamireMoveInDollars'] ?? null,
            'miamire_total_move_in_dollars' => $propertyData['MiamireTotalMoveInDollars'] ?? null,
            'miamire_pets_allowed_yn' => $propertyData['MiamirePetsAllowedYN'] ?? null,
            'miamire_pet_fee' => $propertyData['MiamirePetFee'] ?? null,
            'miamire_pet_fee_desc' => $processedData['MiamirePetFeeDesc'] ?? null,
            'miamire_application_fee' => $propertyData['MiamireApplicationFee'] ?? null,
            'miamire_rent_length_desc' => $processedData['MiamireRentLengthDesc'] ?? null,
        ]);

        $leaseInfo->save();
    }


    protected function createFeatureCategories()
    {
        foreach ($this->featureCategories as $categoryName) {
            BridgeFeatureCategory::firstOrCreate(['name' => $categoryName]);
        }
    }

    protected function displayStats()
    {
        $this->info('Import completed with the following results:');
        $this->info("Total properties processed: {$this->stats['total']}");
        $this->info("Properties created: {$this->stats['created']}");
        $this->info("Properties updated: {$this->stats['updated']}");
        $this->info("Properties skipped: {$this->stats['skipped']}");
        $this->info("Properties failed: {$this->stats['failed']}");
        $this->info("Media items imported: {$this->stats['media_imported']}");
        $this->info("Features imported: {$this->stats['features_imported']}");
        $this->info("Agents imported: {$this->stats['agents_imported']}");
        $this->info("Offices imported: {$this->stats['offices_imported']}");
        $this->info("Schools imported: {$this->stats['schools_imported']}");
    }
}
