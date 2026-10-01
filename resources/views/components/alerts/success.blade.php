@if(session('success'))
    <div class="p-4 mb-4 bg-green-100 text-green-800 rounded-lg">
        {{ session('success') }}
    </div>
@endif