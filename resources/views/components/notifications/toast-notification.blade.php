<div id="toast-container" class="fixed top-[5%] right-[5%] z-50 space-y-3">
    {{ $slot }}

    @if (session('success'))
        <x-ui.message-notification type="success" :message="session('success')" />
    @endif

    @if (session('warning'))
        <x-ui.message-notification type="warning" :message="session('warning')" />
    @endif

    @if (session('error'))
        <x-ui.message-notification type="error" :message="session('error')" />
    @endif

    @if ($errors->any())
        @if ($errors->count() === 1)
            <x-ui.message-notification type="error" :message="$errors->first()" />
        @else
            <x-ui.message-notification type="error" :messages="$errors->all()" />
        @endif
    @endif
</div>