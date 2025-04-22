<table class="min-w-full text-sm text-left text-gray-600">
    <thead class="bg-gray-100 text-xs uppercase text-gray-700">
        <tr>
            <th class="px-4 py-2">Listing Key----------------</th>
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
