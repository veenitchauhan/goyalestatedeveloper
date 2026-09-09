@php($faviconSettings=$siteSettings??\App\Models\SiteSetting::current())
@php($faviconImage=\App\Models\SiteSetting::image($faviconSettings['branding']['favicon_id']))
<link rel="icon" type="image/png" href="{{ $faviconImage ? route('media.show',$faviconImage) : asset('assets/goyal-favicon.png') }}">
