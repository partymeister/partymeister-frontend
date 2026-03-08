<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{$version->name}} | {{config('motor-cms-frontend.name')}}</title>

    @vite(['resources/assets/sass/partymeister-frontend.package-development.scss'])
    <link rel="shortcut icon" href="/favicon.png" type="image/x-icon">
    <link rel=icon href="/favicon.png" type="image/x-icon">
    @yield('view-styles')
    <style lang="text/css">
        {{ config('partymeister-frontend.css') }}
    </style>
</head>
<body>
@include('motor-cms::layouts.frontend.partials.navigation')
<div class="grid-container" style="margin-bottom: 8rem; margin-top: 1rem;">
    @include('motor-cms::layouts.frontend.partials.template-sections', ['rows' => $template['items']])
</div>
<div class="columns shrink footer text-center" style="position: fixed; bottom: 0; width: 100%;">
</div>
@vite(['resources/assets/js/frontend.js'])
@yield('view-scripts')
<script type="module">
    $(document).foundation();
</script>
</body>
</html>
