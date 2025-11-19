@props(['logo' => null])
<div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
    @if ($logo)
        <div class="flex justify-center mb-4">{{ $logo }}</div>
    @endif
    {{ $slot }}
</div>
