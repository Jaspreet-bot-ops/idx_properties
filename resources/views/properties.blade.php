{{-- <x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Properties</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search Form -->
        <div class="mb-6">
            <form id="property-search-form" action="{{ route('properties') }}" method="GET"
                class="flex flex-col sm:flex-row gap-2">
                <div class="flex-grow relative">
                    <input type="text" id="mapbox-search" name="search"
                        placeholder="Search by address, city, postal code..." autocomplete="off"
                        value="{{ request('search') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="mapbox-results"
                        class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden"></div>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Search
                </button>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="p-4">
                @if ($properties->count())
                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm text-left text-gray-600">
                            <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                                <tr>
                                    <th class="px-4 py-2">Listing Key</th>
                                    <th class="px-4 py-2">
                                        <a href="{{ route('properties', array_merge(request()->query(), ['sort_by' => 'PropertyType', 'sort_direction' => request('sort_by') == 'PropertyType' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-gray-700 hover:underline">
                                            Type
                                            @if (request('sort_by') == 'PropertyType')
                                                @if (request('sort_direction') == 'asc')
                                                    <span>&#x2191;</span> <!-- Ascending arrow -->
                                                @else
                                                    <span>&#x2193;</span> <!-- Descending arrow -->
                                                @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2">Price</th>
                                    <th class="px-4 py-2">Beds</th>
                                    <th class="px-4 py-2">Baths</th>
                                    <th class="px-4 py-2">Area (sqft)</th>
                                    <th class="px-4 py-2">
                                        <a href="{{ route('properties', array_merge(request()->query(), ['sort_by' => 'UnitNumber', 'sort_direction' => request('sort_by') == 'UnitNumber' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-gray-700 hover:underline">
                                            Unit Number
                                            @if (request('sort_by') == 'UnitNumber')
                                                @if (request('sort_direction') == 'asc')
                                                    <span>&#x2191;</span> <!-- Ascending arrow -->
                                                @else
                                                    <span>&#x2193;</span> <!-- Descending arrow -->
                                                @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2">Address</th>
                                    <th class="px-4 py-2">City</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Agent</th>
                                    <th class="px-4 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($properties as $property)
                                    <tr>
                                        <td class="px-4 py-2">{{ $property->ListingKey }}</td>
                                        <td class="px-4 py-2">{{ $property->PropertyType }}</td>
                                        <td class="px-4 py-2">${{ number_format($property->ListPrice, 2) }}</td>
                                        <td class="px-4 py-2">{{ $property->BedroomsTotal }}</td>
                                        <td class="px-4 py-2">
                                            {{ $property->BathroomsFull }}
                                            {{ $property->BathroomsHalf ? '½' : '' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $property->LivingArea }}</td>
                                        <td class="px-4 py-2">{{ $property->UnitNumber }}</td>
                                        <td class="px-4 py-2">
                                            {{ $property->StreetNumber ?? '' }} {{ $property->StreetName ?? '' }}
                                            {{ $property->StreetSuffix ? $property->StreetSuffix . ',' : '' }}
                                            {{ $property->City ? $property->City . ',' : '' }} {{ $property->StateOrProvince ?? '' }}
                                            {{ $property->PostalCode ?? '' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $property->City }}</td>
                                        <td class="px-4 py-2">{{ $property->StandardStatus }}</td>
                                        <td class="px-4 py-2">{{ $property->ListAgentFullName }}</td>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('properties.show', $property->id) }}"
                                                class="text-blue-600 hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $properties->appends(request()->query())->links() }}
                    </div>
                @else
                    <p class="text-gray-500">No properties found.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Mapbox Scripts -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Replace with your Mapbox access token
            const mapboxToken =
                'pk.eyJ1IjoiamFzcC1yZWV0IiwiYSI6ImNtOWxiaXluczAyeHUybHIxc2sycHVsNjQifQ.NW350JyVU-z-cMkzgdCrNw';

            if (!mapboxToken) {
                console.error('Mapbox access token is not set');
                return;
            }

            const searchInput = document.getElementById('mapbox-search');
            const resultsContainer = document.getElementById('mapbox-results');
            const searchForm = document.getElementById('property-search-form');

            let debounceTimer;

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);

                const query = this.value;

                if (query.length < 1) { // Changed from 2 to 1
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    // Geocoding API endpoint - focus on places and regions for city/state searches
                    const endpoint =
                        `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${mapboxToken}&country=us&types=place,region,district,locality,neighborhood,address,postcode&limit=5`;

                    fetch(endpoint)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';

                            if (data.features && data.features.length > 0) {
                                fetch(`/property-suggestions?q=${encodeURIComponent(query)}`)
                                    .then(response => response.json())
                                    .then(propertyData => {
                                        if (propertyData.length > 0) {
                                            // Add a divider
                                            const divider = document.createElement('div');
                                            divider.className =
                                                'border-t border-gray-200 my-1';
                                            resultsContainer.appendChild(divider);

                                            // Add a heading for property results
                                            const heading = document.createElement('div');
                                            heading.className =
                                                'p-2 bg-gray-100 font-semibold text-sm';
                                            heading.textContent = 'Properties';
                                            resultsContainer.appendChild(heading);

                                            propertyData.forEach(property => {
                                                const item = document.createElement(
                                                    'div');
                                                item.className =
                                                    'p-2 hover:bg-gray-100 cursor-pointer';

                                                const address = document
                                                    .createElement('span');
                                                address.className = 'font-medium';
                                                address.textContent = property
                                                    .UnparsedAddress;

                                                const cityState = document
                                                    .createElement('span');
                                                cityState.className =
                                                    'text-gray-500 ml-1';
                                                cityState.textContent =
                                                    `${property.City}, ${property.StateOrProvince}`;

                                                item.appendChild(address);
                                                item.appendChild(cityState);

                                                item.addEventListener('click',
                                                    function() {
                                                        searchInput.value =
                                                            `${property.UnparsedAddress}, ${property.City}, ${property.StateOrProvince}`;
                                                        resultsContainer
                                                            .classList.add(
                                                                'hidden');

                                                        // Optional: Submit the form immediately
                                                        // searchForm.submit();
                                                    });

                                                resultsContainer.appendChild(item);
                                            });
                                        }

                                        resultsContainer.classList.remove('hidden');
                                    })
                                    .catch(error => {
                                        console.error(
                                            'Error fetching property suggestions:',
                                            error);
                                        resultsContainer.classList.remove('hidden');
                                    });
                            } else {
                                resultsContainer.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching Mapbox data:', error);
                        });
                }, 300);
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });

            // Show results when focusing on input if there's content
            searchInput.addEventListener('focus', function() {
                if (this.value.length >= 2 && resultsContainer.children.length > 0) {
                    resultsContainer.classList.remove('hidden');
                }
            });
        });
    </script>
