@props([
    'label'        => '',
    'name'         => '',
    'value'        => null, // URL existente o null
    'uuid'         => null, // UUID existente o null
    'required'     => false,
    'allowedMimes' => ['jpg', 'jpeg', 'png', 'webp'],
    'maxSize'      => 5120, // KB
])

@php
    $acceptMimes = '.' . implode(',.', $allowedMimes);
    $dotName = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($dotName);
    
    // Transforma 'photos[0][image]' a 'photos[0][image_url]' y 'photos[0][image_uuid]'
    $urlName = str_contains($name, ']') 
        ? preg_replace('/\]$/', '_url]', $name) 
        : $name . '_url';

    $uuidName = str_contains($name, ']') 
        ? preg_replace('/\]$/', '_uuid]', $name) 
        : $name . '_uuid';
@endphp

<div 
    x-data="imageUploader({
        initialValue: '{{ $value ? asset($value) : '' }}',
        initialUuid: '{{ $uuid ?? '' }}',
        maxSizeKb: {{ $maxSize }},
        hasError: {{ $hasError ? 'true' : 'false' }}
    })"
    class="flex flex-col gap-1.5 mb-4 w-full"
>
    @if($label)
        {{-- La misma etiqueta que el resto del formulario: traía otro tamaño y otro gris. --}}
        <label for="{{ $name }}" class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    {{-- Input oculto exclusivo para la URL de la imagen existente --}}
    <input 
        type="hidden" 
        name="{{ $urlName }}" 
        :value="hiddenUrlValue"
    />

    {{-- Input oculto para el UUID (SIEMPRE se envía si existe) --}}
    <input 
        type="hidden" 
        name="{{ $uuidName }}" 
        :value="hiddenUuidValue"
    />

    {{-- Zona Drag and Drop / Preview --}}
    <div 
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        :class="{
            'border-black bg-gray-50': isDragging,
            'border-red-500 bg-red-50/10': errorMessage || hasError,
            'border-[#929292] hover:border-gray-400': !isDragging && !errorMessage && !hasError
        }"
        class="relative flex flex-col items-center justify-center w-full min-h-[160px] p-4 border-2 border-dashed rounded-xl transition-colors text-center cursor-pointer overflow-hidden group"
        @click="$refs.fileInput.click()"
    >
        {{-- Input File Único para subir archivo nuevo --}}
        <input 
            x-ref="fileInput"
            type="file" 
            name="{{ $name }}"
            id="{{ $name }}"
            accept="{{ $acceptMimes }}"
            class="hidden"
            @change="handleFileSelect($event)"
            {{ $required && !$value ? 'required' : '' }}
        />

        {{-- Vista previa de la imagen --}}
        <template x-if="previewUrl">
            <div class="relative w-full h-full min-h-[140px] flex items-center justify-center">
                <img 
                    :src="previewUrl" 
                    alt="Preview" 
                    class="max-h-40 max-w-full object-contain rounded-lg shadow-sm"
                />
                
                {{-- Overlay para cambiar o eliminar --}}
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                    <span class="text-xs text-white bg-black/60 px-3 py-1.5 rounded-md font-medium">
                        Cambiar imagen
                    </span>
                    <button 
                        type="button"
                        @click.stop="removeImage()"
                        class="p-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                        title="Eliminar imagen"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        {{-- Estado inicial sin imagen --}}
        <template x-if="!previewUrl">
            <div class="flex flex-col items-center justify-center space-y-2">
                <div class="p-3 bg-gray-100 rounded-full text-gray-500 group-hover:text-black transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="text-size-small-heading text-black">
                    <span class="font-semibold text-black">Haz clic para subir</span> o arrastra y suelta
                </div>
                <p class="text-size-small-heading text-gray-400 uppercase tracking-wider">
                    {{ implode(', ', $allowedMimes) }} (Máx. {{ round($maxSize / 1024, 1) }} MB)
                </p>
            </div>
        </template>
    </div>

    {{-- Error local --}}
    <template x-if="errorMessage">
        <p class="text-xs text-red-600 font-medium mt-0.5" x-text="errorMessage"></p>
    </template>

    {{-- Error del Servidor --}}
    @error($dotName)
        <p class="text-xs text-red-600 font-medium mt-0.5">{{ $message }}</p>
    @enderror
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('imageUploader', (config = {}) => ({
        previewUrl: config.initialValue || '',
        hiddenUrlValue: config.initialValue || '',
        hiddenUuidValue: config.initialUuid || '',
        maxSizeKb: config.maxSizeKb || 5120,
        hasError: config.hasError || false,
        isDragging: false,
        errorMessage: '',
        hasNewFile: false,

        handleFileSelect(event) {
            const file = event.target.files[0];
            this.processFile(file);
        },

        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.fileInput.files = dataTransfer.files;
                this.processFile(file);
            }
        },

        processFile(file) {
            this.errorMessage = '';

            if (!file) return;

            if (file.size > this.maxSizeKb * 1024) {
                this.errorMessage = `La imagen supera el límite permitido de ${(this.maxSizeKb / 1024).toFixed(1)} MB.`;
                this.removeImage();
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'El archivo seleccionado debe ser una imagen válida.';
                this.removeImage();
                return;
            }

            this.hasNewFile = true;
            this.hiddenUrlValue = ''; // Vaciamos la URL guardada porque se está subiendo un archivo nuevo

            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        removeImage() {
            this.previewUrl = '';
            this.hiddenUrlValue = ''; // Al eliminar, vaciamos la URL (pero conservamos hiddenUuidValue)
            this.hasNewFile = false;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    }));
});
</script>
@endpush
@endonce