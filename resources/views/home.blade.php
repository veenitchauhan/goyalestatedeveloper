@extends('layouts.public')
@section('content')
@php($content=\App\Models\Homepage::editableContent($content))
@php($ctas=\App\Models\ContentEntry::publishedItems('cta')->keyBy('id'))
@php($primaryCta=$ctas->get($content['hero']['primary_cta_id']))
@php($secondaryCta=$ctas->get($content['hero']['secondary_cta_id']))
@php($hasContact=$sections->contains('id','contact'))
<section class="hero" aria-labelledby="hero-title">
    @php($heroImage=\App\Models\Media::where('is_public',true)->where('publication_status','published')->whereNull('archived_at')->where('mime','like','image/%')->whereKey($content['hero']['media_id']??null)->first())
    <img class="hero-architecture" src="{{ $heroImage ? route('media.show',$heroImage) : asset('assets/architecture/hero-concept.webp') }}" alt="{{ $heroImage?->alt ?? 'Conceptual architectural illustration of high-rise structures and a tower crane' }}" width="1200" height="1000" fetchpriority="high">
    <div class="hero-inner">
        <p class="eyebrow"><span class="orange-line"></span>{{ $content['hero']['eyebrow'] }}</p>
        <h1 id="hero-title">{{ $content['hero']['line_one'] }}<br>{{ $content['hero']['line_two'] }}<br><span>{{ $content['hero']['line_three'] }}</span></h1>
        <p class="hero-description">{{ $content['hero']['description'] }}</p>
        <div class="hero-actions">@if($primaryCta || $hasContact)<a class="button orange" href="{{ $primaryCta['url'] ?? '#contact' }}">{{ $primaryCta['title'] ?? $content['hero']['primary_cta'] }} <span aria-hidden="true">↗</span></a>@endif @if($secondaryCta || $sections->contains('id','projects'))<a class="text-link" href="{{ $secondaryCta['url'] ?? route('projects.index') }}">{{ $secondaryCta['title'] ?? $content['hero']['secondary_cta'] }} <span aria-hidden="true">↗</span></a>@endif</div>
    </div>
    <div class="hero-baseline"><span>{{ $content['hero']['baseline'] }}</span><span class="visual-caption">{{ $heroImage ? $heroImage->caption : 'Architectural concept · not a project photograph' }}</span><a href="#{{ $sections->first()['id'] ?? 'main' }}" aria-label="Explore the company">↓</a></div>
</section>
@php($heroVideo=\App\Models\Media::where('is_public',true)->where('publication_status','published')->whereNull('archived_at')->where('mime','video/mp4')->whereKey($content['hero']['video_id'])->first())
@if($heroVideo)<details class="hero-film"><summary>{{ $content['hero']['video_label'] ?: $heroVideo->title }}</summary><figure><video controls playsinline preload="none" @if($heroImage) poster="{{ route('media.show',$heroImage) }}" @endif aria-label="{{ $heroVideo->title }}"><source src="{{ route('media.show',$heroVideo) }}" type="video/mp4"></video>@if($heroVideo->caption)<figcaption>{{ $heroVideo->caption }}</figcaption>@endif @if($heroVideo->description)<p>{{ $heroVideo->description }}</p>@endif</figure></details>@endif

