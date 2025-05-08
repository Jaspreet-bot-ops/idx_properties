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
            scrollbar-width: none;
            /* Firefox */
        }

        .admin-tabs::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Edge */
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

            .admin-header .flex>div:last-child {
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
                    <p class="admin-subtitle">ID: {{ $property->id }} | Listing Key: {{ $property->ListingKey }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('properties') }}" class="admin-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
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
            {{-- <div class="admin-tab" data-tab="amenities">Amenities</div> --}}
            <div class="admin-tab" data-tab="features">Features</div>
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
                            <div class="admin-data-value">{{ $property->property_type }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Property Sub Type</div>
                            <div class="admin-data-value">{{ $property->property_sub_type }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Status</div>
                            <div class="admin-data-value">
                                <span
                                    class="admin-badge {{ $property->standard_status == 'Active' ? 'admin-badge-success' : 'admin-badge-default' }}">
                                    {{ $property->standard_status }}
                                </span>
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">MLS Status</div>
                            <div class="admin-data-value">{{ $property->mls_status }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">List Price</div>
                            <div class="admin-data-value">${{ number_format($property->list_price, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Original List Price</div>
                            <div class="admin-data-value">${{ number_format($property->original_list_price, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Close Price</div>
                            <div class="admin-data-value">${{ number_format($property->close_price, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Days on Market</div>
                            <div class="admin-data-value">{{ $property->days_on_market }}</div>
                        </div>

                        <div class="admin-section-title">Address Information</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Number</div>
                            <div class="admin-data-value">{{ $property->street_number }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Direction Prefix</div>
                            <div class="admin-data-value">{{ $property->street_dir_prefix }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Name</div>
                            <div class="admin-data-value">{{ $property->street_name }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Suffix</div>
                            <div class="admin-data-value">{{ $property->street_suffix }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Street Direction Suffix</div>
                            <div class="admin-data-value">{{ $property->street_dir_suffix }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Unit Number</div>
                            <div class="admin-data-value">{{ $property->unit_number }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">City</div>
                            <div class="admin-data-value">{{ $property->city }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">State/Province</div>
                            <div class="admin-data-value">{{ $property->state_or_province }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Postal Code</div>
                            <div class="admin-data-value">{{ $property->postal_code }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Postal Code Plus4</div>
                            <div class="admin-data-value">{{ $property->postal_code_plus4 }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">County/Parish</div>
                            <div class="admin-data-value">{{ $property->county_or_parish }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Country</div>
                            <div class="admin-data-value">{{ $property->country ?? 'N/A' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Country Region</div>
                            <div class="admin-data-value">{{ $property->country_region }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Unparsed Address</div>
                            <div class="admin-data-value">{{ $property->unparsed_address }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Coordinates</div>
                            <div class="admin-data-value">{{ $property->latitude ?? 'N/A' }},
                                {{ $property->longitude ?? 'N/A' }}</div>
                        </div>

                        <div class="admin-section-title">Property Specifications</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Bedrooms</div>
                            <div class="admin-data-value">{{ $property->bedrooms_total }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Bathrooms (Decimal)</div>
                            <div class="admin-data-value">{{ $property->bathrooms_total_decimal }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Bathrooms</div>
                            <div class="admin-data-value">
                                {{ $property->bathrooms_full }}{{ $property->bathrooms_half ? ' + ' . $property->bathrooms_half . ' half' : '' }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Living Area</div>
                            <div class="admin-data-value">{{ number_format($property->living_area) }}
                                {{ $property->living_area_units }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Size (Square Feet)</div>
                            <div class="admin-data-value">{{ number_format($property->lot_size_square_feet) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Size (Acres)</div>
                            <div class="admin-data-value">{{ $property->lot_size_acres }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Dimensions</div>
                            <div class="admin-data-value">{{ $property->lot_size_dimensions }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Year Built</div>
                            <div class="admin-data-value">{{ $property->year_built }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Year Built Details</div>
                            <div class="admin-data-value">{{ $property->year_built_details }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Stories Total</div>
                            <div class="admin-data-value">{{ $property->stories_total }}</div>
                        </div>

                        <div class="admin-section-title">Parking Information</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Garage</div>
                            <div class="admin-data-value">{{ $property->garage_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Attached Garage</div>
                            <div class="admin-data-value">{{ $property->attached_garage_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Garage Spaces</div>
                            <div class="admin-data-value">{{ $property->garage_spaces }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Carport</div>
                            <div class="admin-data-value">{{ $property->carport_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Carport Spaces</div>
                            <div class="admin-data-value">{{ $property->carport_spaces }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Open Parking</div>
                            <div class="admin-data-value">{{ $property->open_parking_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Covered Spaces</div>
                            <div class="admin-data-value">{{ $property->covered_spaces }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Total Parking</div>
                            <div class="admin-data-value">{{ $property->parking_total }}</div>
                        </div>

                        <div class="admin-section-title">Pool & Spa Information</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Private Pool</div>
                            <div class="admin-data-value">{{ $property->pool_private_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Spa</div>
                            <div class="admin-data-value">{{ $property->spa_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-section-title">Financial Information</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Tax Annual Amount</div>
                            <div class="admin-data-value">${{ number_format($property->tax_annual_amount, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Tax Year</div>
                            <div class="admin-data-value">{{ $property->tax_year }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Tax Lot</div>
                            <div class="admin-data-value">{{ $property->tax_lot }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Parcel Number</div>
                            <div class="admin-data-value">{{ $property->parcel_number }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Association Fee</div>
                            <div class="admin-data-value">${{ number_format($property->association_fee, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Association Fee Frequency</div>
                            <div class="admin-data-value">{{ $property->association_fee_frequency }}</div>
                        </div>

                        <div class="admin-section-title">Property Features</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">New Construction</div>
                            <div class="admin-data-value">{{ $property->new_construction_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Furnished</div>
                            <div class="admin-data-value">{{ $property->furnished }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Waterfront</div>
                            <div class="admin-data-value">{{ $property->waterfront_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">View</div>
                            <div class="admin-data-value">{{ $property->view_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Horse Property</div>
                            <div class="admin-data-value">{{ $property->horse_yn ? 'Yes' : 'No' }}</div>
                        </div>

                        <div class="admin-section-title">Virtual Tour</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Virtual Tour URL</div>
                            <div class="admin-data-value">
                                @if ($property->virtual_tour_url_unbranded)
                                    <a href="{{ $property->virtual_tour_url_unbranded }}" target="_blank">View
                                        Virtual Tour</a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>

                        <div class="admin-section-title">Remarks</div>
                        <div class="admin-data-item">
                            <div class="admin-data-label">Public Remarks</div>
                            <div class="admin-data-value">{{ $property->public_remarks }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Private Remarks</div>
                            <div class="admin-data-value">{{ $property->private_remarks }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Syndication Remarks</div>
                            <div class="admin-data-value">{{ $property->syndication_remarks }}</div>
                        </div>

                        <div class="admin-section-title">Listing Information</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing Key</div>
                            <div class="admin-data-value">{{ $property->listing_key }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing ID</div>
                            <div class="admin-data-value">{{ $property->listing_id }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing Contract Date</div>
                            <div class="admin-data-value">{{ $property->listing_contract_date }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">On Market Date</div>
                            <div class="admin-data-value">{{ $property->on_market_date }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Off Market Date</div>
                            <div class="admin-data-value">{{ $property->off_market_date }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Pending Timestamp</div>
                            <div class="admin-data-value">{{ $property->pending_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Close Date</div>
                            <div class="admin-data-value">{{ $property->close_date }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Contract Status Change Date</div>
                            <div class="admin-data-value">{{ $property->contract_status_change_date }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Listing Agreement</div>
                            <div class="admin-data-value">{{ $property->listing_agreement }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Contingency</div>
                            <div class="admin-data-value">{{ $property->contingency }}</div>
                        </div>

                        <div class="admin-section-title">API Timestamps</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Original Entry Timestamp</div>
                            <div class="admin-data-value">{{ $property->original_entry_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Modification Timestamp</div>
                            <div class="admin-data-value">{{ $property->modification_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Price Change Timestamp</div>
                            <div class="admin-data-value">{{ $property->price_change_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Status Change Timestamp</div>
                            <div class="admin-data-value">{{ $property->status_change_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Major Change Timestamp</div>
                            <div class="admin-data-value">{{ $property->major_change_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Photos Change Timestamp</div>
                            <div class="admin-data-value">{{ $property->photos_change_timestamp }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Bridge Modification Timestamp</div>
                            <div class="admin-data-value">{{ $property->bridge_modification_timestamp }}</div>
                        </div>

                        <div class="admin-section-title">Metadata</div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Source System Key</div>
                            <div class="admin-data-value">{{ $property->source_system_key }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Originating System Key</div>
                            <div class="admin-data-value">{{ $property->originating_system_key }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Originating System Name</div>
                            <div class="admin-data-value">{{ $property->originating_system_name }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Originating System ID</div>
                            <div class="admin-data-value">{{ $property->originating_system_id }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Created At</div>
                            <div class="admin-data-value">
                                {{ $property->created_at ? $property->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Updated At</div>
                            <div class="admin-data-value">
                                {{ $property->updated_at ? $property->updated_at->format('Y-m-d H:i:s') : 'N/A' }}
                            </div>
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
                                <div class="admin-data-label">Building Name</div>
                                <div class="admin-data-value">{{ $details->building_name ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Builder Model</div>
                                <div class="admin-data-value">{{ $details->builder_model ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Business Name</div>
                                <div class="admin-data-value">{{ $details->buisness_name ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Business Type</div>
                                <div class="admin-data-value">{{ $details->buisness_type ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Subdivision Name</div>
                                <div class="admin-data-value">{{ $details->subdivision_name ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Building Area Total</div>
                                <div class="admin-data-value">{{ $details->building_area_total ?? 'N/A' }}
                                    {{ $details->building_area_units ?? 'sq ft' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Building Area Source</div>
                                <div class="admin-data-value">{{ $details->building_area_source ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Common Walls</div>
                                <div class="admin-data-value">{{ $details->common_walls ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Directions</div>
                                <div class="admin-data-value">{{ $details->directions ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Direction Faces</div>
                                <div class="admin-data-value">{{ $details->direction_faces ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Property Condition</div>
                                <div class="admin-data-value">{{ $details->property_condition ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Zoning</div>
                                <div class="admin-data-value">{{ $details->zoning ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Tax Legal Description</div>
                                <div class="admin-data-value">{{ $details->tax_legal_description ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Current Financing</div>
                                <div class="admin-data-value">{{ $details->current_financing ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Possession</div>
                                <div class="admin-data-value">{{ $details->possession ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Showing Instructions</div>
                                <div class="admin-data-value">{{ $details->showing_instructions ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Showing Contact Type</div>
                                <div class="admin-data-value">{{ $details->showing_contact_type ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Availability Date</div>
                                <div class="admin-data-value">{{ $details->availability_date ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Development Status</div>
                                <div class="admin-data-value">{{ $details->development_status ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Ownership Type</div>
                                <div class="admin-data-value">{{ $details->ownership_type ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Special Listing Conditions</div>
                                <div class="admin-data-value">{{ $details->special_listing_conditions ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Listing Terms</div>
                                <div class="admin-data-value">{{ $details->listing_terms ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Listing Service</div>
                                <div class="admin-data-value">{{ $details->listing_service ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Sign on Property</div>
                                <div class="admin-data-value">{{ $details->sign_on_property_yn ? 'Yes' : 'No' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Association</div>
                                <div class="admin-data-value">{{ $details->association_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Disclosures</div>
                                <div class="admin-data-value">{{ $details->disclosures ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Home Warranty</div>
                                <div class="admin-data-value">{{ $details->home_warranty_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <!-- MIAMIRE specific fields -->
                            <div class="admin-section-title">MIAMIRE Specific Information</div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Adjusted Area (SF)</div>
                                <div class="admin-data-value">{{ $details->miamire_adjusted_area_sf ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">List Price per Sq Ft</div>
                                <div class="admin-data-value">
                                    ${{ number_format($details->miamire_lp_amt_sq_ft ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Ratio Current Price by Sq Ft</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_ratio_current_price_by_sqft ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Area</div>
                                <div class="admin-data-value">{{ $details->miamire_area ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Style</div>
                                <div class="admin-data-value">{{ $details->miamire_style ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Internet Remarks</div>
                                <div class="admin-data-value">{{ $details->miamire_internet_remarks ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Pool</div>
                                <div class="admin-data-value">{{ $details->miamire_pool_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Pool Dimensions</div>
                                <div class="admin-data-value">{{ $details->miamire_pool_dimensions ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Membership Purchase Required</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_membership_purch_rqd_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Special Assessment</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_special_assessment_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Type of Association</div>
                                <div class="admin-data-value">{{ $details->miamire_type_of_association ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Type of Governing Bodies</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_type_of_governing_bodies ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Restrictions</div>
                                <div class="admin-data-value">{{ $details->miamire_restrictions ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Subdivision Information</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_subdivision_information ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Buyer Country of Residence</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_buyer_country_of_residence ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Seller Contributions</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_seller_contributions_yn ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Seller Contributions Amount</div>
                                <div class="admin-data-value">
                                    ${{ number_format($details->miamire_seller_contributions_amt ?? 0, 2) }}</div>
                            </div>

                            <!-- Additional MIAMIRE fields -->
                            <div class="admin-section-title">Additional MIAMIRE Information</div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Application Fee</div>
                                <div class="admin-data-value">
                                    ${{ number_format($details->miamire_application_fee ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Approval Information</div>
                                <div class="admin-data-value">{{ $details->miamire_approval_information ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Attribution Contact</div>
                                <div class="admin-data-value">{{ $details->miamire_attribution_contact ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Buy State</div>
                                <div class="admin-data-value">{{ $details->miamire_buy_state ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">For Lease MLS Number</div>
                                <div class="admin-data-value">{{ $details->miamire_for_lease_mls_number ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">For Lease</div>
                                <div class="admin-data-value">{{ $details->miamire_for_lease_yn ? 'Yes' : 'No' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">For Sale MLS Number</div>
                                <div class="admin-data-value">{{ $details->miamire_for_sale_mls_number ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">For Sale</div>
                                <div class="admin-data-value">{{ $details->miamire_for_sale_yn ? 'Yes' : 'No' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Global City</div>
                                <div class="admin-data-value">{{ $details->miamire_global_city ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Guest House Description</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_guest_house_description ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Length of Rental</div>
                                <div class="admin-data-value">{{ $details->miamire_length_of_rental ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Maintenance Includes</div>
                                <div class="admin-data-value">{{ $details->miamire_maintenance_includes ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Maximum Leasable Sq Ft</div>
                                <div class="admin-data-value">{{ $details->miamire_maximum_leasable_sqft ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Move In Dollars</div>
                                <div class="admin-data-value">
                                    ${{ number_format($details->miamire_move_in_dollars ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">OK to Advertise List</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_ok_to_advertise_list ? 'Yes' : 'No' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Pet Fee</div>
                                <div class="admin-data-value">${{ number_format($details->miamire_pet_fee ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Pet Fee Description</div>
                                <div class="admin-data-value">{{ $details->miamire_pet_fee_desc ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Pets Allowed</div>
                                <div class="admin-data-value">{{ $details->miamire_pets_allowed_yn ? 'Yes' : 'No' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Rent Length Description</div>
                                <div class="admin-data-value">{{ $details->miamire_rent_length_desc ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Showing Time Flag</div>
                                <div class="admin-data-value">
                                    {{ $details->miamire_showing_time_flag ? 'Yes' : 'No' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Temporary Off Market Date</div>
                                <div class="admin-data-value">{{ $details->miamire_temp_off_market_date ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Total Move In Dollars</div>
                                <div class="admin-data-value">
                                    ${{ number_format($details->miamire_total_move_in_dollars ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Type of Business</div>
                                <div class="admin-data-value">{{ $details->miamire_type_of_business ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">No property details available.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="tab-content">
            <div class="admin-card">
                <div class="admin-card-header">Property Features</div>
                <div class="admin-card-body">
                    @if (isset($featuresGrouped) && $featuresGrouped->count() > 0)
                        <div class="admin-data-list">
                            @foreach ($featuresGrouped as $categoryName => $features)
                                <div class="admin-section-title">{{ $categoryName }}</div>

                                @foreach ($features as $feature)
                                    <div class="admin-data-item">
                                        <div class="admin-data-label">{{ $feature->name }}</div>
                                        <div class="admin-data-value">
                                            @if (isset($feature->pivot) && !empty($feature->pivot->value))
                                                {{ $feature->pivot->value }}
                                            @else
                                                Yes
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No features information available.</p>
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
                                    <img src="{{ $media->media_url }}" alt="{{ $media->title ?? 'Media' }}">
                                    {{-- <div class="admin-media-info">
                                <div class="font-medium truncate">{{ $media->title ?? 'Untitled' }}</div>
                                <div class="text-gray-500 truncate">{{ $media->description ?? 'No description' }}</div>
                                <div class="text-gray-500 truncate">Type: {{ $media->mime_type }}</div>
                                <div class="text-gray-500 truncate">Order: {{ $media->order }}</div>
                                <div class="text-gray-500 truncate">Primary: {{ $media->is_primary ? 'Yes' : 'No' }}</div>
                            </div> --}}
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
            <div class="admin-card">
                <div class="admin-card-header">Schools</div>
                <div class="admin-card-body">
                    @if ($property->schools)
                        <div class="admin-data-list">
                            <div class="admin-data-item">
                                <div class="admin-data-label">Elementary School</div>
                                <div class="admin-data-value">{{ $property->schools->ElementarySchool ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Elementary School District</div>
                                <div class="admin-data-value">
                                    {{ $property->schools->ElementarySchoolDistrict ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Middle/Junior School</div>
                                <div class="admin-data-value">{{ $property->schools->MiddleOrJuniorSchool ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Middle/Junior School District</div>
                                <div class="admin-data-value">
                                    {{ $property->schools->MiddleOrJuniorSchoolDistrict ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">High School</div>
                                <div class="admin-data-value">{{ $property->schools->HighSchool ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">High School District</div>
                                <div class="admin-data-value">{{ $property->schools->HighSchoolDistrict ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">No school information available.</p>
                    @endif
                </div>
            </div>

            @if ($property->elementarySchool || $property->middleSchool || $property->highSchool)
                <div class="admin-card mt-4">
                    <div class="admin-card-header">School Relationships</div>
                    <div class="admin-card-body">
                        <div class="admin-data-list">
                            @if ($property->elementarySchool)
                                <div class="admin-section-title">Elementary School Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">School Name</div>
                                    <div class="admin-data-value">{{ $property->elementarySchool->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">District</div>
                                    <div class="admin-data-value">{{ $property->elementarySchool->district }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Type</div>
                                    <div class="admin-data-value">{{ $property->elementarySchool->type }}</div>
                                </div>
                            @endif

                            @if ($property->middleSchool)
                                <div class="admin-section-title">Middle School Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">School Name</div>
                                    <div class="admin-data-value">{{ $property->middleSchool->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">District</div>
                                    <div class="admin-data-value">{{ $property->middleSchool->district }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Type</div>
                                    <div class="admin-data-value">{{ $property->middleSchool->type }}</div>
                                </div>
                            @endif

                            @if ($property->highSchool)
                                <div class="admin-section-title">High School Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">School Name</div>
                                    <div class="admin-data-value">{{ $property->highSchool->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">District</div>
                                    <div class="admin-data-value">{{ $property->highSchool->district }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Type</div>
                                    <div class="admin-data-value">{{ $property->highSchool->type }}</div>
                                </div>
                            @endif
                        </div>
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
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->gross_income ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Gross Scheduled Income</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->gross_scheduled_income ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Net Operating Income</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->net_operating_income ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Total Actual Rent</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->total_actual_rent ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Operating Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->operating_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Operating Expense Includes</div>
                                <div class="admin-data-value">
                                    {{ $property->financialDetails->operating_expense_includes ?? 'N/A' }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Insurance Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->insurance_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Maintenance Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->maintenance_expense ?? 0, 2) }}
                                </div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Manager Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->manager_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">New Taxes Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->new_taxes_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Other Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->other_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Supplies Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->supplies_expense ?? 0, 2) }}</div>
                            </div>

                            <div class="admin-data-item">
                                <div class="admin-data-label">Trash Expense</div>
                                <div class="admin-data-value">
                                    ${{ number_format($property->financialDetails->trash_expense ?? 0, 2) }}</div>
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

            <!-- Agent Relationship Details -->
            @if ($property->listAgent || $property->coListAgent || $property->buyerAgent || $property->coBuyerAgent)
                <div class="admin-card mt-4">
                    <div class="admin-card-header">Agent Relationship Details</div>
                    <div class="admin-card-body">
                        <div class="admin-data-list">
                            @if ($property->listAgent)
                                <div class="admin-section-title">Listing Agent Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Agent ID</div>
                                    <div class="admin-data-value">{{ $property->listAgent->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->listAgent->full_name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Email</div>
                                    <div class="admin-data-value">{{ $property->listAgent->email ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->listAgent->phone ?? 'N/A' }}</div>
                                </div>
                            @endif

                            @if ($property->coListAgent)
                                <div class="admin-section-title">Co-Listing Agent Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Agent ID</div>
                                    <div class="admin-data-value">{{ $property->coListAgent->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->coListAgent->full_name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Email</div>
                                    <div class="admin-data-value">{{ $property->coListAgent->email ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->coListAgent->phone ?? 'N/A' }}</div>
                                </div>
                            @endif

                            @if ($property->buyerAgent)
                                <div class="admin-section-title">Buyer Agent Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Agent ID</div>
                                    <div class="admin-data-value">{{ $property->buyerAgent->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->buyerAgent->full_name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Email</div>
                                    <div class="admin-data-value">{{ $property->buyerAgent->email ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->buyerAgent->phone ?? 'N/A' }}</div>
                                </div>
                            @endif

                            @if ($property->coBuyerAgent)
                                <div class="admin-section-title">Co-Buyer Agent Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Agent ID</div>
                                    <div class="admin-data-value">{{ $property->coBuyerAgent->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->coBuyerAgent->full_name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Email</div>
                                    <div class="admin-data-value">{{ $property->coBuyerAgent->email ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->coBuyerAgent->phone ?? 'N/A' }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Office Relationship Details -->
            @if ($property->listOffice || $property->coListOffice || $property->buyerOffice || $property->coBuyerOffice)
                <div class="admin-card mt-4">
                    <div class="admin-card-header">Office Relationship Details</div>
                    <div class="admin-card-body">
                        <div class="admin-data-list">
                            @if ($property->listOffice)
                                <div class="admin-section-title">Listing Office Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Office ID</div>
                                    <div class="admin-data-value">{{ $property->listOffice->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->listOffice->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->listOffice->phone ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Address</div>
                                    <div class="admin-data-value">{{ $property->listOffice->address ?? 'N/A' }}</div>
                                </div>
                            @endif

                            @if ($property->coListOffice)
                                <div class="admin-section-title">Co-Listing Office Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Office ID</div>
                                    <div class="admin-data-value">{{ $property->coListOffice->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->coListOffice->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->coListOffice->phone ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Address</div>
                                    <div class="admin-data-value">{{ $property->coListOffice->address ?? 'N/A' }}
                                    </div>
                                </div>
                            @endif

                            @if ($property->buyerOffice)
                                <div class="admin-section-title">Buyer Office Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Office ID</div>
                                    <div class="admin-data-value">{{ $property->buyerOffice->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->buyerOffice->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->buyerOffice->phone ?? 'N/A' }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Address</div>
                                    <div class="admin-data-value">{{ $property->buyerOffice->address ?? 'N/A' }}
                                    </div>
                                </div>
                            @endif

                            @if ($property->coBuyerOffice)
                                <div class="admin-section-title">Co-Buyer Office Details</div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Office ID</div>
                                    <div class="admin-data-value">{{ $property->coBuyerOffice->id }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Name</div>
                                    <div class="admin-data-value">{{ $property->coBuyerOffice->name }}</div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Phone</div>
                                    <div class="admin-data-value">{{ $property->coBuyerOffice->phone ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="admin-data-item">
                                    <div class="admin-data-label">Address</div>
                                    <div class="admin-data-value">{{ $property->coBuyerOffice->address ?? 'N/A' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
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
                            <div class="admin-data-value">{{ $property->LotSizeAcres ?? 'N/A' }} acres /
                                {{ $property->LotSizeSquareFeet ?? 'N/A' }} sq ft</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Lot Dimensions</div>
                            <div class="admin-data-value">{{ $property->LotDimensions ?? 'N/A' }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Living Area</div>
                            <div class="admin-data-value">{{ $property->LivingArea ?? 'N/A' }}
                                {{ $property->LivingAreaUnits ?? 'sq ft' }}</div>
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
                            <div class="admin-data-value">${{ number_format($property->OriginalListPrice ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Previous List Price</div>
                            <div class="admin-data-value">${{ number_format($property->PreviousListPrice ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Close Price</div>
                            <div class="admin-data-value">${{ number_format($property->ClosePrice ?? 0, 2) }}</div>
                        </div>

                        <div class="admin-data-item">
                            <div class="admin-data-label">Concessions Amount</div>
                            <div class="admin-data-value">${{ number_format($property->ConcessionsAmount ?? 0, 2) }}
                            </div>
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
