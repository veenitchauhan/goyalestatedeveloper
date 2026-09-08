@extends('layouts.public')
@section('content')
<article><section class="corporate-hero"><div class="corporate-container"><a href="{{ route('careers.index') }}">← Careers</a><p class="eyebrow">{{ $payload['department'] }} · {{ $payload['job_type'] }}</p><h1>{{ $payload['title'] }}</h1><p class="lead">{{ $payload['location'] }} · {{ $payload['employment_type'] }}</p></div></section><div class="corporate-container corporate-detail">
<dl class="corporate-facts">@foreach(['experience'=>'Experience','education'=>'Education','deadline'=>'Apply by'] as $key=>$label) @if($payload[$key]??null)<div><dt>{{ $label }}</dt><dd>{{ $payload[$key] }}</dd></div>@endif @endforeach
@if(($payload['salary_public']??false) && ($payload['salary']??null))<div><dt>Salary</dt><dd>{{ $payload['salary'] }}</dd></div>@endif</dl>
@foreach(['description'=>'About the role','responsibilities'=>'Responsibilities','requirements'=>'Requirements','skills'=>'Skills'] as $key=>$label) @if($payload[$key]??null)<section class="corporate-group"><h2>{{ $label }}</h2><p class="cms-prose">{{ $payload[$key] }}</p></section>@endif @endforeach
@if(!($preview??false)) @include('careers.application-form') @endif
</div></article>
@endsection
