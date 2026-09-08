@php($cardImages=\App\Models\Media::whereIn('id',$items->pluck('cover_media_id')->filter())->where('is_public',true)->where('publication_status','published')->whereNull('archived_at')->where('mime','like','image/%')->get()->keyBy('id'))
<div class="corporate-grid">@foreach($items as $item)<article class="corporate-card">
@if($cardImage=$cardImages->get($item['cover_media_id']??null))<a class="corporate-card-image" href="{{ $item['url'] }}" tabindex="-1" aria-hidden="true"><img src="{{ route('media.show',$cardImage) }}" alt="" loading="lazy" width="800" height="600"></a>@endif
<div class="corporate-card-copy">
@if(($item['type']??'')==='company_milestone')<p class="eyebrow dark">{{ ($item['facts']['timeline']??'company')==='leadership' ? 'Leadership experience' : 'Company journey' }} · {{ $item['facts']['occurred_on'] }}</p>@endif
@if(($item['type']??'')==='equipment')<p class="eyebrow dark">{{ $item['facts']['category'] }}</p>@endif
<h3><a href="{{ $item['url'] }}">{{ $item['title'] }} <span aria-hidden="true">↗</span></a></h3>
@if(($item['type']??'')==='team_member')<p class="corporate-designation">{{ $item['facts']['designation'] }}</p>@endif
<p>{{ $item['summary']??'' }}</p></div></article>@endforeach</div>
