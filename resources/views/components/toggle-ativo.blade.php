@props([
    'name' => 'ativo',
    'checked' => false,
    'onLabel' => 'Ativo',
    'offLabel' => 'Inativo',
    'id' => null,
    'labelId' => null,
    'labelClass' => '',
    'textClass' => 'text-sm text-slate-700',
    'inputClass' => '',
])

@php
    $inputId = $id ?? ($name.'-toggle-'.uniqid());
@endphp

<input type="hidden" name="{{ $name }}" value="0">
<label for="{{ $inputId }}" class="inline-flex items-center gap-3 cursor-pointer select-none {{ $labelClass }}">
    <input id="{{ $inputId }}"
           type="checkbox"
           name="{{ $name }}"
           value="1"
           {{ $attributes->merge(['class' => 'sr-only peer '.$inputClass]) }}
        {{ $checked ? 'checked' : '' }}>
    <span class="relative h-6 w-11 rounded-full bg-rose-500 transition peer-checked:bg-emerald-600
                 after:content-[''] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition
                 peer-checked:after:translate-x-5"></span>
    @if ($labelId)
        <span id="{{ $labelId }}" class="{{ $textClass }}">
            {{ $checked ? $onLabel : $offLabel }}
        </span>
    @else
        <span class="{{ $textClass }} peer-checked:hidden">{{ $offLabel }}</span>
        <span class="{{ $textClass }} hidden peer-checked:inline">{{ $onLabel }}</span>
    @endif
</label>
