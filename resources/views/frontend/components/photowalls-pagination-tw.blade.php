<nav aria-label="Pagination" class="flex justify-center my-4">
    <div class="flex items-center">
        @if ($currentPage > 1)
            <a href="{{\Request::url()}}?page={{$currentPage-1}}" aria-label="Previous page" class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border border-border bg-surface text-text hover:bg-surface-raised transition-colors -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg">Previous</a>
        @else
            <button class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border border-border bg-surface text-text -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg opacity-50 cursor-not-allowed" disabled>Previous</button>
        @endif
        @for ($i=1; $i<=$pages; $i++)
            @if ($currentPage == $i)
                <button class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg bg-accent text-body border-accent cursor-default">{{$i}}</button>
            @else
                <a href="{{\Request::url()}}?page={{$i}}" aria-label="Page {{$i}}" class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border border-border bg-surface text-text hover:bg-surface-raised transition-colors -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg">{{$i}}</a>
            @endif
        @endfor
        @if ($currentPage < $pages)
            <a href="{{\Request::url()}}?page={{$currentPage+1}}" aria-label="Next page" class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border border-border bg-surface text-text hover:bg-surface-raised transition-colors -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg">Next</a>
        @else
            <button class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium border border-border bg-surface text-text -ml-px first:ml-0 first:rounded-l-lg last:rounded-r-lg opacity-50 cursor-not-allowed" disabled>Next</button>
        @endif
    </div>
</nav>