@php($sectionAssets=\App\Models\Media::where('is_public',true)->where('publication_status','published')->whereNull('archived_at')->whereIn('id',$sections->flatMap(fn($section)=>[$section['media_id']??null,$section['video_id']??null,$section['card_1_id']??null,$section['card_2_id']??null,$section['card_3_id']??null])->filter()->unique())->get()->keyBy('id'))
@foreach($sections as $section)
<section id="{{ $section['id'] }}" class="section section-{{ $section['id'] }}">
    <div class="section-wrap">
    @switch($section['id'])
    @case('about')
        @php($aboutImage=$sectionAssets->get($section['media_id']??null))
        @if($section['artwork_enabled']??true)<figure class="company-visual"><img src="{{ $aboutImage ? route('media.show',$aboutImage) : asset('assets/architecture/delivery.webp') }}" alt="{{ $aboutImage?->alt ?: 'Architectural concept model with drawings and material samples' }}" width="1536" height="1024" loading="lazy">@if($aboutImage?->caption)<figcaption>{{ $aboutImage->caption }}</figcaption>@elseif(!$aboutImage)<figcaption>Design thinking. Built into every detail. <span>Concept illustration</span></figcaption>@endif</figure>@endif
        <div class="company-story"><div class="about-heading"><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><div class="about-copy"><p class="lead">{{ $section['text'] }}</p><div class="signature">{{ $section['label'] }}</div><a class="text-link dark-link" href="{{ route('corporate.about.index') }}">Explore our company ↗</a></div></div>
        @break
    @case('business')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div>
        @php($publishedBusiness=\App\Services\CorporateContent::items('business_unit'))
        @php($businessCards=$publishedBusiness->isNotEmpty() ? $publishedBusiness->map(fn($item)=>['title'=>$item['title'],'text'=>$item['summary'],'detail'=>'','url'=>$item['url']]) : collect($section['items']))
        <div class="business-grid">@foreach($businessCards as $item)
            <article class="business-card"><div class="card-top"><span>{{ sprintf('%02d',$loop->iteration) }}</span></div>
            @if($section['artwork_enabled']??true)
            @php($cardImage=$sectionAssets->get($section['card_'.($loop->index%3+1).'_id']??null))
            <figure class="business-art"><img src="{{ $cardImage ? route('media.show',$cardImage) : asset('assets/architecture/'.['construction','infrastructure','delivery'][$loop->index%3].'.webp') }}" alt="{{ $cardImage?->alt ?: 'Concept illustration: '.$item['title'] }}" loading="lazy" width="1536" height="1024">@unless($cardImage)<figcaption>Concept illustration</figcaption>@endunless</figure>
            @endif

            <h3><a class="business-card-link" href="{{ $item['url'] ?? route('corporate.business.index') }}">{{ $item['title'] }}</a></h3><p>{{ $item['text'] }}</p>@if($item['detail'])<div class="card-detail">{{ $item['detail'] }}</div>@endif</article>
        @endforeach</div>
        @break
    @case('capabilities')
        <div class="process-intro"><p class="eyebrow">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p>@php($processImage=$sectionAssets->get($section['media_id']??null))
        @if($section['artwork_enabled']??true)<figure class="process-art"><img src="{{ $processImage ? route('media.show',$processImage) : asset('assets/architecture/construction-journey.webp') }}" alt="{{ $processImage?->alt ?: 'Concept illustration showing architectural plans, a concrete structure and a completed building in copper and teal' }}" width="1024" height="1024" loading="lazy"><figcaption>{{ $processImage?->caption ?: 'From first drawings to final details' }} @unless($processImage)<span>AI-generated concept illustration</span>@endunless</figcaption></figure>@endif<div class="process-visual" aria-hidden="true"><span>PLAN</span><i></i><span>STRUCTURE</span><i></i><span>DELIVERY</span></div></div>
        <div class="process-list">@foreach($section['items'] as $item)<details name="construction-process" @if($loop->first) open @endif><summary><span class="step-number">{{ sprintf('%02d',$loop->iteration) }}</span><h3>{{ $item['title'] }}</h3><span class="expand" aria-hidden="true">＋</span></summary><p>{{ $item['text'] }}</p></details>@endforeach</div>
        @break
    @case('projects')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div>
        @php($featuredProjects=\App\Services\ProjectContent::items()->where('featured',true)->take(6))
        @if($featuredProjects->isNotEmpty())<div class="project-cards">@foreach($featuredProjects as $project)<article>@include('partials.card-image', ['card' => $project])
