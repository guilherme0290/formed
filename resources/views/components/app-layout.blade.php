@props(['title' => config('app.name', 'Formed'), 'header' => null])

{{-- Encaminha o slot para o layout base --}}
@include('layouts.app', ['title' => $title, 'slot' => $slot, 'header' => $header ?? null])
