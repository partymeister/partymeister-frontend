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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @yield('view-styles')
</head>
<body class="min-h-screen bg-body font-[Inter] text-text" x-data="{ mobileNav: false }">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 bg-surface border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                {{-- Site title --}}
                <a href="/" class="text-heading font-bold text-lg shrink-0 hover:text-accent transition-colors"
                   style="{{config('motor-cms-frontend.style')}}">
                    {!! config('motor-cms-frontend.name') !!}
                </a>

                {{-- Desktop nav --}}
                <div class="hidden lg:flex items-center gap-1">
                    @foreach($navigationItems as $item)
                        @if ($item->is_visible && $item->is_active)
                            <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                               class="px-3 py-2 rounded-md text-sm transition-colors
                                      @if($activeNavigationSlugs[0] == $item->full_slug)
                                          text-heading font-medium
                                      @else
                                          text-text hover:text-heading
                                      @endif">
                                {{$item->name}}
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileNav = true" class="lg:hidden p-2 rounded-md text-text hover:text-heading hover:bg-surface-raised transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- Mobile slide-out drawer --}}
    <div x-show="mobileNav" x-cloak class="fixed inset-0 z-[60] lg:hidden">
        {{-- Overlay --}}
        <div x-show="mobileNav"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileNav = false"
             class="absolute inset-0 bg-black/60"></div>

        {{-- Drawer panel --}}
        <div x-show="mobileNav"
             x-transition:enter="transition-transform ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 w-72 bg-surface shadow-xl">

            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-4 h-14 border-b border-border">
                <span class="text-heading font-bold" style="{{config('motor-cms-frontend.style')}}">
                    {!! config('motor-cms-frontend.name') !!}
                </span>
                <button @click="mobileNav = false" class="p-2 rounded-md text-text hover:text-heading transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Drawer nav links --}}
            <nav class="px-2 py-4 space-y-1">
                @foreach($navigationItems as $item)
                    @if ($item->is_visible && $item->is_active)
                        <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                           class="block px-3 py-2 rounded-md text-sm transition-colors
                                  @if($activeNavigationSlugs[0] == $item->full_slug)
                                      text-heading bg-surface-raised font-medium
                                  @else
                                      text-text hover:text-heading hover:bg-surface-raised
                                  @endif">
                            {{$item->name}}
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Breadcrumbs --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <nav class="flex text-sm text-text-muted" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1">
                @foreach($activeNavigationItem->ancestors as $item)
                    @if (!$loop->first)
                        <li class="flex items-center gap-1">
                            <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                               class="hover:text-heading transition-colors">{{$item->name}}</a>
                            <svg class="h-4 w-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </li>
                    @endif
                @endforeach
                <li class="text-text">{{$activeNavigationItem->name}}</li>
            </ol>
        </nav>
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
