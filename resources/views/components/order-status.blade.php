@props(['name' => null, 'color' => null])

<span {{ $attributes->merge(['class' => 'qm-order-status']) }} data-tone="{{ $color }}">{{ trim((string) $name) !== '' ? $name : 'Unknown status' }}</span>
