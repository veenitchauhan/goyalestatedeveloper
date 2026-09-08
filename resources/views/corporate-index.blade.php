@extends('layouts.public')
@section('content')
<section class="corporate-hero"><div class="corporate-container"><p class="eyebrow"><span class="orange-line"></span>{{ str($group)->replace('equipment','Equipment & machinery')->headline() }}</p><h1>{{ $title }}</h1><p class="lead">{{ $introduction }}</p></div></section>
<nav class="corporate-subnav" aria-label="Explore the company"><a href="{{ route('corporate.about.index') }}">Company</a><a href="{{ route('corporate.business.index') }}">Business</a><a href="{{ route('corporate.capabilities.index') }}">Capabilities</a><a href="{{ route('corporate.equipment.index') }}">Equipment & machinery</a><a href="{{ route('corporate.leadership.index') }}">Leadership</a><a href="{{ route('corporate.journey.index') }}">Our journey</a><a href="{{ route('corporate.stories.index') }}">Employee stories</a></nav>
<div class="corporate-container corporate-listing">
@foreach($groups as $type=>$items)
@if($items->isNotEmpty())<section class="corporate-group" aria-labelledby="group-{{ $type }}"><h2 id="group-{{ $type }}">{{ config('corporate.'.$type.'.label') }}</h2>
@if($type==='company_milestone')
@foreach(['company'=>'Company journey','leadership'=>'Leadership / promoter experience'] as $timeline=>$label)
@php($timelineItems=$items->filter(fn($item)=>$item['facts']['timeline']===$timeline)->sortBy('facts.occurred_on'))
@if($timelineItems->isNotEmpty())<h3 class="timeline-label">{{ $label }}</h3>@include('partials.corporate-cards',['items'=>$timelineItems])@endif
@endforeach
@else @include('partials.corporate-cards',['items'=>$items]) @endif
</section>@endif
@endforeach
@if($groups->every(fn($items)=>$items->isEmpty()))<div class="corporate-empty"><span class="mini-structure" aria-hidden="true">╱╱╱</span><h2>More to share.</h2><p>Published {{ strtolower($group==='equipment' ? 'equipment profiles' : 'information') }} will appear here. For a specific requirement, please contact our team.</p>@if($sections->contains('id','contact'))<a class="button ink" href="{{ route('contact') }}">Start a conversation ↗</a>@endif</div>@endif
@if($group==='capabilities')<aside class="corporate-callout"><p class="eyebrow dark">SUPPORTING PROJECT EXECUTION</p><h2>Equipment & machinery.</h2><p>Explore equipment information alongside the capabilities it supports.</p><a class="text-link dark-link" href="{{ route('corporate.equipment.index') }}">Explore equipment ↗</a></aside>@endif
</div>
@endsection