</x-app-layout> --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Properties</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Search Form -->
        <div class="mb-6">
            <form id="property-search-form" action="{{ route('properties') }}" method="GET"
                class="flex flex-col sm:flex-row gap-2">
                <div class="flex-grow relative">
                    <input type="text" id="mapbox-search" name="search"
                        placeholder="Search by address, city, postal code..." autocomplete="off"
                        value="{{ request('search') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="mapbox-results"
                        class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden"></div>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Search
                </button>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="p-4">
                @if ($properties->count())
                    <div class="overflow-x-auto">

                        <table class="min-w-full text-sm text-left text-gray-600">
                            <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                                <tr>
                                    <th class="px-4 py-2">Listing Key</th>
                                    <th class="px-4 py-2">
                                        <a href="{{ route('properties', array_merge(request()->query(), ['sort_by' => 'property_type', 'sort_direction' => request('sort_by') == 'property_type' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-gray-700 hover:underline">
                                            Type
                                            @if (request('sort_by') == 'property_type')
                                                @if (request('sort_direction') == 'asc')
                                                    <span>&#x2191;</span> <!-- Ascending arrow -->
                                                @else
                                                    <span>&#x2193;</span> <!-- Descending arrow -->
                                                @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2">Price</th>
                                    <th class="px-4 py-2">Beds</th>
                                    <th class="px-4 py-2">Baths</th>
                                    <th class="px-4 py-2">Area (sqft)</th>
                                    <th class="px-4 py-2">
                                        <a href="{{ route('properties', array_merge(request()->query(), ['sort_by' => 'unit_number', 'sort_direction' => request('sort_by') == 'unit_number' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-gray-700 hover:underline">
                                            Unit Number
                                            @if (request('sort_by') == 'unit_number')
                                                @if (request('sort_direction') == 'asc')
                                                    <span>&#x2191;</span> <!-- Ascending arrow -->
                                                @else
                                                    <span>&#x2193;</span> <!-- Descending arrow -->
                                                @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2">Address</th>
                                    <th class="px-4 py-2">City</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Agent</th>
                                    <th class="px-4 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($properties as $property)
                                    <tr>
                                        <td class="px-4 py-2">{{ $property->listing_key }}</td>
                                        <td class="px-4 py-2">{{ $property->property_type }}</td>
                                        <td class="px-4 py-2">${{ number_format($property->list_price, 2) }}</td>
                                        <td class="px-4 py-2">{{ $property->bedrooms_total }}</td>
                                        <td class="px-4 py-2">
                                            {{ $property->bathrooms_total_integer }}
                                        </td>
                                        <td class="px-4 py-2">{{ $property->living_area }}</td>
                                        <td class="px-4 py-2">{{ $property->unit_number }}</td>
                                        <td class="px-4 py-2">
                                            {{ $property->street_number ?? '' }} {{ $property->street_name ?? '' }}
                                            {{ $property->street_suffix ? $property->street_suffix . ',' : '' }}
                                            {{ $property->city ? $property->city . ',' : '' }}
                                            {{ $property->state_or_province ?? '' }}
                                            {{ $property->postal_code ?? '' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $property->city }}</td>
                                        <td class="px-4 py-2">{{ $property->standard_status }}</td>
                                        <td class="px-4 py-2">
                                            @if ($property->listAgent)
                                                {{ $property->listAgent->first_name }}
                                                {{ $property->listAgent->last_name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('properties.show', $property->id) }}"
                                                class="text-blue-600 hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $properties->appends(request()->query())->links() }}
                    </div>
                @else
                    <p class="text-gray-500">No properties found.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Mapbox Scripts -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Replace with your Mapbox access token
            const mapboxToken =
                'pk.eyJ1IjoiamFzcC1yZWV0IiwiYSI6ImNtOWxiaXluczAyeHUybHIxc2sycHVsNjQifQ.NW350JyVU-z-cMkzgdCrNw';

            if (!mapboxToken) {
                console.error('Mapbox access token is not set');
                return;
            }

            const searchInput = document.getElementById('mapbox-search');
            const resultsContainer = document.getElementById('mapbox-results');
            const searchForm = document.getElementById('property-search-form');

            let debounceTimer;

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);

                const query = this.value;

                if (query.length < 1) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    // Fetch property suggestions from our API
                    fetch(`/property-suggestions?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';

                            if (data.length > 0) {
                                // Group results by type
                                const properties = data.filter(item => item.type ===
                                    'property');
                                const cities = data.filter(item => item.type === 'city');
                                const states = data.filter(item => item.type === 'state');
                                const postalCodes = data.filter(item => item.type ===
                                    'postal_code');

                                // Add properties section
                                if (properties.length > 0) {
                                    const heading = document.createElement('div');
                                    heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                    heading.textContent = 'Properties';
                                    resultsContainer.appendChild(heading);

                                    properties.forEach(property => {
                                        const item = document.createElement('div');
                                        item.className =
                                            'p-2 hover:bg-gray-100 cursor-pointer';

                                        const address = document.createElement('span');
                                        address.className = 'font-medium';
                                        address.textContent = property.unparsed_address;

                                        const cityState = document.createElement(
                                            'span');
                                        cityState.className = 'text-gray-500 ml-1';
                                        cityState.textContent =
                                            `${property.street_name} ${property.street_number}, ${property.city}, ${property.state_or_province}`;

                                        item.appendChild(address);
                                        item.appendChild(cityState);

                                        // For property suggestions
                                        item.addEventListener('click', function() {
                                            // Display the full address information in the search input
                                            searchInput.value =
                                                `${property.label}`;
                                            resultsContainer.classList.add(
                                                'hidden');

                                            // Add a hidden input to the form to pass the property ID for exact matching
                                            let hiddenInput = document
                                                .createElement('input');
                                            hiddenInput.type = 'hidden';
                                            hiddenInput.name = 'property_id';
                                            hiddenInput.value = property.id;
                                            searchForm.appendChild(hiddenInput);

                                            // Submit the form immediately
                                            searchForm.submit();
                                        });


                                        resultsContainer.appendChild(item);
                                    });
                                }

                                // Add cities section
                                if (cities.length > 0) {
                                    const divider = document.createElement('div');
                                    divider.className = 'border-t border-gray-200 my-1';
                                    resultsContainer.appendChild(divider);

                                    const heading = document.createElement('div');
                                    heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                    heading.textContent = 'Cities';
                                    resultsContainer.appendChild(heading);

                                    cities.forEach(city => {
                                        const item = document.createElement('div');
                                        item.className =
                                            'p-2 hover:bg-gray-100 cursor-pointer';

                                        item.dataset.searchType = 'city';
                                        item.dataset.exactCity = city.city;
                                        item.dataset.state = city.state_or_province;
                                        const cityName = document.createElement('span');
                                        cityName.className = 'font-medium';
                                        cityName.textContent = city.city;

                                        const state = document.createElement('span');
                                        state.className = 'text-gray-500 ml-1';
                                        state.textContent = city.state_or_province;

                                        item.appendChild(cityName);
                                        item.appendChild(state);

                                        item.addEventListener('click', function() {
                                            // Set the search input to the exact city name with state
                                            searchInput.value =
                                                `${this.dataset.exactCity}, ${this.dataset.state}`;
                                            resultsContainer.classList.add(
                                                'hidden');
                                            // Submit the form immediately to show filtered results
                                            searchForm.submit();
                                        });

                                        resultsContainer.appendChild(item);
                                    });
                                }

                                // Add states section
                                if (states.length > 0) {
                                    const divider = document.createElement('div');
                                    divider.className = 'border-t border-gray-200 my-1';
                                    resultsContainer.appendChild(divider);

                                    const heading = document.createElement('div');
                                    heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                    heading.textContent = 'States';
                                    resultsContainer.appendChild(heading);

                                    states.forEach(state => {
                                        const item = document.createElement('div');
                                        item.className =
                                            'p-2 hover:bg-gray-100 cursor-pointer';

                                        const stateName = document.createElement(
                                            'span');
                                        stateName.className = 'font-medium';
                                        stateName.textContent = state.state_or_province;

                                        item.appendChild(stateName);

                                        item.addEventListener('click', function() {
                                            searchInput.value = state.label;
                                            resultsContainer.classList.add(
                                                'hidden');
                                            // Submit the form immediately to show filtered results
                                            searchForm.submit();
                                        });

                                        resultsContainer.appendChild(item);
                                    });
                                }

                                // Add postal codes section
                                if (postalCodes.length > 0) {
                                    const divider = document.createElement('div');
                                    divider.className = 'border-t border-gray-200 my-1';
                                    resultsContainer.appendChild(divider);

                                    const heading = document.createElement('div');
                                    heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                    heading.textContent = 'Postal Codes';
                                    resultsContainer.appendChild(heading);

                                    postalCodes.forEach(postalCode => {
                                        const item = document.createElement('div');
                                        item.className =
                                            'p-2 hover:bg-gray-100 cursor-pointer';

                                        const code = document.createElement('span');
                                        code.className = 'font-medium';
                                        code.textContent = postalCode.postal_code;

                                        const state = document.createElement('span');
                                        state.className = 'text-gray-500 ml-1';
                                        state.textContent = postalCode
                                            .state_or_province;

                                        item.appendChild(code);
                                        item.appendChild(state);

                                        item.addEventListener('click', function() {
                                            searchInput.value = postalCode
                                                .postal_code;
                                            resultsContainer.classList.add(
                                                'hidden');
                                            // Submit the form immediately to show filtered results
                                            searchForm.submit();
                                        });
                                        resultsContainer.appendChild(item);
                                    });
                                }

                                resultsContainer.classList.remove('hidden');
                            } else {
                                resultsContainer.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching property suggestions:', error);
                        });
                }, 300);
            });
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });

            // Show results when focusing on input if there's content
            searchInput.addEventListener('focus', function() {
                if (this.value.length >= 2 && resultsContainer.children.length > 0) {
                    resultsContainer.classList.remove('hidden');
                }
            });
        });
    </script>
</x-app-layout>
