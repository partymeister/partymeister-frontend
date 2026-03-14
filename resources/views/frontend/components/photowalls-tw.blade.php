<h3 class="mb-4">Photowall</h3>

@if (count($photos) === 0)
    <div class="rounded-lg border border-accent/40 border-l-4 border-l-accent bg-accent/15 px-4 py-3 text-accent mt-4">
        No photos yet — check back soon!
    </div>
@else
    @include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach ($photos as $photo)
            <div>
                <a href="/photowall/full/{{$photo}}" data-caption="Photowall image" data-fancybox="gallery" class="hover:opacity-90 transition-opacity">
                    <img src="/photowall/thumb/{{$photo}}" alt="Photo" class="rounded-lg shadow-[0_4px_12px_rgba(0,0,0,0.4)] w-full" loading="lazy">
                </a>
            </div>
        @endforeach
    </div>
    @include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
@endif
