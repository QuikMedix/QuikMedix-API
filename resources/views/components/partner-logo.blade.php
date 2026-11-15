@props(['image', 'name', 'height' => 40])

{{-- A partner's own logo once its file is in public/; until then the partner's name as text. --}}
@if(is_file(public_path($image)))
<img src="{{ URL::asset('/'.$image) }}" alt="{{ $name }}" height="{{ $height }}" {{ $attributes }}>
@else
<span {{ $attributes->merge(['class' => 'text-black']) }} style="display: inline-block; line-height: {{ $height }}px; font-weight: 600;">{{ $name }}</span>
@endif
