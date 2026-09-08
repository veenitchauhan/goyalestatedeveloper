@extends('layouts.public')
@section('content')
<section class="corporate-hero"><div class="corporate-container"><p class="eyebrow">CAREERS</p><h1>Build your career with us.</h1><p class="lead">Explore approved openings across our teams, including internships and graduate opportunities.</p><a class="button light" href="#openings">View openings</a></div></section>
<div class="corporate-container corporate-detail">
@if(session('application_received'))<p class="notice" role="status">Thank you. Your application has been received for recruitment review.</p>@endif
@foreach(\App\Models\ContentEntry::publishedItems('block')->whereIn('slug',['careers-why','careers-life']) as $block)<section class="corporate-group"><h2>{{ $block['title'] }}</h2><p class="cms-prose">{{ $block['body']??'' }}</p></section>@endforeach
<section class="corporate-group" id="openings"><h2>Current openings</h2><form method="get" class="project-filters">@foreach(['department'=>'Department','location'=>'Location','experience'=>'Experience','employment_type'=>'Employment','job_type'=>'Job type'] as $key=>$label)<div><label for="filter-{{ $key }}">{{ $label }}</label><select id="filter-{{ $key }}" name="{{ $key }}"><option value="">All</option>@foreach($all->pluck($key)->filter()->unique()->sort() as $value)<option @selected(($filters[$key]??'')===$value)>{{ $value }}</option>@endforeach</select></div>@endforeach<button class="button ink">Filter openings</button><a href="{{ route('careers.index') }}">Reset</a></form>
<div class="project-cards">@forelse($items as $job)<article><p class="eyebrow">{{ $job['department'] }} · {{ $job['job_type'] }}</p><h3><a href="{{ route('careers.show',$job['slug']) }}">{{ $job['title'] }}</a></h3><p>{{ $job['location'] }} · {{ $job['employment_type'] }}</p>@if($job['deadline']??null)<p>Apply by {{ $job['deadline'] }}</p>@endif<a class="text-link dark-link" href="{{ route('careers.show',$job['slug']) }}">View role & apply ↗</a></article>@empty<article><h3>No matching openings at present.</h3><p>You can submit your résumé below for recruitment review.</p></article>@endforelse</div></section>
@if($stories->isNotEmpty())<section class="corporate-group"><h2>Our people</h2>@include('partials.corporate-cards',['items'=>$stories])</section>@endif
@include('careers.application-form')
</div>
@endsection
