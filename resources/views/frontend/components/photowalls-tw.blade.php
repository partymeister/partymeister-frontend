<h3>Photowall</h3>

@include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
<div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    @foreach ($photos as $photo)
        <div>
            <a href="/photowall/full/{{$photo}}" data-caption="Photowall image" data-fancybox="gallery">
                <img src="/photowall/thumb/{{$photo}}" alt="Photo" class="rounded-lg shadow w-full" loading="lazy">
            </a>
        </div>
    @endforeach
</div>
@include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
