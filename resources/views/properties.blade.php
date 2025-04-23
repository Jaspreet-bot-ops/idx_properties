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
                    <input type="text" id="mapbox-search" name="search" placeholder="Search by address, city, postal code..."
                    autocomplete="off"
                    value="{{ request('search') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="mapbox-results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden"></div>
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
                                    <th class="px-4 py-2">Type</th>
                                    <th class="px-4 py-2">Price</th>
                                    <th class="px-4 py-2">Beds</th>
                                    <th class="px-4 py-2">Baths</th>
                                    <th class="px-4 py-2">Area (sqft)</th>
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
                                        <td class="px-4 py-2">
                                            {{ $property->UnparsedAddress }}
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
            const mapboxToken = 'pk.eyJ1IjoiamFzcC1yZWV0IiwiYSI6ImNtOWxiaXluczAyeHUybHIxc2sycHVsNjQifQ.NW350JyVU-z-cMkzgdCrNw';
            
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
                
                if (query.length < 2) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.add('hidden');
                    return;
                }
                
                debounceTimer = setTimeout(() => {
                    // Geocoding API endpoint - focus on places and regions for city/state searches
                    const endpoint = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${mapboxToken}&country=us&types=place,region,district,locality,neighborhood,address,postcode&limit=5`;
                    
                    fetch(endpoint)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            
                            if (data.features && data.features.length > 0) {
                                // Create a heading for Mapbox results
                                const heading = document.createElement('div');
                                heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                heading.textContent = 'Location Suggestions';
                                resultsContainer.appendChild(heading);
                                
                                data.features.forEach(feature => {
                                    // Extract components from the place name
                                    const components = feature.place_name.split(', ');
                                    
                                    const item = document.createElement('div');
                                    item.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                                    
                                    // Format the display to highlight the main part
                                    if (components.length > 1) {
                                        const mainPart = document.createElement('span');
                                        mainPart.className = 'font-medium';
                                        mainPart.textContent = components[0];
                                        
                                        const secondaryPart = document.createElement('span');
                                        secondaryPart.className = 'text-gray-500 ml-1';
                                        secondaryPart.textContent = components.slice(1).join(', ');
                                        
                                        item.appendChild(mainPart);
                                        item.appendChild(secondaryPart);
                                    } else {
                                        item.textContent = feature.place_name;
                                    }
                                    
                                    // Store the full place name as a data attribute
                                    item.dataset.placeName = feature.place_name;
                                    
                                    // Store context data for more precise searching
                                    if (feature.context) {
                                        const contextData = {};
                                        feature.context.forEach(ctx => {
                                            const id = ctx.id.split('.')[0];
                                            contextData[id] = ctx.text;
                                        });
                                        
                                        if (feature.place_type[0] === 'place') {
                                            contextData.city = feature.text;
                                        } else if (feature.place_type[0] === 'region') {
                                            contextData.state = feature.text;
                                        }
                                        
                                        item.dataset.context = JSON.stringify(contextData);
                                    }
                                    
                                    item.addEventListener('click', function() {
                                        searchInput.value = this.dataset.placeName;
                                        resultsContainer.classList.add('hidden');
                                        
                                        // Optional: Submit the form immediately
                                        // searchForm.submit();
                                    });
                                    
                                    resultsContainer.appendChild(item);
                                });
                                
                                // Now fetch property-specific suggestions from your database
                                fetch(`/property-suggestions?q=${encodeURIComponent(query)}`)
                                    .then(response => response.json())
                                    .then(propertyData => {
                                        if (propertyData.length > 0) {
                                            // Add a divider
                                            const divider = document.createElement('div');
                                            divider.className = 'border-t border-gray-200 my-1';
                                            resultsContainer.appendChild(divider);
                                            
                                            // Add a heading for property results
                                            const heading = document.createElement('div');
                                            heading.className = 'p-2 bg-gray-100 font-semibold text-sm';
                                            heading.textContent = 'Properties';
                                            resultsContainer.appendChild(heading);
                                            
                                            propertyData.forEach(property => {
                                                const item = document.createElement('div');
                                                item.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                                                
                                                const address = document.createElement('span');
                                                address.className = 'font-medium';
                                                address.textContent = property.UnparsedAddress;
                                                
                                                const cityState = document.createElement('span');
                                                cityState.className = 'text-gray-500 ml-1';
                                                cityState.textContent = `${property.City}, ${property.StateOrProvince}`;
                                                
                                                item.appendChild(address);
                                                item.appendChild(cityState);
                                                
                                                item.addEventListener('click', function() {
                                                    searchInput.value = `${property.UnparsedAddress}, ${property.City}, ${property.StateOrProvince}`;
                                                    resultsContainer.classList.add('hidden');
                                                    
                                                    // Optional: Submit the form immediately
                                                    // searchForm.submit();
                                                });
                                                
                                                resultsContainer.appendChild(item);
                                            });
                                        }
                                        
                                        resultsContainer.classList.remove('hidden');
                                    })
                                    .catch(error => {
                                        console.error('Error fetching property suggestions:', error);
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
</x-app-layout>

