@extends('layouts.public')
@section('content')
<article><section class="corporate-hero"><div class="corporate-container"><nav class="corporate-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('locations.index') }}">Locations</a>@foreach($ancestors as $ancestor)<span>/</span><a href="{{ $ancestor['url'] }}">{{ $ancestor['title'] }}</a>@endforeach</nav><p class="eyebrow">{{ str($payload['level'])->headline() }} @if($payload['presence']==='future') · Planned location @endif</p><h1>{{ $payload['title'] }}</h1><p class="lead">{{ $payload['summary']??'' }}</p></div></section>
<div class="corporate-container corporate-detail"><div class="cms-prose">{{ $payload['body']??'' }}</div>
@foreach(['industries'=>'Industries served','areas_served'=>'Areas served'] as $key=>$label) @if($payload[$key]??null)<section class="corporate-group"><h2>{{ $label }}</h2><p class="cms-prose">{{ $payload[$key] }}</p></section>@endif @endforeach
@if(isset($payload['latitude'],$payload['longitude']))<section class="corporate-group"><h2>On the map</h2><p>Latitude {{ $payload['latitude'] }} · longitude {{ $payload['longitude'] }}</p><a class="text-link dark-link" href="https://www.openstreetmap.org/?mlat={{ (float)$payload['latitude'] }}&amp;mlon={{ (float)$payload['longitude'] }}#map=12/{{ (float)$payload['latitude'] }}/{{ (float)$payload['longitude'] }}" target="_blank" rel="noopener noreferrer">Open detailed map ↗</a></section>@endif
@if($children->isNotEmpty())<section class="corporate-group"><h2>Explore this area</h2>@foreach($children as $child)<p><a href="{{ $child['url'] }}">{{ $child['title'] }} ↗</a></p>@endforeach</section>@endif
@if($services->isNotEmpty())<section class="corporate-group"><h2>Relevant services</h2>@include('partials.corporate-cards',['items'=>$services])</section>@endif
@if($projects->isNotEmpty())<section class="corporate-group"><h2>Projects in this area</h2><div class="project-cards">@foreach($projects as $project)<article><h3><a href="{{ $project['url'] }}">{{ $project['title'] }}</a></h3><p>{{ $project['stage'] }} · {{ $project['progress'] }}% complete</p></article>@endforeach</div></section>@endif
<aside class="corporate-callout"><h2>Discuss your project.</h2><a class="button ink" href="{{ route('contact',['cta'=>'locations-show']) }}">Start a conversation ↗</a></aside></div></article>
@include('partials.related-knowledge')
@endsection
