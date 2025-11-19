{{-- resources/views/components/card-metric.blade.php --}}
@props(['label','value'=>0])
<div class="bg-white rounded-xl border p-4 shadow-sm text-center">
    <div class="text-sm text-gray-500">{{ $label }}</div>
    <div class="text-3xl font-semibold">{{ $value }}</div>
</div>
