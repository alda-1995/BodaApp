@props([
    'label' => '',
    'help' => '',
    'name' => '',
    'current' => null,       // Lo que ya se está usando, para la vista previa
    'multiple' => false,
    'required' => false,
    'allowedMimes' => ['jpg', 'jpeg', 'png', 'webp'],
    'maxSize' => 5120,       // KB
])

{{--
    Caja de subida: arrastrar y soltar, vista previa y los avisos de tamaño y
    formato antes de mandar nada al servidor.

    Es sólo la caja. No sabe de archivos guardados ni de uuids: quien la usa
    decide qué hace con lo que se suba. El control del wizard
    (controls.inputs.image-upload) añade encima lo suyo.
--}}
@php
    $inputName = $multiple ? $name . '[]' : $name;
    $inputId = 'drop-' . str_replace(['[', ']', '.'], '-', $name);
    $accept = '.' . implode(',.', $allowedMimes);
    $hasError = $errors->has($name);
@endphp

<div class="flex w-full flex-col gap-1.5"
    x-data="fileDrop({
        current: '{{ $current }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        maxSizeKb: {{ $maxSize }},
    })">

    @if ($label)
        <label for="{{ $inputId }}" class="text-size-small-heading text-black">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)" @click="$refs.input.click()"
        :class="{
            'border-black bg-gray-50': isDragging,
            'border-red-500 bg-red-50/10': errorMessage || {{ $hasError ? 'true' : 'false' }},
            'border-[#929292] hover:border-gray-400': !isDragging && !errorMessage,
        }"
        class="group relative flex min-h-[160px] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed p-4 text-center transition-colors">

        <input x-ref="input" type="file" name="{{ $inputName }}" id="{{ $inputId }}" accept="{{ $accept }}"
            class="hidden" @if ($multiple) multiple @endif @change="handleSelect($event)">

        {{-- Lo que se subirá, o lo que ya se está usando. --}}
        <template x-if="previews.length">
            <div class="flex w-full flex-wrap items-center justify-center gap-2">
                <template x-for="(preview, index) in previews.slice(0, 6)" :key="index">
                    <img :src="preview" alt="" class="max-h-28 rounded-lg object-contain shadow-sm">
                </template>

                <template x-if="previews.length > 6">
                    <span class="text-size-small-heading text-[#737373]"
                        x-text="'+' + (previews.length - 6) + ' más'"></span>
                </template>
            </div>
        </template>

        <template x-if="!previews.length">
            <div class="flex flex-col items-center justify-center space-y-2">
                <div class="rounded-full bg-gray-100 p-3 text-gray-500 transition-colors group-hover:text-black">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="text-size-small-heading text-black">
                    <span class="font-semibold">Haz clic para subir</span> o arrastra y suelta
                </div>
                <p class="text-size-small-heading uppercase tracking-wider text-gray-400">
                    {{ implode(', ', $allowedMimes) }}
                    (máx. {{ round($maxSize / 1024, 1) }} MB{{ $multiple ? ' por archivo' : '' }})
                </p>
            </div>
        </template>

        {{-- Cuántos se seleccionaron, útil cuando son muchos. --}}
        <template x-if="fileCount">
            <p class="mt-3 text-size-small-heading text-[#737373]"
                x-text="fileCount === 1 ? '1 archivo seleccionado' : fileCount + ' archivos seleccionados'"></p>
        </template>
    </div>

    @if ($help)
        <p class="text-size-small-heading text-[#808080]">{{ $help }}</p>
    @endif

    <template x-if="errorMessage">
        <p class="text-size-small-heading text-red-600" x-text="errorMessage"></p>
    </template>

    @error($name)
        <p class="text-size-small-heading text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('fileDrop', (config = {}) => ({
                    previews: config.current ? [config.current] : [],
                    multiple: config.multiple || false,
                    maxSizeKb: config.maxSizeKb || 5120,
                    isDragging: false,
                    errorMessage: '',
                    fileCount: 0,

                    handleSelect(event) {
                        this.processFiles([...event.target.files]);
                    },

                    handleDrop(event) {
                        this.isDragging = false;

                        const files = [...event.dataTransfer.files];

                        if (!files.length) {
                            return;
                        }

                        // Se pasan al input para que viajen con el formulario.
                        const transfer = new DataTransfer();
                        files.forEach((file) => transfer.items.add(file));
                        this.$refs.input.files = transfer.files;

                        this.processFiles(files);
                    },

                    processFiles(files) {
                        this.errorMessage = '';

                        if (!files.length) {
                            return;
                        }

                        const tooBig = files.find((file) => file.size > this.maxSizeKb * 1024);

                        if (tooBig) {
                            this.errorMessage = `"${tooBig.name}" supera el límite de ${(this.maxSizeKb / 1024).toFixed(1)} MB.`;
                            this.reset();
                            return;
                        }

                        const notImage = files.find((file) => !file.type.startsWith('image/'));

                        if (notImage) {
                            this.errorMessage = `"${notImage.name}" no es una imagen.`;
                            this.reset();
                            return;
                        }

                        this.fileCount = files.length;
                        this.previews = [];

                        // Sólo se dibujan las primeras: con 36 cuadros no hace
                        // falta leerlos todos para saber que están ahí.
                        files.slice(0, 6).forEach((file) => {
                            const reader = new FileReader();
                            reader.onload = (event) => this.previews.push(event.target.result);
                            reader.readAsDataURL(file);
                        });
                    },

                    reset() {
                        this.previews = [];
                        this.fileCount = 0;

                        if (this.$refs.input) {
                            this.$refs.input.value = '';
                        }
                    },
                }));
            });
        </script>
    @endpush
@endonce
