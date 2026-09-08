@extends('layouts.public')
@section('content')
<section class="corporate-hero"><div class="corporate-container"><p class="eyebrow">DEVELOPMENTS</p><h1>Explore our developments</h1></div></section><div class="corporate-container corporate-detail"><div class="project-cards">@forelse($items as $item)<article>@include('partials.card-image', ['card' => $item])
<p>{{ $item['category'] }} · {{ $item['location'] }}</p><h2><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h2><p>{{ $item['summary']??'' }}</p></article>@empty<p>No developments are published at present.</p>@endforelse</div></div>
@endsection
