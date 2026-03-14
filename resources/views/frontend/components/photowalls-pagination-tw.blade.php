<nav aria-label="Pagination" class="flex justify-center my-4">
    <div class="join">
        @if ($currentPage > 1)
            <a href="{{\Request::url()}}?page={{$currentPage-1}}" aria-label="Previous page" class="join-item btn btn-sm">Previous</a>
        @else
            <button class="join-item btn btn-sm btn-disabled" disabled>Previous</button>
        @endif
        @for ($i=1; $i<=$pages; $i++)
            @if ($currentPage == $i)
                <button class="join-item btn btn-sm btn-active">{{$i}}</button>
            @else
                <a href="{{\Request::url()}}?page={{$i}}" aria-label="Page {{$i}}" class="join-item btn btn-sm">{{$i}}</a>
            @endif
        @endfor
        @if ($currentPage < $pages)
            <a href="{{\Request::url()}}?page={{$currentPage+1}}" aria-label="Next page" class="join-item btn btn-sm">Next</a>
        @else
            <button class="join-item btn btn-sm btn-disabled" disabled>Next</button>
        @endif
    </div>
</nav>
