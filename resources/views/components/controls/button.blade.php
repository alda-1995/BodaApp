@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'main',
    'as' => null,
])

@php
    $palette = [
        'main'        => 'btn-main',
        'primary'     => 'btn-primary',
        'secondary'   => 'btn-secondary',
        'cancel'      => 'btn-cancel',
        'remove'      => 'btn-remove',
        'remove-text' => 'btn-remove-text'
    ][$variant] ?? '';

    $isLink = $href || $as === 'a' || $attributes->has(':href') || $attributes->has('x-bind:href');
@endphp

@if ($isLink)
    <a @if($href) href="{{ $href }}" @endif
       {{ $attributes->merge(['class' => "btn parrafo $palette"]) }}>
        <span class="truncate">{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => "btn parrafo $palette"]) }}>
        <span class="truncate">{{ $slot }}</span>
    </button>
@endif