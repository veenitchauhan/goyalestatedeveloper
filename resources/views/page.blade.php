@extends('layouts.public')
@section('content')<article class="section"><div class="section-wrap"><p class="eyebrow dark">{{ config('app.name') }}</p><h1>{{ $payload['title'] }}</h1><div class="cms-prose">{{ $payload['body']??'' }}</div>
@if($entry->type==='statistic')<p class="lead">{{ $payload['value']??'' }}</p>@endif
@if(in_array($entry->type,['menu','cta']))<p><a href="{{ $payload['url'] }}">{{ $payload['title'] }}</a></p>@endif
@foreach(\App\Models\ContentEntry::publishedItems('block')->whereIn('id',$payload['block_ids']??[]) as $block)<section><h2>{{ $block['title'] }}</h2><div class="cms-prose">{{ $block['body']??'' }}</div></section>@endforeach
@include('partials.statistics',['statistics'=>\App\Models\ContentEntry::publishedItems('statistic')->whereIn('id',$payload['statistic_ids']??[])])
@foreach(\App\Models\ContentEntry::publishedItems('cta')->whereIn('id',$payload['cta_ids']??[]) as $cta)<p><a class="button orange" href="{{ $cta['url'] }}">{{ $cta['title'] }} ↗</a></p>@endforeach
@foreach(\App\Models\Media::whereIn('id',$payload['document_ids']??[])->where('mime','application/pdf')->where('is_public',true)->where('publication_status','published')->whereNull('archived_at')->orderBy('sort_order')->get() as $document)<p><a href="{{ route('media.show',$document) }}">Download {{ $document->title }} (PDF)</a></p>@endforeach
</div></article>@endsection
