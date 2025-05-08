<x-app-layout>
    <style>
        /* Admin Backend Styling */
        .admin-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }
        
        .admin-container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .admin-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .admin-subtitle {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .admin-card {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .admin-card-header {
            background-color: #f8fafc;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
        }
        
        .admin-card-body {
            padding: 1rem;
        }
        
        .admin-data-list {
            width: 100%;
        }
        
        .admin-data-item {
            display: flex;
            flex-direction: column;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 0;
        }
        
        .admin-data-item:last-child {
            border-bottom: none;
        }
        
        .admin-data-label {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }
        
        .admin-data-value {
            word-break: break-word;
        }
        
        .admin-section-title {
            font-weight: 600;
            color: #1e293b;
            background-color: #f3f4f6;
            padding: 0.5rem;
            margin: 1rem 0 0.5rem 0;
        }
        
        .admin-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .admin-badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .admin-badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .admin-badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .admin-badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .admin-badge-default {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        .admin-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        
        .admin-tabs::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        .admin-tab {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        
        .admin-tab.active {
            border-bottom-color: #2563eb;
            color: #2563eb;
            font-weight: 500;
        }
        
        .admin-action-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #f3f4f6;
            color: #4b5563;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .admin-action-btn:hover {
            background-color: #e5e7eb;
        }
        
        .admin-action-btn-primary {
            background-color: #2563eb;
            color: white;
        }
        
        .admin-action-btn-primary:hover {
            background-color: #1d4ed8;
        }
        
        .admin-action-btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .admin-action-btn-danger:hover {
            background-color: #dc2626;
        }
        
        /* Media grid for backend */
        .admin-media-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 0.75rem;
        }
        
        @media (min-width: 480px) {
            .admin-media-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 640px) {
            .admin-media-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 768px) {
            .admin-media-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .admin-media-item {
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: 0.25rem;
            overflow: hidden;
        }
        
        .admin-media-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .admin-media-info {
            padding: 0.5rem;
            font-size: 0.75rem;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Tab content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .admin-header .flex {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-header .flex > div:last-child {
                margin-top: 0.5rem;
                width: 100%;
                display: flex;
                justify-content: space-between;
            }
        }
    </style>

    <div class="admin-header">
        <div class="admin-container">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="admin-title">Property Details</h1>
                    <p class="admin-subtitle">ID: {{ $property->id }} | Listing Key: {{ $property->listing_key }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('properties') }}" class="admin-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <div class="admin-tabs">
            <div class="admin-tab active" data-tab="overview">Overview</div>
            <div class="admin-tab" data-tab="details">Details</div>
            <div class="admin-tab" data-tab="amenities">Amenities</div>
            <div class="admin-tab" data-tab="media">Media</div>
            <div class="admin-tab" data-tab="schools">Schools</div>
            <div class="admin-tab" data-tab="financial">Financial</div>
            <div class="admin-tab" data-tab="agents">Agents & Offices</div>
            <div class="admin-tab" data-tab="bridge">Bridge Data</div>
        </div>

        <!-- Overview Section -->
        <div id="overview" class="tab-content active">
            <div class="admin-card">
                <div class="admin-card-header">Basic Information</div>
                <div class="admin-card-body">
                    <div class="admin-data-list">
                        <div class="admin-data-item">
                            <div class="admin-data-label">Property Type</div>
                            <div class="admin-data-value">{{ $property->PropertyType }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Status</div>
                            <div class="admin-data-value">
                                <span class="admin-badge {{ $property->StandardStatus == 'Active' ? 'admin-badge-success' : 'admin-badge-default' }}">
                                    {{ $property->StandardStatus }}
                                </span>
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">BuildingName</div>
                            <div class="admin-data-value">{{ $property->building_name }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Price</div>
                            <div class="admin-data-value">${{ number_format($property->ListPrice, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing Agent</div>
                            <div class="admin-data-value">{{ $property->ListAgentFullName }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Number</div>
                            <div class="admin-data-value">
                                {{ $property->StreetNumber }} 
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">StreetDirPrefix</div>
                            <div class="admin-data-value">
                                {{ $property->StreetDirPrefix }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">StreetName</div>
                            <div class="admin-data-value">
                                {{ $property->StreetName }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">StreetSuffix</div>
                            <div class="admin-data-value">
                                {{ $property->StreetSuffix }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">City</div>
                            <div class="admin-data-value">
                                {{ $property->City }}
                            </div>
                        </div>
                        <div class="admin-data-item">
                            <div class="admin-data-label">StateorProvince</div>
                            <div class="admin-data-value">
                                {{ $property->StateOrProvince }} 
                            </div>
                        </div>
                        <div class="admin-data-item">
                            <div class="admin-data-label">PostalCode</div>
                            <div class="admin-data-value">
                                {{ $property->PostalCode }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Country</div>
                            <div class="admin-data-value">{{ $property->Country ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">UnparsedAddress</div>
                            <div class="admin-data-value">
                                {{ $property->UnparsedAddress }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Coordinates</div>
                            <div class="admin-data-value">{{ $property->Latitude ?? 'N/A' }}, {{ $property->Longitude ?? 'N/A' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Unit Number</div>
                            <div class="admin-data-value">
                                {{ $property->UnitNumber }}
                            </div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Bedrooms</div>
                            <div class="admin-data-value">{{ $property->BedroomsTotal }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Bathrooms</div>
                            <div class="admin-data-value">{{ $property->BathroomsFull }}{{ $property->BathroomsHalf ? ' + ' . $property->BathroomsHalf . ' half' : '' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Living Area</div>
                            <div class="admin-data-value">{{ number_format($property->LivingArea) }} sqft</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Created At</div>
                            <div class="admin-data-value">{{ $property->created_at ? $property->created_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Updated At</div>
                            <div class="admin-data-value">{{ $property->updated_at ? $property->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Details Section -->
        <div id="details" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">Property Details</div>
                <div class="admin-card-body">
                    @php $details = $property->details; @endphp
                    @if ($details)
                    <div class="admin-data-list">
                        <div class="admin-data-item">
                            <div class="admin-data-label">Building Area</div>
                            <div class="admin-data-value">{{ $details->BuildingAreaTotal ?? 'N/A' }} sqft ({{ $details->BuildingAreaSource ?? 'N/A' }})</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Structure Type</div>
                            <div class="admin-data-value">{{ $details->StructureType ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Architectural Style</div>
                            <div class="admin-data-value">{{ $details->ArchitecturalStyle ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Ownership</div>
                            <div class="admin-data-value">{{ $details->Ownership ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">New Construction</div>
                            <div class="admin-data-value">{{ $details->NewConstructionYN ? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Heating</div>
                            <div class="admin-data-value">{{ $details->Heating ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Cooling</div>
                            <div class="admin-data-value">{{ $details->Cooling ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Water Source</div>
                            <div class="admin-data-value">{{ $details->WaterSource ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Features</div>
                            <div class="admin-data-value">{{ $details->LotFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">View</div>
                            <div class="admin-data-value">{{ $details->ViewYN ? $details->View ?? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Parcel Number</div>
                            <div class="admin-data-value">{{ $details->ParcelNumber ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Annual Tax</div>
                            <div class="admin-data-value">${{ number_format($details->TaxAnnualAmount ?? 0, 2) }} ({{ $details->TaxYear ?? 'N/A' }})</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Possession</div>
                            <div class="admin-data-value">{{ $details->Possession ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Current Use</div>
                            <div class="admin-data-value">{{ $details->CurrentUse ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Waterfront</div>
                            <div class="admin-data-value">{{ $details->WaterfrontYN ? $details->WaterfrontFeatures ?? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Special Listing Conditions</div>
                            <div class="admin-data-value">{{ $details->SpecialListingConditions ?? 'N/A' }}</div>
                        </div>
                    </div>
                    @else
                    <p class="text-gray-500">No property details available.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Amenities Section -->
        <div id="amenities" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">Amenities</div>
                <div class="admin-card-body">
                    @if ($property->amenities)
                    <div class="admin-data-list">
                        <div class="admin-section-title">Interior Features</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Interior Features</div>
                            <div class="admin-data-value">{{ $property->amenities->InteriorFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Appliances</div>
                            <div class="admin-data-value">{{ $property->amenities->Appliances ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Flooring</div>
                            <div class="admin-data-value">{{ $property->amenities->Flooring ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Window Features</div>
                            <div class="admin-data-value">{{ $property->amenities->WindowFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Door Features</div>
                            <div class="admin-data-value">{{ $property->amenities->DoorFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Laundry Features</div>
                            <div class="admin-data-value">{{ $property->amenities->LaundryFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Accessibility Features</div>
                            <div class="admin-data-value">{{ $property->amenities->AccessibilityFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Fireplace</div>
                            <div class="admin-data-value">{{ $property->amenities->FireplaceFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Security Features</div>
                            <div class="admin-data-value">{{ $property->amenities->SecurityFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Exterior Features</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Exterior Features</div>
                            <div class="admin-data-value">{{ $property->amenities->ExteriorFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Patio & Porch Features</div>
                            <div class="admin-data-value">{{ $property->amenities->PatioAndPorchFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Fencing</div>
                            <div class="admin-data-value">{{ $property->amenities->Fencing ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Other Structures</div>
                            <div class="admin-data-value">{{ $property->amenities->OtherStructures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Building Features</div>
                            <div class="admin-data-value">{{ $property->amenities->BuildingFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Parking</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Garage</div>
                            <div class="admin-data-value">{{ $property->amenities->GarageYN ? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Attached Garage</div>
                            <div class="admin-data-value">{{ $property->amenities->AttachedGarageYN ? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Garage Spaces</div>
                            <div class="admin-data-value">{{ $property->amenities->GarageSpaces ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Covered Spaces</div>
                            <div class="admin-data-value">{{ $property->amenities->CoveredSpaces ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Total Parking</div>
                            <div class="admin-data-value">{{ $property->amenities->ParkingTotal ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Parking Features</div>
                            <div class="admin-data-value">{{ $property->amenities->ParkingFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Pool & Spa</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Private Pool</div>
                            <div class="admin-data-value">{{ $property->amenities->PoolPrivateYN ? ($property->amenities->PoolFeatures ?? 'Yes') : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Spa</div>
                            <div class="admin-data-value">{{ $property->amenities->SpaYN ? ($property->amenities->SpaFeatures ?? 'Yes') : 'No' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Community</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">HOA</div>
                            <div class="admin-data-value">{{ $property->amenities->AssociationYN ? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Association Fee</div>
                            <div class="admin-data-value">${{ number_format($property->amenities->AssociationFee ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Fee Frequency</div>
                            <div class="admin-data-value">{{ $property->amenities->AssociationFeeFrequency ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Association Amenities</div>
                            <div class="admin-data-value">{{ $property->amenities->AssociationAmenities ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Community Features</div>
                            <div class="admin-data-value">{{ $property->amenities->CommunityFeatures ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Number of Units</div>
                            <div class="admin-data-value">{{ $property->amenities->NumberOfUnitsInCommunity ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Other Amenities</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Horse Property</div>
                            <div class="admin-data-value">{{ $property->amenities->HorseYN ? 'Yes' : 'No' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Horse Amenities</div>
                            <div class="admin-data-value">{{ $property->amenities->HorseAmenities ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Utilities</div>
                            <div class="admin-data-value">{{ $property->amenities->Utilities ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Other Equipment</div>
                            <div class="admin-data-value">{{ $property->amenities->OtherEquipment ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Furnished</div>
                            <div class="admin-data-value">{{ $property->amenities->Furnished ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Inclusions</div>
                            <div class="admin-data-value">{{ $property->amenities->Inclusions ?? 'N/A' }}</div>
                        </div>
                    </div>
                    @else
                    <p class="text-gray-500">No amenities information available.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Media Section -->
        <div id="media" class="tab-content">
            @if ($property->media->count())
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex justify-between items-center">
                        <span>Media ({{ $property->media->count() }} items)</span>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="admin-media-grid">
                        @foreach ($property->media as $media)
                        <div class="admin-media-item">
                            <img src="{{ $media->url }}" alt="{{ $media->title ?? 'Media' }}">
                            <div class="admin-media-info">
                                <div class="font-medium truncate">{{ $media->title ?? 'Untitled' }}</div>
                                <div class="text-gray-500 truncate">{{ $media->description ?? 'No description' }}</div>
                                <div class="text-gray-500 truncate">Type: {{ $media->media_type }}</div>
                                <div class="text-gray-500 truncate">Order: {{ $media->order }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex justify-between items-center">
                        <span>Media</span>
                    </div>
                </div>
                <div class="admin-card-body">
                    <p class="text-gray-500">No media available for this property.</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Schools Section -->
        <div id="schools" class="tab-content">
            @if ($property->schools)
            <div class="admin-card">
                <div class="admin-card-header">Schools</div>
                <div class="admin-card-body">
                    <div class="admin-data-list">
                        <div class="admin-data-item">
                            <div class="admin-data-label">Elementary School</div>
                            <div class="admin-data-value">{{ $property->schools->ElementarySchool ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Elementary School District</div>
                            <div class="admin-data-value">{{ $property->schools->ElementarySchoolDistrict ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Middle/Junior School</div>
                            <div class="admin-data-value">{{ $property->schools->MiddleOrJuniorSchool ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Middle/Junior School District</div>
                            <div class="admin-data-value">{{ $property->schools->MiddleOrJuniorSchoolDistrict ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">High School</div>
                            <div class="admin-data-value">{{ $property->schools->HighSchool ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">High School District</div>
                            <div class="admin-data-value">{{ $property->schools->HighSchoolDistrict ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="admin-card">
                <div class="admin-card-header">Schools</div>
                <div class="admin-card-body">
                    <p class="text-gray-500">No school information available.</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Financial Details Section -->
        <div id="financial" class="tab-content">
            @if ($property->financialDetails)
            <div class="admin-card">
                <div class="admin-card-header">Financial Details</div>
                <div class="admin-card-body">
                    <div class="admin-data-list">
                        <div class="admin-data-item">
                            <div class="admin-data-label">Gross Income</div>
                            <div class="admin-data-value">${{ number_format($property->financialDetails->GrossIncome ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Net Operating Income</div>
                            <div class="admin-data-value">${{ number_format($property->financialDetails->NetOperatingIncome ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Total Actual Rent</div>
                            <div class="admin-data-value">${{ number_format($property->financialDetails->TotalActualRent ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Operating Expense</div>
                            <div class="admin-data-value">${{ number_format($property->financialDetails->OperatingExpense ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Lease Amount</div>
                            <div class="admin-data-value">${{ number_format($property->financialDetails->LeaseAmount ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Lease Term</div>
                            <div class="admin-data-value">{{ $property->financialDetails->LeaseTerm ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Business Name</div>
                            <div class="admin-data-value">{{ $property->financialDetails->BusinessName ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="admin-card">
                <div class="admin-card-header">Financial Details</div>
                <div class="admin-card-body">
                    <p class="text-gray-500">No financial information available.</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Agents & Offices Section -->
        <div id="agents" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">Agents & Offices</div>
                <div class="admin-card-body">
                    <div class="admin-data-list">
                        <div class="admin-section-title">Listing Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Agent Key</div>
                            <div class="admin-data-value">{{ $property->ListAgentKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Agent Full Name</div>
                            <div class="admin-data-value">{{ $property->ListAgentFullName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Office Key</div>
                            <div class="admin-data-value">{{ $property->ListOfficeKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Office Name</div>
                            <div class="admin-data-value">{{ $property->ListOfficeName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Co-Listing Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-List Agent Key</div>
                            <div class="admin-data-value">{{ $property->CoListAgentKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-List Agent Full Name</div>
                            <div class="admin-data-value">{{ $property->CoListAgentFullName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-List Office Key</div>
                            <div class="admin-data-value">{{ $property->CoListOfficeKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-List Office Name</div>
                            <div class="admin-data-value">{{ $property->CoListOfficeName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Buyer Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Buyer Agent Key</div>
                            <div class="admin-data-value">{{ $property->BuyerAgentKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Buyer Agent Full Name</div>
                            <div class="admin-data-value">{{ $property->BuyerAgentFullName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Buyer Office Key</div>
                            <div class="admin-data-value">{{ $property->BuyerOfficeKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Buyer Office Name</div>
                            <div class="admin-data-value">{{ $property->BuyerOfficeName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Co-Buyer Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-Buyer Agent Key</div>
                            <div class="admin-data-value">{{ $property->CoBuyerAgentKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-Buyer Agent Full Name</div>
                            <div class="admin-data-value">{{ $property->CoBuyerAgentFullName ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-Buyer Office Key</div>
                            <div class="admin-data-value">{{ $property->CoBuyerOfficeKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Co-Buyer Office Name</div>
                            <div class="admin-data-value">{{ $property->CoBuyerOfficeName ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bridge Data Section -->
        <div id="bridge" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">Bridge API Data</div>
                <div class="admin-card-body">
                    <div class="admin-data-list">
                        <div class="admin-section-title">Bridge Identifiers</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing Key</div>
                            <div class="admin-data-value">{{ $property->ListingKey ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing ID</div>
                            <div class="admin-data-value">{{ $property->ListingId ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">MLS Number</div>
                            <div class="admin-data-value">{{ $property->MLSNumber ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">MLS Status</div>
                            <div class="admin-data-value">{{ $property->MlsStatus ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Standard Status</div>
                            <div class="admin-data-value">{{ $property->StandardStatus ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">MLS Area Major</div>
                            <div class="admin-data-value">{{ $property->MLSAreaMajor ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">MLS Area Minor</div>
                            <div class="admin-data-value">{{ $property->MLSAreaMinor ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Listing Dates</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">List Date</div>
                            <div class="admin-data-value">{{ $property->ListDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Status Change Date</div>
                            <div class="admin-data-value">{{ $property->StatusChangeDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Pending Date</div>
                            <div class="admin-data-value">{{ $property->PendingDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Close Date</div>
                            <div class="admin-data-value">{{ $property->CloseDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Contract Date</div>
                            <div class="admin-data-value">{{ $property->ContractDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Expiration Date</div>
                            <div class="admin-data-value">{{ $property->ExpirationDate ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Cumulative Days on Market</div>
                            <div class="admin-data-value">{{ $property->CumulativeDaysOnMarket ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Days On Market</div>
                            <div class="admin-data-value">{{ $property->DaysOnMarket ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Property Classification</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Property Sub Type</div>
                            <div class="admin-data-value">{{ $property->PropertySubType ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Property Use</div>
                            <div class="admin-data-value">{{ $property->PropertyUse ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Zoning</div>
                            <div class="admin-data-value">{{ $property->Zoning ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Property Measurements</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Size</div>
                            <div class="admin-data-value">{{ $property->LotSizeAcres ?? 'N/A' }} acres / {{ $property->LotSizeSquareFeet ?? 'N/A' }} sq ft</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Dimensions</div>
                            <div class="admin-data-value">{{ $property->LotDimensions ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Living Area</div>
                            <div class="admin-data-value">{{ $property->LivingArea ?? 'N/A' }} {{ $property->LivingAreaUnits ?? 'sq ft' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Year Built</div>
                            <div class="admin-data-value">{{ $property->YearBuilt ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Year Built Source</div>
                            <div class="admin-data-value">{{ $property->YearBuiltSource ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Year Built Details</div>
                            <div class="admin-data-value">{{ $property->YearBuiltDetails ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-section-title">Pricing Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Original List Price</div>
                            <div class="admin-data-value">${{ number_format($property->OriginalListPrice ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Previous List Price</div>
                            <div class="admin-data-value">${{ number_format($property->PreviousListPrice ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Close Price</div>
                            <div class="admin-data-value">${{ number_format($property->ClosePrice ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Concessions Amount</div>
                            <div class="admin-data-value">${{ number_format($property->ConcessionsAmount ?? 0, 2) }}</div>
                        </div>
                        
                        <div class="admin-section-title">Additional Information</div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Public Remarks</div>
                            <div class="admin-data-value">{{ $property->PublicRemarks ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Private Remarks</div>
                            <div class="admin-data-value">{{ $property->PrivateRemarks ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Showing Instructions</div>
                            <div class="admin-data-value">{{ $property->ShowingInstructions ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Occupancy Type</div>
                            <div class="admin-data-value">{{ $property->OccupancyType ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="admin-data-item">
                            <div class="admin-data-label">Ownership Type</div>
                            <div class="admin-data-value">{{ $property->OwnershipType ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.admin-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show the corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</x-app-layout>