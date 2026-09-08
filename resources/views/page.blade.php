@extends('layouts.public')
@section('content')<article class="section"><div class="section-wrap"><p class="eyebrow dark">{{ config('app.name') }}</p><h1>{{ $payload['title'] }}</h1><div class="cms-prose">{{ $payload['body']??'' }}</div>
@if($entry->type==='statistic')<p class="lead">{{ $payload['value']??'' }}</p>@endif
@if($entry->type==='menu')<p><a href="{{ $payload['url'] }}">{{ $payload['title'] }}</a></p>@endif
@foreach(\App\Models\ContentEntry::publishedItems('block')->whereIn('id',$payload['block_ids']??[]) as $block)<section><h2>{{ $block['title'] }}</h2><div class="cms-prose">{{ $block['body']??'' }}</div></section>@endforeach
@include('partials.statistics',['statistics'=>\App\Models\ContentEntry::publishedItems('statistic')->whereIn('id',$payload['statistic_ids']??[])])
</div></article>@endsection
