@props(['statusId' => null, 'name' => null, 'color' => null])

<span {{ $attributes->merge(['class' => 'qm-order-status']) }} data-tone="{{ $color }}">{{ \App\Support\OrderStatus::label($statusId, $name) }}</span>
