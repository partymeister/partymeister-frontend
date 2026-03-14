<!doctype html>
<html lang="en" data-theme="night">
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
<body class="min-h-screen bg-base-100 font-[Inter]">

{{-- Drawer wrapper for mobile nav --}}
<div class="drawer">
    <input id="mobile-nav" type="checkbox" class="drawer-toggle" />

    {{-- Main content area --}}
    <div class="drawer-content flex flex-col">

        {{-- Navbar --}}
        <div class="navbar bg-base-100 shadow-sm sticky top-0 z-50">
            <div class="navbar-start">
                {{-- Mobile hamburger --}}
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                        </svg>
                    </div>
                    <ul tabindex="-1" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow">
                        @foreach($navigationItems as $item)
                            @if ($item->is_visible && $item->is_active)
                                <li>
                                    <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                                       class="@if($activeNavigationSlugs[0] == $item->full_slug) active @endif">
                                        {{$item->name}}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                {{-- Site title --}}
                <a href="/" class="btn btn-ghost text-xl" style="{{config('motor-cms-frontend.style')}}">
                    {!! config('motor-cms-frontend.name') !!}
                </a>
            </div>

            {{-- Desktop nav --}}
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    @foreach($navigationItems as $item)
                        @if ($item->is_visible && $item->is_active)
                            <li>
                                <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                                   class="@if($activeNavigationSlugs[0] == $item->full_slug) active @endif">
                                    {{$item->name}}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="navbar-end">
            </div>
        </div>

        {{-- Breadcrumbs --}}
        <div class="container mx-auto px-4 pt-4">
            <div class="breadcrumbs text-sm">
                <ul>
                    @foreach($activeNavigationItem->ancestors as $item)
                        @if (!$loop->first)
                            <li>
                                <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}">{{$item->name}}</a>
                            </li>
                        @endif
                    @endforeach
                    <li>{{$activeNavigationItem->name}}</li>
                </ul>
            </div>
        </div>

        {{-- Page content --}}
        <main class="container mx-auto px-4 py-6 mb-24 flex-1">
            @include('motor-cms::layouts.frontend.partials.template-sections-tw', ['rows' => $template['items']])
        </main>

        {{-- Footer --}}
        <footer class="footer footer-center p-4 bg-base-200 text-base-content">
        </footer>
    </div>

    {{-- Mobile sidebar drawer --}}
    <div class="drawer-side z-[60]">
        <label for="mobile-nav" aria-label="close sidebar" class="drawer-overlay"></label>
        <ul class="menu bg-base-200 min-h-full w-72 p-4">
            <li class="menu-title" style="{{config('motor-cms-frontend.style')}}">
                {!! config('motor-cms-frontend.name') !!}
            </li>
            @foreach($navigationItems as $item)
                @if ($item->is_visible && $item->is_active)
                    <li>
                        <a href="{{ route('frontend.pages.index', ['slug' => $item->full_slug])}}"
                           class="@if($activeNavigationSlugs[0] == $item->full_slug) active @endif">
                            {{$item->name}}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>

@vite(['resources/assets/js/frontend-tw.js'])
@yield('view-scripts')
</body>
</html>
