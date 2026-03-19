<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{$version->name}} | {{config('motor-cms-frontend.name')}}</title>
    @vite(['resources/assets/css/frontend.css'])
    <link rel="shortcut icon" href="/favicon.png" type="image/x-icon">
    <link rel="icon" href="/favicon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700|quattrocento-sans:400,700" rel="stylesheet" />
    @yield('view-styles')
</head>
<body class="min-h-screen bg-body font-['Source_Sans_3'] text-base text-text" x-data="{ mobileNav: false }">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 bg-surface border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                {{-- Site title with logo --}}
                <a href="/" class="flex items-center gap-2 text-heading font-bold text-lg shrink-0 hover:text-accent transition-colors"
                   style="{{config('motor-cms-frontend.style')}}">
                    <img src="/images/logo-small.png" alt="" class="h-8 w-8">
                    {!! config('motor-cms-frontend.name') !!}
                </a>

                {{-- Desktop nav --}}
                <div class="hidden lg:flex items-center gap-1.5">
                    @foreach($navigationItems as $item)
                        @if ($item->is_visible && $item->is_active)
                            <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                               class="px-3 py-2 rounded-md transition-colors
                                      @if($activeNavigationSlugs[0] == $item->full_slug)
                                          text-heading font-semibold
                                      @else
                                          text-text font-medium hover:text-heading
                                      @endif">
                                {{$item->name}}
                            </a>
                        @endif
                    @endforeach
                    @if(isset($visitor) && !is_null($visitor) && config('partymeister-competitions-voting.party_has_voting'))
                        <a href="{{ route('frontend.pages.index', ['slug' => 'voting'])}}"
                           class="px-3 py-2 rounded-md transition-colors text-accent font-medium hover:text-accent-hover">
                            Vote
                        </a>
                    @endif
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileNav = true" x-bind:aria-expanded="mobileNav" aria-label="Open menu" class="lg:hidden p-2 rounded-md text-text hover:text-heading hover:bg-surface-raised transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- Mobile full-screen nav overlay --}}
    <div x-show="mobileNav" x-cloak class="fixed inset-0 z-[60] lg:hidden"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="absolute inset-0 bg-body/95 backdrop-blur-sm flex flex-col">
            {{-- Close button --}}
            <div class="relative z-10 flex items-center justify-between px-6 h-14">
                <span class="flex items-center gap-2 text-heading font-bold text-lg" style="{{config('motor-cms-frontend.style')}}">
                    <img src="/images/logo-small.png" alt="" class="h-8 w-8">
                    {!! config('motor-cms-frontend.name') !!}
                </span>
                <button @click="mobileNav = false" aria-label="Close menu" class="p-2 rounded-md text-text hover:text-heading transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Centered nav links --}}
            <nav class="flex-1 flex flex-col items-center justify-center gap-2 -mt-14">
                @foreach($navigationItems as $item)
                    @if ($item->is_visible && $item->is_active)
                        <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                           class="px-6 py-3 rounded-lg text-2xl transition-colors
                                  @if($activeNavigationSlugs[0] == $item->full_slug)
                                      text-heading font-semibold
                                  @else
                                      text-text font-normal hover:text-heading
                                  @endif">
                            {{$item->name}}
                        </a>
                    @endif
                @endforeach
                @if(isset($visitor) && !is_null($visitor) && config('partymeister-competitions-voting.party_has_voting'))
                    <a href="{{ route('frontend.pages.index', ['slug' => 'voting'])}}"
                       class="px-6 py-3 rounded-lg text-2xl transition-colors text-accent font-normal hover:text-accent-hover">
                        Vote
                    </a>
                @endif
            </nav>
        </div>
    </div>

    {{-- Page content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mb-24 flex-1">
        @include('motor-cms::layouts.frontend.partials.template-sections-tw', ['rows' => $template['items']])
    </main>

    {{-- Footer --}}
    <footer class="bg-surface border-t border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center text-sm text-text-muted">
        </div>
    </footer>

@vite(['resources/assets/js/frontend-tw.js'])
@yield('view-scripts')
</body>
</html>
