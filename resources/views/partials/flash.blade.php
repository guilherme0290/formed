@if(session('success'))
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
        <ul class="list-disc ml-5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
