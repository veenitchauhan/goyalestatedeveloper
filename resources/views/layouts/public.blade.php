<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content['seo']['title'] }}</title>
    <meta name="description" content="{{ $content['seo']['description'] }}">
    <link rel="canonical" href="{{ isset($entry) && $entry->type==='page' ? route('pages.show',$entry->slug) : route('home') }}">
    <meta property="og:title" content="{{ $content['seo']['title'] }}">
    <meta property="og:description" content="{{ $content['seo']['description'] }}">
    <meta property="og:type" content="website">
    <link rel="stylesheet" href="{{ asset('assets/website.css') }}">
</head>
<body>
@php($menuItems=\App\Models\ContentEntry::publishedItems('menu'))
@if($preview??false)<aside class="preview-notice">Private draft preview · This version is not necessarily published. Resize your browser to review mobile layouts.</aside>@endif
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="company-name" href="{{ route('home') }}">{{ config('app.name') }}</a>
        <nav class="desktop-nav" aria-label="Main navigation">
            <a href="{{ route('home') }}" aria-current="page">Home</a>
            @foreach($sections as $section) @if($section['nav'])<a href="{{ request()->routeIs('home') ? '' : route('home') }}#{{ $section['id'] }}">{{ $section['nav'] }}</a>@endif @endforeach
            @foreach($menuItems->whereIn('placement',['header','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach
        </nav>
        @if($sections->contains('id','contact'))<a class="header-cta" href="{{ request()->routeIs('home') ? '' : route('home') }}#contact">Start a project ↗</a>@endif
        <details class="mobile-nav"><summary>Menu <span aria-hidden="true">＋</span></summary><nav aria-label="Mobile navigation"><a href="{{ route('home') }}">Home</a>@foreach($sections as $section) @if($section['nav'])<a href="{{ request()->routeIs('home') ? '' : route('home') }}#{{ $section['id'] }}">{{ $section['nav'] }}</a>@endif @endforeach @foreach($menuItems->whereIn('placement',['header','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach</nav></details>
    </div>
</header>
<main id="main">@yield('content')</main>
<footer class="site-footer">
    <div class="footer-top"><a class="company-name" href="{{ route('home') }}">{{ config('app.name') }}</a><p>{{ $content['hero']['eyebrow'] }}</p></div>
    <nav class="footer-nav" aria-label="Footer navigation">@foreach($sections as $section) @if($section['nav'])<a href="{{ request()->routeIs('home') ? '' : route('home') }}#{{ $section['id'] }}">{{ $section['nav'] }}</a>@endif @endforeach @foreach($menuItems->whereIn('placement',['footer','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach</nav>
    <div class="footer-bottom"><span>© {{ date('Y') }} {{ config('app.name') }}</span><a href="#main">Back to top ↑</a></div>
</footer>
@if($content['contact']['phone'] || $content['contact']['whatsapp'])<aside class="mobile-actions" aria-label="Contact actions">@if($content['contact']['whatsapp'])<a href="https://wa.me/{{ $content['contact']['whatsapp'] }}">WhatsApp ↗</a>@endif @if($content['contact']['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/','',$content['contact']['phone']) }}">Call ↗</a>@endif</aside>@endif
</body>
</html>
