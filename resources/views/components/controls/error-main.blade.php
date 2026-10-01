@props([
    'field' => 'general',
])

@if ($errors->has($field))
    <div {{ $attributes->merge([
            'class' => 'bg-red-100 border border-red-400 text-red-700
                        px-4 py-3 rounded mb-4'
        ]) }}>
        <strong>Error:</strong> {{ $errors->first($field) }}
    </div>
@endif