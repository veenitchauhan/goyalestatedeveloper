@extends('layouts.public')
@section('content')
<section class="corporate-hero"><div class="corporate-container"><p class="eyebrow">OUR PRESENCE</p><h1>Strong foundations.<br>Room to grow.</h1><p class="lead">Explore our verified operating locations and the projects connected to them.</p></div></section>
<div class="corporate-container corporate-detail">
@if($items->filter(fn($item)=>isset($item['latitude'],$item['longitude']))->isNotEmpty())
<section class="corporate-group"><h2>Geographic overview</h2><p>Verified coordinates. Select a marker or a location below to explore.</p><svg viewBox="0 0 720 360" role="img" aria-label="World coordinate overview of verified operating locations" class="location-map"><rect width="720" height="360" fill="#e9eeeb"/>
@for($x=0;$x<=720;$x+=120)<path d="M{{ $x }} 0V360" stroke="#ccd6d1"/>@endfor
@for($y=0;$y<=360;$y+=60)<path d="M0 {{ $y }}H720" stroke="#ccd6d1"/>@endfor
@foreach($items as $item) @if(isset($item['latitude'],$item['longitude']))<a href="{{ $item['url'] }}" aria-label="{{ $item['title'] }}"><circle cx="{{ ((float)$item['longitude']+180)*2 }}" cy="{{ (90-(float)$item['latitude'])*2 }}" r="6" fill="#b65432"><title>{{ $item['title'] }}</title></circle></a>@endif @endforeach</svg><p class="muted">Longitude −180° to 180° · latitude 90° to −90°. Detailed map links are available on location pages.</p></section>
@endif
@foreach(\App\Services\LocationContent::LEVELS as $level)
@if($items->where('level',$level)->isNotEmpty())<section class="corporate-group"><h2>{{ str($level)->plural()->headline() }}</h2><div class="project-cards">@foreach($items->where('level',$level) as $item)<article><h3><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h3><p>{{ $item['summary'] }}</p><a class="text-link dark-link" href="{{ $item['url'] }}">Explore location ↗</a></article>@endforeach</div></section>@endif
@endforeach
@if($items->isEmpty())<section class="corporate-group"><h2>Location profiles are being prepared.</h2><p>Verified local information and project links will appear here when approved.</p></section>@endif
</div>
@endsection
