<div class="card p-0 overflow-hidden">
    <div class="flex items-center justify-between p-4 border-b">
        <h2>Low stock</h2>
        <span class="text-2xs text-gray-600">At or below {{ $threshold }}</span>
    </div>

    @if ($products->isEmpty())
        <p class="p-4 text-sm text-gray-600">Nothing is low on stock.</p>
    @else
        <ul>
            @foreach ($products as $product)
                <li class="flex items-center justify-between px-4 py-3 border-b last:border-b-0">
                    <a href="{{ $product['edit_url'] }}" class="text-blue hover:text-black">{{ $product['title'] }}</a>
                    <span class="font-medium {{ $product['on_hand'] <= 0 ? 'text-red-500' : 'text-gray-800' }}">{{ $product['on_hand'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
