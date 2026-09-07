@if($documentPath = \App\Support\Branding::documentPath($document))
    <img src="{{ asset($documentPath) }}" alt="{{ $description }}" style="{{ $imageStyle ?? 'width: 100%; height: auto;' }}">
@else
    <div data-document-unavailable="{{ $document }}" role="status" style="position: relative; z-index: 10; box-sizing: border-box; padding: 24px; background: white; color: #2a3142; border: 1px solid #ddd; {{ $imageStyle ?? '' }}">
        {{ $description }} is currently unavailable. Please contact QuikMedix support.
    </div>
@endif
