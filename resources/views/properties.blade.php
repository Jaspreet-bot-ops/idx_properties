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
                <input type="text" name="search" placeholder="Search by address, city, postal code..."
                    value="{{ request('search') }}"
                    class="flex-grow px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
</x-app-layout>
