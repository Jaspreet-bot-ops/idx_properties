<style>
    /* Media grid */
    .media-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 16px;
    }

    @media (min-width: 640px) {
        .media-grid {
            grid-template-columns: repeat(2, 1fr);
            /* 2 columns on small screens */
        }
    }

    @media (min-width: 768px) {
        .media-grid {
            grid-template-columns: repeat(3, 1fr);
            /* 3 columns on medium screens */
        }
    }

    @media (min-width: 1024px) {
        .media-grid {
            grid-template-columns: repeat(4, 1fr);
            /* 4 columns on large screens */
        }
    }

    .media-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s;
    }

    .media-card:hover {
        transform: scale(1.05);
    }

    .media-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .media-card .content {
        padding: 16px;
    }

    .media-card h4 {
        font-size: 1.1rem;
        font-weight: bold;
    }

    .media-card p {
        font-size: 0.875rem;
        color: #555;
    }
</style>
<x-app-layout>
    <div class="max-w-5xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold mb-4">Property Details</h2>
        <div class="mt-6">
            <a href="{{ route('properties') }}" class="text-blue-600 hover:underline">&larr; Back to List</a>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <p><strong>Listing Key000000000:</strong> {{ $property->ListingKey }}</p>
            <p><strong>Type:</strong> {{ $property->PropertyType }}</p>
            <p><strong>Price:</strong> ${{ number_format($property->ListPrice, 2) }}</p>
            <p><strong>Beds:</strong> {{ $property->BedroomsTotal }}</p>
            <p><strong>Baths:</strong> {{ $property->BathroomsFull }}{{ $property->BathroomsHalf ? '½' : '' }}</p>
            <p><strong>Area:</strong> {{ $property->LivingArea }} sqft</p>
            <p><strong>Address:</strong> {{ $property->StreetNumber }} {{ $property->StreetName }}
                {{ $property->StreetSuffix }}, {{ $property->City }}, {{ $property->StateOrProvince }}
                {{ $property->PostalCode }}</p>
            <p><strong>Status:</strong> {{ $property->StandardStatus }}</p>
            <p><strong>Agent:</strong> {{ $property->ListAgentFullName }}</p>
        </div>

        <div class="mt-6 bg-white p-6 rounded shadow">
            <div class="card-header">
                <h4>Property Details</h4>
            </div>
            <div class="card-body">
                @php $details = $property->details; @endphp

                @if ($details)
                    <ul class="list-group list-group-flush">
                        {{-- Building Details --}}
                        <li class="list-group-item"><strong>Building Area:</strong>
                            {{ $details->BuildingAreaTotal ?? 'N/A' }} sqft
                            ({{ $details->BuildingAreaSource ?? 'N/A' }})</li>
                        <li class="list-group-item"><strong>Structure Type:</strong>
                            {{ $details->StructureType ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Architectural Style:</strong>
                            {{ $details->ArchitecturalStyle ?? 'N/A' }}</li>

                        {{-- Property Characteristics --}}
                        <li class="list-group-item"><strong>Ownership:</strong> {{ $details->Ownership ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>New Construction:</strong>
                            {{ $details->NewConstructionYN ? 'Yes' : 'No' }}</li>

                        {{-- Utilities --}}
                        <li class="list-group-item"><strong>Heating:</strong> {{ $details->Heating ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Cooling:</strong> {{ $details->Cooling ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Water Source:</strong> {{ $details->WaterSource ?? 'N/A' }}
                        </li>

                        {{-- Lot Details --}}
                        <li class="list-group-item"><strong>Lot Features:</strong> {{ $details->LotFeatures ?? 'N/A' }}
                        </li>
                        <li class="list-group-item"><strong>View:</strong>
                            {{ $details->ViewYN ? $details->View ?? 'Yes' : 'No' }}</li>

                        {{-- Legal Info --}}
                        <li class="list-group-item"><strong>Parcel Number:</strong>
                            {{ $details->ParcelNumber ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Annual Tax:</strong>
                            ${{ number_format($details->TaxAnnualAmount ?? 0, 2) }} ({{ $details->TaxYear ?? 'N/A' }})
                        </li>

                        {{-- Possession & Use --}}
                        <li class="list-group-item"><strong>Possession:</strong> {{ $details->Possession ?? 'N/A' }}
                        </li>
                        <li class="list-group-item"><strong>Current Use:</strong> {{ $details->CurrentUse ?? 'N/A' }}
                        </li>

                        {{-- Waterfront --}}
                        <li class="list-group-item"><strong>Waterfront:</strong>
                            {{ $details->WaterfrontYN ? $details->WaterfrontFeatures ?? 'Yes' : 'No' }}</li>

                        {{-- Miscellaneous --}}
                        <li class="list-group-item"><strong>Special Listing Conditions:</strong>
                            {{ $details->SpecialListingConditions ?? 'N/A' }}</li>
                    </ul>
                @else
                    <p class="text-muted">No property details available.</p>
                @endif
            </div>
        </div>


        <div class="mt-6 bg-white p-6 rounded shadow">
            <div class="card-header">
                <h4>Amenities</h4>
            </div>
            <div class="card-body">
                @if ($property->amenities)
                    <ul class="list-group list-group-flush">

                        {{-- Interior Features --}}
                        <li class="list-group-item"><strong>Interior Features:</strong>
                            {{ $property->amenities->InteriorFeatures }}</li>

                        <li class="list-group-item"><strong>Appliances:</strong> {{ $property->amenities->Appliances }}
                        </li>

                        <li class="list-group-item"><strong>Flooring:</strong> {{ $property->amenities->Flooring }}
                        </li>

                        <li class="list-group-item"><strong>Window Features:</strong>
                            {{ $property->amenities->WindowFeatures }}</li>

                        <li class="list-group-item"><strong>Door Features:</strong>
                            {{ $property->amenities->DoorFeatures }}</li>

                        <li class="list-group-item"><strong>Laundry Features:</strong>
                            {{ $property->amenities->LaundryFeatures }}</li>

                        <li class="list-group-item"><strong>Accessibility Features:</strong>
                            {{ $property->amenities->AccessibilityFeatures }}</li>

                        <li class="list-group-item"><strong>Fireplace:</strong>
                            {{ $property->amenities->FireplaceFeatures ?? 'Yes' }}</li>

                        <li class="list-group-item"><strong>Security Features:</strong>
                            {{ $property->amenities->SecurityFeatures }}</li>


                        {{-- Exterior Features --}}
                        <li class="list-group-item"><strong>Exterior Features:</strong>
                            {{ $property->amenities->ExteriorFeatures }}</li>

                        <li class="list-group-item"><strong>Patio & Porch Features:</strong>
                            {{ $property->amenities->PatioAndPorchFeatures }}</li>

                        <li class="list-group-item"><strong>Fencing:</strong> {{ $property->amenities->Fencing }}</li>

                        <li class="list-group-item"><strong>Other Structures:</strong>
                            {{ $property->amenities->OtherStructures }}</li>

                        <li class="list-group-item"><strong>Building Features:</strong>
                            {{ $property->amenities->BuildingFeatures }}</li>


                        {{-- Parking --}}
                        <li class="list-group-item"><strong>Garage:</strong> Yes</li>

                        <li class="list-group-item"><strong>Attached Garage:</strong> Yes</li>


                        <li class="list-group-item"><strong>Garage Spaces:</strong>
                            {{ $property->amenities->GarageSpaces }}</li>


                        <li class="list-group-item"><strong>Covered Spaces:</strong>
                            {{ $property->amenities->CoveredSpaces }}</li>


                        <li class="list-group-item"><strong>Total Parking:</strong>
                            {{ $property->amenities->ParkingTotal }}</li>

                        <li class="list-group-item"><strong>Open Parking:</strong> Yes</li>

                        <li class="list-group-item"><strong>Parking Features:</strong>
                            {{ $property->amenities->ParkingFeatures }}</li>


                        {{-- Pool / Spa --}}
                        <li class="list-group-item"><strong>Private Pool:</strong>
                            {{ $property->amenities->PoolFeatures ?? 'Yes' }}</li>

                        <li class="list-group-item"><strong>Spa:</strong>
                            {{ $property->amenities->SpaFeatures ?? 'Yes' }}</li>


                        {{-- Community --}}
                        <li class="list-group-item"><strong>HOA:</strong> Yes</li>


                        <li class="list-group-item"><strong>Association Fee:</strong>
                            ${{ number_format($property->amenities->AssociationFee, 2) }}</li>


                        <li class="list-group-item"><strong>Fee Frequency:</strong>
                            {{ $property->amenities->AssociationFeeFrequency }}</li>

                        <li class="list-group-item"><strong>Association Amenities:</strong>
                            {{ $property->amenities->AssociationAmenities }}</li>

                        <li class="list-group-item"><strong>Community Features:</strong>
                            {{ $property->amenities->CommunityFeatures }}</li>

                        <li class="list-group-item"><strong>Senior Community:</strong> Yes</li>


                        <li class="list-group-item"><strong>Number of Units:</strong>
                            {{ $property->amenities->NumberOfUnitsInCommunity }}</li>


                        {{-- Horse-related --}}
                        <li class="list-group-item"><strong>Horse Property:</strong> Yes</li>

                        <li class="list-group-item"><strong>Horse Amenities:</strong>
                            {{ $property->amenities->HorseAmenities }}</li>

                        {{-- Other Amenities --}}
                        <li class="list-group-item"><strong>Utilities:</strong> {{ $property->amenities->Utilities }}
                        </li>

                        <li class="list-group-item"><strong>Other Equipment:</strong>
                            {{ $property->amenities->OtherEquipment }}</li>

                        <li class="list-group-item"><strong>Furnished:</strong> {{ $property->amenities->Furnished }}
                        </li>

                        <li class="list-group-item"><strong>Inclusions:</strong> {{ $property->amenities->Inclusions }}
                        </li>


                    </ul>
                @else
                    <p class="text-muted">No amenities available.</p>
                @endif
            </div>
        </div>

        @if ($property->schools)
            <div class="mt-6 bg-white p-6 rounded shadow">
                <h3 class="text-xl font-semibold mb-4">Nearby Schools</h3>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Elementary School:</strong>
                        {{ $property->schools->ElementarySchool ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Middle/Junior School:</strong>
                        {{ $property->schools->MiddleOrJuniorSchool ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>High School:</strong>
                        {{ $property->schools->HighSchool ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Elementary School District:</strong>
                        {{ $property->schools->ElementarySchoolDistrict ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Middle/Junior School District:</strong>
                        {{ $property->schools->MiddleOrJuniorSchoolDistrict ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>High School District:</strong>
                        {{ $property->schools->HighSchoolDistrict ?? 'N/A' }}</li>
                </ul>
            </div>
        @else
            <div class="mt-6 bg-white p-6 rounded shadow">
                <h3 class="text-xl font-semibold mb-4">Nearby Schools</h3>
                <p class="text-muted">No school information available.</p>
            </div>
        @endif


        @if ($property->financialDetails)
            <div class="mt-6 bg-white p-6 rounded shadow">
                <h3 class="text-xl font-semibold mb-4">Financial Details</h3>

                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Gross Income:</strong>
                        ${{ number_format($property->financialDetails->GrossIncome ?? 0, 2) }}</li>
                    <li><strong>Net Operating Income:</strong>
                        ${{ number_format($property->financialDetails->NetOperatingIncome ?? 0, 2) }}</li>
                    <li><strong>Total Actual Rent:</strong>
                        ${{ number_format($property->financialDetails->TotalActualRent ?? 0, 2) }}</li>
                    <li><strong>Operating Expense:</strong>
                        ${{ number_format($property->financialDetails->OperatingExpense ?? 0, 2) }}</li>
                    <li><strong>Lease Amount:</strong>
                        ${{ number_format($property->financialDetails->LeaseAmount ?? 0, 2) }}</li>
                    <li><strong>Lease Term:</strong> {{ $property->financialDetails->LeaseTerm ?? 'N/A' }}</li>
                    <li><strong>Business Name:</strong> {{ $property->financialDetails->BusinessName ?? 'N/A' }}</li>
                </ul>
            </div>
        @endif

        @if ($property->media->count())
            <div class="mt-6">
                <h3 class="text-xl font-semibold mb-4">Media</h3>
                <div class="media-grid">
                    @foreach ($property->media as $media)
                        <div class="media-card">
                            <img src="{{ $media->url }}" alt="{{ $media->title ?? 'Media' }}">
                            <div class="content">
                                <h4>{{ $media->title ?? 'Untitled' }}</h4>
                                <p>{{ $media->description ?? 'No description available.' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
