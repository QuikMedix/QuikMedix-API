@props(['target', 'label' => 'password'])

<div class="input-group-append">
    <button type="button" class="btn btn-outline-secondary" data-password-toggle
        data-show-label="Show {{ $label }}" data-hide-label="Hide {{ $label }}"
        aria-controls="{{ $target }}" aria-label="Show {{ $label }}" aria-pressed="false"
        title="Show {{ $label }}">
        <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
    </button>
</div>
