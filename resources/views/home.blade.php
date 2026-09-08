@extends('layouts.public')
@section('content')
@php($hasContact=$sections->contains('id','contact'))
<section class="hero" aria-labelledby="hero-title">
    @php($heroImage=\App\Models\Media::where('is_public',true)->whereKey($content['hero']['media_id']??null)->first())
    <img class="hero-architecture" src="{{ $heroImage ? route('media.show',$heroImage) : asset('assets/architecture/hero.svg') }}" alt="{{ $heroImage?->alt ?? 'Conceptual architectural illustration of high-rise structures and a tower crane' }}" width="1200" height="1000" fetchpriority="high">
    <div class="hero-inner">
        <p class="eyebrow"><span class="orange-line"></span>{{ $content['hero']['eyebrow'] }}</p>
        <h1 id="hero-title">{{ $content['hero']['line_one'] }}<br>{{ $content['hero']['line_two'] }}<br><span>{{ $content['hero']['line_three'] }}</span></h1>
        <p class="hero-description">{{ $content['hero']['description'] }}</p>
        <div class="hero-actions">@if($hasContact)<a class="button orange" href="#contact">{{ $content['hero']['primary_cta'] }} <span aria-hidden="true">↗</span></a>@endif @if($sections->contains('id','projects'))<a class="text-link" href="#projects">{{ $content['hero']['secondary_cta'] }} <span aria-hidden="true">↗</span></a>@endif</div>
    </div>
    <div class="hero-baseline"><span>TRICITY ROOTS. A FORWARD VISION.</span><span class="visual-caption">{{ $heroImage ? $heroImage->caption : 'Architectural concept · not a project photograph' }}</span><a href="#{{ $sections->first()['id'] ?? 'main' }}" aria-label="Explore the company">↓</a></div>
</section>
@include('partials.statistics',['statistics'=>\App\Models\ContentEntry::publishedItems('statistic')])
@foreach($sections as $section)
<section id="{{ $section['id'] }}" class="section section-{{ $section['id'] }}">
    <div class="section-wrap">
    @switch($section['id'])
    @case('about')
        <div class="about-heading"><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div>
        <div class="about-copy"><p class="lead">{{ $section['text'] }}</p><div class="signature"><span class="mini-structure" aria-hidden="true">╱╱╱</span>{{ $section['label'] }}</div></div>
        @break
    @case('business')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div>
        <div class="business-grid">@foreach($section['items'] as $item)
            <article class="business-card"><div class="card-top"><span>{{ sprintf('%02d',$loop->iteration) }}</span><span aria-hidden="true">↗</span></div>
            <svg class="service-drawing" viewBox="0 0 300 170" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.2">@if($loop->index===0)<path d="M70 145V35l85-25 75 30v105M70 35l75 32 85-27M145 67v95M85 42v102m15-97v99m15-92v93m15-86v88M70 70l75 31 85-28M70 100l75 31 85-28M155 65v93m20-99v95m20-101v93m20-99v95"/>@elseif($loop->index===1)<path d="M20 135l125-55 135 45M25 150l120-54 130 42M80 118V35m135 83V35M80 35q68 92 135 0M80 60q68 85 135 0M80 35v115m135-115v112M105 75v48m25-31v20m25-15v15m25-22v27m25-56v70"/>@else<path d="M30 135l105-55 140 38-106 48ZM75 113V57l80-36 80 25v89M75 57l83 25 77-36M158 82v80M100 69v56m30-48v64m53-71v80m28-94v83"/><path d="M42 35h30m-15-15v30m190 96h30m-15-15v30"/>@endif</svg>
            <h3>{{ $item['title'] }}</h3><p>{{ $item['text'] }}</p><div class="card-detail">{{ $item['detail'] }}</div></article>
        @endforeach</div>
        @break
    @case('capabilities')
        <div class="process-intro"><p class="eyebrow">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p><div class="process-visual" aria-hidden="true"><span>PLAN</span><i></i><span>STRUCTURE</span><i></i><span>DELIVERY</span></div></div>
        <div class="process-list">@foreach($section['items'] as $item)<details name="construction-process" @if($loop->first) open @endif><summary><span class="step-number">{{ sprintf('%02d',$loop->iteration) }}</span><h3>{{ $item['title'] }}</h3><span class="expand" aria-hidden="true">＋</span></summary><p>{{ $item['text'] }}</p></details>@endforeach</div>
        @break
    @case('projects')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div>
        <div class="project-note"><div class="project-outline" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div><div><p>{{ $section['empty'] }}</p>@if($hasContact)<a class="text-link dark-link" href="#contact">{{ $section['cta'] }} ↗</a>@endif</div></div>
        @break
    @case('presence')
        <div class="presence-copy"><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p><small>{{ $section['future'] }}</small></div><div class="presence-art" aria-label="Abstract illustration of the Tricity foundation"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="orbit orbit-three"></div><div class="presence-point"><i></i><span>{{ $section['label'] }}</span></div><span class="compass" aria-hidden="true">N ↑</span></div>
        @break
    @case('careers')
        <div><p class="eyebrow">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><div class="careers-copy"><p>{{ $section['text'] }}</p><p class="muted">{{ $section['empty'] }}</p>@if($hasContact)<a class="button light" href="#contact">{{ $section['cta'] }} ↗</a>@endif</div>
        @break
    @case('insights')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div><div class="faq-list">@foreach($section['items'] as $item)<details><summary>{{ $item['title'] }}<span aria-hidden="true">＋</span></summary><p>{{ $item['text'] }}</p></details>@endforeach</div>
        @break
    @case('contact')
        <div class="contact-intro"><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p><div class="contact-details">@if($content['contact']['email'])<a href="mailto:{{ $content['contact']['email'] }}">{{ $content['contact']['email'] }}</a>@endif @if($content['contact']['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/','',$content['contact']['phone']) }}">{{ $content['contact']['phone'] }}</a>@endif @if($content['contact']['address'])<p>{{ $content['contact']['address'] }}</p>@endif</div></div>
        <div class="contact-form">@if(session('enquiry_sent'))<div class="form-success" role="status"><h3>Thank you for getting in touch.</h3><p>Your enquiry has been received.</p></div>@endif
        @if($errors->any())<div class="form-errors" role="alert"><strong>Please check your enquiry.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="post" action="{{ route('enquiries.store') }}">@csrf<div class="form-grid"><div><label for="name">Your name *</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="150" required></div><div><label for="email">Email address *</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div><div><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="25"></div><div><label for="location">Project / enquiry location *</label><input id="location" name="location" value="{{ old('location') }}" maxlength="150" required></div><div class="full"><label for="type">How can we help? *</label><select id="type" name="type" required><option value="">Select your enquiry type</option>@foreach(['Construction','Infrastructure','Project delivery','Development opportunity','Vendor / partner','Career','General'] as $type)<option @selected(old('type')===$type)>{{ $type }}</option>@endforeach</select></div><div class="full"><label for="message">Tell us about your requirement *</label><textarea id="message" name="message" rows="4" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea></div></div><div class="honey" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div><label class="consent"><input name="consent" type="checkbox" value="1" @checked(old('consent')) required><span>I agree that my details may be used to respond to this enquiry.</span></label><button class="button ink">{{ $section['cta'] }} <span aria-hidden="true">↗</span></button></form></div>
        @break
    @endswitch
    </div>
</section>
@endforeach
@endsection
