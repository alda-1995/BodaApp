@props([
    'type' => 'text',
    'variant' => 'primary',
    'hasError' => false,
])

@php
    $inputBase = 'mb-4 border rounded-md px-4 py-1 h-[50px] text-size-small-heading font-inter w-full';

    $variants = [
        'primary' => 'border-base-gray text-black',
    ];

    $inputStyles = $hasError 
        ? 'border-red-500 bg-white text-red-900 focus:border-red-500 focus:ring-red-200' 
        : ($variants[$variant] ?? $variants['primary']);
@endphp

<input
    {{ $attributes->merge([
        'type' => $type,
        'class' => "{$inputBase} {$inputStyles}"
    ]) }}
/>