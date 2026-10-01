@if(session('error'))
    <div class="p-4 mb-4 bg-red-100 text-red-800 rounded-lg">
        {{ session('error') }}
    </div>
@endif