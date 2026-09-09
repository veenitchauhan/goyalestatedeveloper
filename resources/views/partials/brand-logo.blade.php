@php($brandingSettings=$siteSettings??\App\Models\SiteSetting::current())
@php($brandingLogo=\App\Models\SiteSetting::image($brandingSettings['branding']['logo_id']))
<img class="branding-logo" src="{{ $brandingLogo ? route('media.show',$brandingLogo) : asset('assets/goyal-logo.png') }}" alt="{{ $brandingSettings['company']['name'] }}" width="300" height="100">
