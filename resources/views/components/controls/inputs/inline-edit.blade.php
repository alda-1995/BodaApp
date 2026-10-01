@props([
    'label' => '',
    'name' => '',
    'dotName' => '',
    'itemKey' => '',
    'id' => null,
    'placeholder' => '',
    'required' => false,
    'multiline' => false,
])

@php
    $fieldId = $id ?: Str::slug($name);
    $errorKey = $dotName ?: $name;
    $hasError = filled($errorKey) ? $errors->has($errorKey) : false;
    $key = Js::from($itemKey);
@endphp

{{--
    Editor de un campo inline: se muestra en el panel que se abre debajo de la fila,
    a todo el ancho, para que en móvil no quede apretado junto a los botones.
    El valor vive en el repeaterRow padre (item), y el textarea es el campo real
    que se envía: sigue en el DOM aunque el panel esté cerrado.
--}}
<div class="flex flex-col gap-1.5 w-full">
    <label for="{{ $fieldId }}" class="font-inter text-size-small-heading text-black">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    {{-- Sin [required] en el HTML: el panel puede estar oculto y un required
         invisible bloquea el submit nativo. La obligatoriedad la valida el servidor. --}}
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        x-model="item[{{ $key }}]"
        rows="{{ $multiline ? 4 : 2 }}"
        @unless($multiline)
            {{-- El título es de una sola línea: Enter no mete saltos. --}}
            @keydown.enter.prevent
        @endunless
        @keydown.escape.prevent="cancelEdit()"
        placeholder="{{ $placeholder }}"
        @class([
            'w-full resize-y rounded-md border px-3 py-2 bg-white font-inter text-size-small-heading outline-none focus:border-black',
            'border-red-500 text-red-900' => $hasError,
            'border-base-gray text-black' => !$hasError,
        ])
    ></textarea>

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter block">{{ $message }}</span>
    @enderror
</div>
