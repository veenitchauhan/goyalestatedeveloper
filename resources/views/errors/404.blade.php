@php($availableSections=collect(\App\Models\Homepage::where('key','main')->first()?->content['sections']??[])->where('enabled',true))
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,follow"><title>Page not found | {{ config('app.name') }}</title><link rel="stylesheet" href="{{ asset('assets/website.css') }}"></head>
<body><a class="skip-link" href="#main">Skip to content</a><header class="site-header"><div class="header-inner"><a class="company-name" href="{{ route('home') }}">{{ config('app.name') }}</a></div></header>
<main id="main" class="error-page"><p class="eyebrow dark">404 · PAGE NOT FOUND</p><h1>This page isn’t here.</h1><p>The page may have moved or may not be published yet. Explore our work or tell us about your project.</p><nav class="error-actions" aria-label="Next steps"><a class="button ink" href="{{ route('home') }}">Back home ↗</a>@if($availableSections->contains('id','projects'))<a class="text-link dark-link" href="{{ route('home') }}#projects">Explore projects ↗</a>@endif
@if($availableSections->contains('id','contact'))<a class="text-link dark-link" href="{{ route('home') }}#contact">Contact us ↗</a>@endif</nav></main></body>
</html>
