@extends('layouts.public')
@section('content')
<article>
<section class="corporate-hero"><div class="corporate-container"><nav class="corporate-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('corporate.'.$definition['group'].'.index') }}">{{ str($definition['group'])->headline() }}</a></nav><p class="eyebrow">{{ $definition['singular'] }}</p><h1>{{ $payload['title'] }}</h1><p class="lead">{{ $payload['summary']??'' }}</p></div></section>
<div class="corporate-container corporate-detail">
@if(($cover=$media->get($payload['cover_media_id']??null)) && str_starts_with($cover->mime,'image/'))<figure class="corporate-cover"><img src="{{ route('media.show',$cover) }}" alt="{{ $cover->alt }}" width="1200" height="800" fetchpriority="high">@if($cover->caption)<figcaption>{{ $cover->caption }}</figcaption>@endif</figure>@endif
<div class="corporate-body cms-prose">{{ $payload['body']??'' }}</div>
@if($definition['fields'])<dl class="corporate-facts">@foreach($definition['fields'] as $key=>$options)
@php($value=$payload['facts'][$key]??null)
@if($value!==null && $value!=='')
@if($options['kind']==='relation') @if($relation=$relations[$key]??null)<div><dt>{{ $options['label'] }}</dt><dd><a href="{{ $relation['url'] }}">{{ $relation['title'] }} ↗</a></dd></div>@endif
@else<div><dt>{{ $key==='quantity' ? 'Quantity' : $options['label'] }}</dt><dd>{{ $options['kind']==='select' ? ($options['options'][$value]??$value) : $value }}</dd></div>@endif
@endif
@endforeach</dl>@endif
@if(($film=$media->get($payload['video_media_id']??null)) && $film->mime==='video/mp4')<figure class="section-media"><video controls playsinline preload="none" aria-label="{{ $film->title }}"><source src="{{ route('media.show',$film) }}" type="video/mp4"></video>@if($film->caption)<figcaption>{{ $film->caption }}</figcaption>@endif @if($film->description)<p>{{ $film->description }}</p>@endif</figure>@endif
@if($payload['gallery_ids']??[])<div class="corporate-gallery">@foreach($payload['gallery_ids'] as $id) @if(($image=$media->get($id)) && str_starts_with($image->mime,'image/'))<figure><a href="{{ route('media.show',$image) }}"><img src="{{ route('media.show',$image) }}" alt="{{ $image->alt }}" loading="lazy" width="800" height="600"></a>@if($image->caption)<figcaption>{{ $image->caption }}</figcaption>@endif</figure>@endif @endforeach</div>@endif
@if($payload['document_ids']??[])<div class="corporate-downloads">@foreach($payload['document_ids'] as $id) @if(($document=$media->get($id)) && $document->mime==='application/pdf')<a class="text-link dark-link" href="{{ route('media.show',$document) }}">Download {{ $document->title }} (PDF) ↓</a>@endif @endforeach</div>@endif
@if($services->isNotEmpty())<section class="corporate-group"><h2>Services</h2>@include('partials.corporate-cards',['items'=>$services])</section>@endif
@if($related->isNotEmpty())<section class="corporate-group"><h2>Explore further</h2>@include('partials.corporate-cards',['items'=>$related])</section>@endif
@if($ctas->isNotEmpty())<aside class="corporate-callout"><h2>Continue the conversation.</h2><div class="error-actions">@foreach($ctas as $cta)<a class="button ink" href="{{ $cta['url'] }}">{{ $cta['title'] }} ↗</a>@endforeach</div></aside>@endif
</div></article>
@endsection
