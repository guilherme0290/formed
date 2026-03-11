@props([
    'title',
    'description' => null,
    'id' => null,
])

<section {{ $attributes->class('cfg-section card border-0 shadow-sm') }} @if($id) id="{{ $id }}" @endif>
    <div class="card-body p-4 p-xl-5">
        <div class="cfg-section__head mb-4">
            <h2 class="h5 mb-1">{{ $title }}</h2>
            @if($description)
                <p class="text-secondary small mb-0">{{ $description }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</section>
