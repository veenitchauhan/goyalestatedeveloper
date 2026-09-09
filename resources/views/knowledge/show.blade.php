@extends('layouts.public')
@section('content')
<article class="knowledge-article"><section class="corporate-hero"><div class="corporate-container"><p class="eyebrow">{{ $payload['category'] }}</p><h1>{{ $payload['title'] }}</h1><p class="lead">{{ $payload['short_answer']??'' }}</p></div></section><div class="corporate-container corporate-detail"><p><a href="{{ route('knowledge.'.$entry->type.'.index') }}">← {{ \App\Services\KnowledgeContent::TYPES[$entry->type] }}</a></p>
<div class="article-meta">
@if(!empty($payload['author']))<span>By {{ $payload['author'] }}</span>@endif
@if(!empty($payload['reviewer']))<span>Reviewed by {{ $payload['reviewer'] }}</span>@endif
@if($publishedDate)<span>Published {{ \Illuminate\Support\Carbon::parse($publishedDate)->format('j M Y') }}</span>@endif
@if($updatedDate && $updatedDate !== $publishedDate)<span>Updated {{ \Illuminate\Support\Carbon::parse($updatedDate)->format('j M Y') }}</span>@endif
</div>
@if($payload['topic']??null)<p>Topic: <a href="{{ route('knowledge.'.$entry->type.'.index',['topic'=>$payload['topic']]) }}">{{ $payload['topic'] }}</a></p>@endif
<section class="corporate-group"><h2>{{ $entry->type==='faq' ? 'Answer' : 'In detail' }}</h2><div class="cms-prose">{{ $payload['body']??'' }}</div></section>@if($payload['explanation']??null)<section class="corporate-group"><h2>Supporting explanation</h2><div class="cms-prose">{{ $payload['explanation'] }}</div></section>@endif
@foreach($relatedItems->groupBy('type') as $type=>$items)<section class="corporate-group"><h2>Related {{ \App\Services\KnowledgeContent::TYPES[$type]??str($type)->plural()->headline() }}</h2>@foreach($items as $item)<p><a href="{{ $item['url'] }}">{{ $item['title'] }} ↗</a></p>@endforeach</section>@endforeach
@include('partials.related-knowledge')
<aside class="corporate-callout"><h2>Talk to our project team.</h2><a class="button ink" href="{{ route('contact',['cta'=>'knowledge-show']) }}">Start a conversation ↗</a></aside></div></article>
@endsection
