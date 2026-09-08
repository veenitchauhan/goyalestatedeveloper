@php($siteSettings=$siteSettings??\App\Models\SiteSetting::current())
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo-head')
    @if($favicon=\App\Models\SiteSetting::image($siteSettings['branding']['favicon_id']))<link rel="icon" href="{{ route('media.show',$favicon) }}">@endif
    @if($siteSettings['seo']['search_console_verification'])<meta name="google-site-verification" content="{{ $siteSettings['seo']['search_console_verification'] }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/website.css') }}">
<link rel="stylesheet" href="{{ asset('assets/projects.css') }}">
</head>
<body>
@php($headerCta=\App\Models\ContentEntry::publishedItems('cta')->firstWhere('id',$content['hero']['primary_cta_id']??null))
@php($menuItems=\App\Models\ContentEntry::publishedItems('menu'))
@if($preview??false)<aside class="preview-notice">Private draft preview · This version is not necessarily published. Resize your browser to review mobile layouts.</aside>@endif
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="company-name" href="{{ route('home') }}">@if($logo=\App\Models\SiteSetting::image($siteSettings['branding']['logo_id']))<img class="company-logo" src="{{ route('media.show',$logo) }}" alt="">@endif{{ $siteSettings['company']['name'] }}</a>
        <nav class="desktop-nav" aria-label="Main navigation"><a href="{{ route('search') }}">Search</a>
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
            @foreach($sections as $section) @if($section['nav'])<a href="{{ in_array($section['id'],['about','business','capabilities']) ? route('corporate.'.$section['id'].'.index') : ((in_array($section['id'],['careers','insights','contact']) ? route(match($section['id']) { 'careers'=>'careers.index', 'insights'=>'knowledge.article.index', default=>'contact' }) : ($section['id']==='projects' ? route('projects.index') : (request()->routeIs('home') ? '' : route('home')).'#'.$section['id']))) }}">{{ $section['nav'] }}</a>@endif @endforeach
            @foreach($menuItems->whereIn('placement',['header','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach
        </nav>
        @if($headerCta || $sections->contains('id','contact'))<a class="header-cta" href="{{ $headerCta['url'] ?? route('contact') }}">{{ $headerCta['title'] ?? $content['hero']['primary_cta'] }} ↗</a>@endif
        <details class="mobile-nav"><summary>Menu <span aria-hidden="true">＋</span></summary><nav aria-label="Mobile navigation"><a href="{{ route('search') }}">Search</a><a href="{{ route('home') }}">Home</a>@foreach($sections as $section) @if($section['nav'])<a href="{{ in_array($section['id'],['about','business','capabilities']) ? route('corporate.'.$section['id'].'.index') : ((in_array($section['id'],['careers','insights','contact']) ? route(match($section['id']) { 'careers'=>'careers.index', 'insights'=>'knowledge.article.index', default=>'contact' }) : ($section['id']==='projects' ? route('projects.index') : (request()->routeIs('home') ? '' : route('home')).'#'.$section['id']))) }}">{{ $section['nav'] }}</a>@endif @endforeach @foreach($menuItems->whereIn('placement',['header','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach</nav></details>
    </div>
</header>
<main id="main">@if(isset($entry,$payload['title']))<nav class="corporate-subnav" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-current="page">{{ $payload['title'] }}</span></nav>@endif
@yield('content')</main>
<footer class="site-footer">
    <div class="footer-top"><a class="company-name" href="{{ route('home') }}">@if($logo=\App\Models\SiteSetting::image($siteSettings['branding']['logo_id']))<img class="company-logo" src="{{ route('media.show',$logo) }}" alt="">@endif{{ $siteSettings['company']['name'] }}</a><p>{{ $content['hero']['eyebrow'] }}</p></div>
    <nav class="footer-nav" aria-label="Footer navigation">@if(\App\Services\DevelopmentContent::enabled())<a href="{{ route('developments.index') }}">Developments</a>@endif<a href="{{ route('search') }}">Search</a><a href="{{ route('knowledge.knowledge.index') }}">Knowledge Bank</a><a href="{{ route('knowledge.faq.index') }}">FAQs</a>@foreach($sections as $section) @if($section['nav'])<a href="{{ in_array($section['id'],['about','business','capabilities']) ? route('corporate.'.$section['id'].'.index') : ((in_array($section['id'],['careers','insights','contact']) ? route(match($section['id']) { 'careers'=>'careers.index', 'insights'=>'knowledge.article.index', default=>'contact' }) : ($section['id']==='projects' ? route('projects.index') : (request()->routeIs('home') ? '' : route('home')).'#'.$section['id']))) }}">{{ $section['nav'] }}</a>@endif @endforeach @foreach($menuItems->whereIn('placement',['footer','both']) as $item)<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@endforeach</nav>
    <div class="footer-settings">
    @if($content['contact']['address'])<p>{{ $content['contact']['address'] }}</p>@endif
    @if($content['contact']['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/','',$content['contact']['phone']) }}">{{ $content['contact']['phone'] }}</a>@endif
    @if($content['contact']['email'])<a href="mailto:{{ $content['contact']['email'] }}">{{ $content['contact']['email'] }}</a>@endif
    @if($content['contact']['office_hours']??'')<p>{{ $content['contact']['office_hours'] }}</p>@endif
    @if($content['contact']['map_url']??'')<a href="{{ $content['contact']['map_url'] }}" rel="noopener">View location map ↗</a>@endif
    <nav aria-label="Social profiles">@foreach($siteSettings['social'] as $network=>$url) @if($url)<a href="{{ $url }}" rel="noopener">{{ str($network)->headline() }} ↗</a>@endif @endforeach</nav>
    <nav aria-label="Legal information"><a href="{{ route('privacy.preferences') }}">Privacy preferences</a>@foreach(['privacy_url'=>'Privacy policy','terms_url'=>'Terms & conditions','cookies_url'=>'Cookie policy','disclaimer_url'=>'Disclaimer'] as $key=>$label) @if($siteSettings['footer'][$key])<a href="{{ $siteSettings['footer'][$key] }}">{{ $label }}</a>@endif @endforeach</nav>
    @if($siteSettings['company']['legal_information'])<p>{{ $siteSettings['company']['legal_information'] }}</p>@endif
    </div>
    <div class="footer-bottom"><span>© {{ date('Y') }} {{ config('app.name') }}</span><a href="#main">Back to top ↑</a></div>
</footer>
@if($content['contact']['phone'] || $content['contact']['whatsapp'])<aside class="mobile-actions" aria-label="Contact actions">@if($content['contact']['whatsapp'])<a href="https://wa.me/{{ $content['contact']['whatsapp'] }}">WhatsApp ↗</a>@endif @if($content['contact']['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/','',$content['contact']['phone']) }}">Call ↗</a>@endif</aside>@endif
</body>
</html>
