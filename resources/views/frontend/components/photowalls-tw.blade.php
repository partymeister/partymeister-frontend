@section('view-styles')
    <style type="text/css">
        .img-thumbnail {
            margin-bottom: 15px;
        }
    </style>
@endsection


<h3>Photowall</h3>

@include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach ($photos as $photo)
        <div>
            <a href="/photowall/cache/{{$photo}}" data-caption="Photowall image" data-fancybox="gallery">
                <img src="/photowall/cache/{{$photo}}" alt="Photo" class="rounded-lg shadow w-full">
            </a>
        </div>
    @endforeach
</div>
@include('partymeister-frontend::frontend.components.photowalls-pagination-tw')