<p class="eyebrow dark">{{ $project['city'] }} · {{ $project['status'] }}</p><h3>{{ $project['title'] }}</h3><p>{{ $project['stage'] }} — {{ $project['progress'] }}% complete</p><a class="text-link dark-link" href="{{ $project['url'] }}">Explore project ↗</a></article>@endforeach</div><a class="text-link dark-link" href="{{ route('projects.index') }}">View all projects ↗</a>
        @else
        @php($projectImage=$sectionAssets->get($section['media_id']??null))
        <div class="project-showcase">
        @if($section['artwork_enabled']??true)<figure><img src="{{ $projectImage ? route('media.show',$projectImage) : asset('assets/architecture/construction-journey.webp') }}" alt="{{ $projectImage?->alt ?: 'Concept illustration of construction from structural frame to completed architecture' }}" width="1024" height="1024" loading="lazy">@if($projectImage?->caption)<figcaption>{{ $projectImage->caption }}</figcaption>@elseif(!$projectImage)<figcaption>Concept illustration · Not a completed company project</figcaption>@endif</figure>@endif
        <div class="project-showcase-copy"><p class="eyebrow">PROJECTS & POSSIBILITIES</p><p>{{ $section['empty'] }}</p><div class="project-showcase-actions">@if($hasContact)<a class="button orange" href="{{ route('contact') }}">{{ $section['cta'] }} ↗</a>@endif<a class="text-link" href="{{ route('projects.index') }}">Explore projects ↗</a></div></div></div>
        @endif
        @break
    @case('presence')
        <div class="presence-copy"><a class="text-link dark-link" href="{{ route('locations.index') }}">Explore our locations ↗</a><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p><small>{{ $section['future'] }}</small></div>@php($presenceImage=$sectionAssets->get($section['media_id']??null))
        @if($section['artwork_enabled']??true)<figure class="presence-concept"><img src="{{ $presenceImage ? route('media.show',$presenceImage) : asset('assets/architecture/presence.webp') }}" alt="{{ $presenceImage?->alt ?: 'Conceptual illustration of connected urban neighbourhoods' }}" width="1536" height="1024" loading="lazy"><figcaption>{{ $presenceImage?->caption ?: 'Connected places. New possibilities. · Concept illustration, not a geographic map' }}</figcaption></figure>@endif

        @break
    @case('careers')
        <div><p class="eyebrow">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><div class="careers-copy"><p>{{ $section['text'] }}</p>@if(\App\Services\CareerContent::openings()->isEmpty())<p class="muted">{{ $section['empty'] }}</p>@endif<a class="button light" href="{{ route('careers.index') }}">Explore careers ↗</a></div>
        @break
    @case('insights')
        <div class="section-heading"><div><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2></div><p>{{ $section['text'] }}</p></div><div class="faq-list">@foreach($section['items'] as $item)<details><summary>{{ $item['title'] }}<span aria-hidden="true">＋</span></summary><p>{{ $item['text'] }}</p></details>@endforeach
        </div>
        @php($featuredInsights=\App\Services\KnowledgeContent::items()->where('featured',true)->take(6))
        @if($featuredInsights->isNotEmpty())
        <section class="featured-insights" aria-labelledby="featured-insights-title">
            <h3 id="featured-insights-title">{{ $section['featured_title'] ?? 'Ideas, insights & answers' }}</h3>
            <div class="faq-list">
            @foreach($featuredInsights as $article)
                <details><summary>{{ $article['title'] }}<span aria-hidden="true">＋</span></summary><p>{{ $article['short_answer'] }}</p><p><a class="text-link" href="{{ $article['url'] }}">Read more ↗</a></p></details>
            @endforeach
            </div>
            <nav class="featured-insights-links" aria-label="Explore knowledge"><a href="{{ route('knowledge.article.index') }}">Explore insights ↗</a><a href="{{ route('knowledge.knowledge.index') }}">Knowledge Bank ↗</a><a href="{{ route('knowledge.faq.index') }}">FAQs ↗</a></nav>
        </section>
        @endif
        @break
    @case('contact')
        <div class="contact-intro"><p class="eyebrow dark">{{ $section['eyebrow'] }}</p><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p><div class="contact-details">@if($content['contact']['email'])<a href="mailto:{{ $content['contact']['email'] }}">{{ $content['contact']['email'] }}</a>@endif @if($content['contact']['phone'])<a href="tel:{{ preg_replace('/[^+0-9]/','',$content['contact']['phone']) }}">{{ $content['contact']['phone'] }}</a>@endif @if($content['contact']['address'])<p>{{ $content['contact']['address'] }}</p>@endif</div></div>
        <div class="contact-form">@if(session('enquiry_sent'))<div class="form-success" role="status"><h3>Thank you for getting in touch.</h3><p>Your enquiry has been received.</p></div>@endif
        @if($errors->any())<div class="form-errors" role="alert"><strong>Please check your enquiry.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="post" action="{{ route('enquiries.store') }}">@csrf<div class="form-grid"><div><label for="name">Your name *</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="150" required></div><div><label for="email">Email address *</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div><div><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="25"></div><div><label for="location">Project / enquiry location *</label><input id="location" name="location" value="{{ old('location') }}" maxlength="150" required></div><div class="full"><label for="type">How can we help? *</label><select id="type" name="type" required><option value="">Select your enquiry type</option>@foreach(array_keys(\App\Services\EnquiryForms::enabled()) as $type)<option @selected(old('type')===$type)>{{ $type }}</option>@endforeach</select></div><div class="full"><label for="message">Tell us about your requirement *</label><textarea id="message" name="message" rows="4" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea></div></div><div class="honey" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div><label class="consent"><input name="consent" type="checkbox" value="1" @checked(old('consent')) required><span>I agree that my details may be used to respond to this enquiry.</span></label><button class="button ink">{{ $section['cta'] }} <span aria-hidden="true">↗</span></button></form></div>
        @break
    @endswitch
    @php($sectionImage=$sectionAssets->get($section['media_id']??null))
    @php($sectionVideo=$sectionAssets->get($section['video_id']??null))
    @php($sectionCta=$ctas->get($section['cta_id']??null))
    @if((!in_array($section['id'],['about','capabilities','presence','projects']) || ($section['id']==='projects' && $featuredProjects->isNotEmpty())) && $sectionImage && str_starts_with($sectionImage->mime,'image/'))<figure class="section-media"><img src="{{ route('media.show',$sectionImage) }}" alt="{{ $sectionImage->alt }}" loading="lazy" width="1200" height="800">@if($sectionImage->caption)<figcaption>{{ $sectionImage->caption }}</figcaption>@endif</figure>@endif
    @if($sectionVideo?->mime==='video/mp4')<figure class="section-media"><video controls playsinline preload="none" aria-label="{{ $sectionVideo->title }}"><source src="{{ route('media.show',$sectionVideo) }}" type="video/mp4"></video>@if($sectionVideo->caption)<figcaption>{{ $sectionVideo->caption }}</figcaption>@endif @if($sectionVideo->description)<p>{{ $sectionVideo->description }}</p>@endif</figure>@endif
    @if($sectionCta)<div class="section-action"><a class="button orange" href="{{ $sectionCta['url'] }}">{{ $sectionCta['title'] }} ↗</a></div>@endif
    </div>
</section>
@endforeach
@endsection
