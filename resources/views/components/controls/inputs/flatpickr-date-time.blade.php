@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'value' => '',
    'required' => false,
    'disabled' => false,
    'placeholder' => 'Seleccionar fecha y hora...',
    'variant' => 'primary',
    'format' => 'd/m/Y H:i',
    'dateFormat' => 'Y-m-d H:i:s',
    'dotName' => '',
])

@php
    $id = $id ?? $name;
    // Dentro de un repeater el name viaja como 'events[0][date]', pero old() y
    // @error se consultan con 'events.0.date'.
    $errorKey = $dotName ?: $name;
    $hasError = $errors->has($errorKey);

    $rawValue = old($errorKey, $value);
    
    if ($rawValue instanceof \DateTimeInterface) {
        $rawValue = $rawValue->format('Y-m-d H:i:s');
    } elseif (is_string($rawValue) && !empty($rawValue)) {
        $cleanDate = preg_replace('/(\d{2}:\d{2}:)(\d)$/', '${1}0${2}', trim($rawValue));
        $time = strtotime($cleanDate);
        $rawValue = $time ? date('Y-m-d H:i:s', $time) : $rawValue;
    }

    $inputBase = 'mb-4 border rounded-md px-4 py-1 h-[50px] text-size-small-heading font-inter w-full';

    $variants = [
        'primary' => 'border-base-gray text-black',
    ];

    $inputStyles = $hasError 
        ? 'border-red-500 bg-white text-red-900 focus:border-red-500 focus:ring-red-200' 
        : ($variants[$variant] ?? $variants['primary']);
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if(filled($label))
        <label for="{{ $id }}" class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    <input 
        {{ $attributes->merge(['class' => "{$inputBase} {$inputStyles}"]) }}
        x-data="{ fp: null }"
        x-init="
            $nextTick(() => {
                if (typeof flatpickr === 'undefined') return;

                const inputEl = $el;
                const rawVal = inputEl.value ? inputEl.value.trim() : '';

                const config = {
                    enableTime: true,
                    dateFormat: '{{ $dateFormat }}',
                    altInput: true,
                    altFormat: '{{ $format }}',
                    time_24hr: true,
                    allowInput: true,
                    onChange: function(selectedDates, dateStr) {
                        inputEl.value = dateStr;
                        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                };

                if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.es) {
                    config.locale = window.flatpickr.l10ns.es;
                }

                if (rawVal !== '') {
                    config.defaultDate = rawVal;
                }

                fp = flatpickr(inputEl, config);

                if (inputEl.hasAttribute('x-model') || inputEl.hasAttribute('x-bind:value')) {
                    $watch('$el.value', (newVal) => {
                        if (newVal && fp) {
                            fp.setDate(newVal, false);
                        }
                    });
                }
            });
        "
        type="text" 
        id="{{ $id }}" 
        name="{{ $name }}" 
        value="{{ $rawValue }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
    />

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>