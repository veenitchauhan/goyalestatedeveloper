@extends('layouts.admin')
@section('title','Homepage content')
@section('content')<h1>Homepage content.</h1><p>Edit homepage copy and section order. Company tagline and contact details are managed in Website settings. Saving creates a private draft.</p>
<form method="post" action="{{ route('admin.homepage.update') }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $revision->version }}">
<label for="hero-media">Hero image</label><select name="hero_media_id" id="hero-media"><option value="">Keep architectural illustration</option>@foreach($media->filter(fn($item)=>str_starts_with($item->mime,'image/')) as $image)<option value="{{ $image->id }}" @selected(old('hero_media_id',$revision->payload['hero']['media_id']??null)==$image->id)>{{ $image->title }}</option>@endforeach</select><p>Publish an image in the media library to make it available here.</p>
<label for="hero-video">Hero film (optional)</label><select id="hero-video" name="hero_video_id"><option value="">No film</option>@foreach($media->where('mime','video/mp4') as $video)<option value="{{ $video->id }}" @selected(old('hero_video_id',$revision->payload['hero']['video_id']??null)==$video->id)>{{ $video->title }}</option>@endforeach</select><p>Visitors choose when to play the film. Upload an approved, compressed MP4 in Media first.</p>
@foreach(['primary'=>'Primary','secondary'=>'Secondary'] as $key=>$label)<label for="{{ $key }}-cta">{{ $label }} shared CTA</label><select id="{{ $key }}-cta" name="{{ $key }}_cta_id"><option value="">Use homepage label and section link</option>@foreach($ctas as $cta)<option value="{{ $cta['id'] }}" @selected(old($key.'_cta_id',$revision->payload['hero'][$key.'_cta_id']??null)==$cta['id'])>{{ $cta['title'] }}</option>@endforeach</select>@endforeach
<label for="statistics-mode">Homepage statistics</label><select id="statistics-mode" name="statistics_mode">@foreach(['all'=>'All published statistics','selected'=>'Only selected statistics','hidden'=>'Hide statistics'] as $mode=>$label)<option value="{{ $mode }}" @selected(old('statistics_mode',$revision->payload['statistics']['mode']??'all')===$mode)>{{ $label }}</option>@endforeach</select>
<fieldset><legend>Select statistics</legend>@forelse($statistics as $statistic)<label><input type="checkbox" name="statistic_ids[]" value="{{ $statistic['id'] }}" @checked(in_array($statistic['id'],(old('statistics_mode')!==null ? old('statistic_ids',[]) : ($revision->payload['statistics']['ids']??[]))))> {{ $statistic['title'] }}</label>@empty<p>No published statistics. Create verified statistics in Content first.</p>@endforelse</fieldset>
@php($groups=collect($fields)->reject(fn($value,$key)=>str_starts_with($key,'statistics.')||str_starts_with($key,'contact.')||$key==='hero.eyebrow')->groupBy(fn($value,$key)=>str_starts_with($key,'sections.') ? 'sections.'.explode('.',$key)[1] : explode('.',$key)[0]))
@foreach($groups as $group=>$items)<details class="editor-group" open><summary>{{ str_starts_with($group,'sections.') ? (data_get($revision->payload,$group.'.nav') ?: data_get($revision->payload,$group.'.id')) : str($group)->headline() }}</summary>
@if(str_starts_with($group,'sections.'))
@php($sectionIndex=explode('.',$group)[1])
@php($assetFields=['media_id'=>'Section image','video_id'=>'Section video','cta_id'=>'Section CTA'] + (data_get($revision->payload,$group.'.id')==='business' ? ['card_1_id'=>'Construction card image','card_2_id'=>'Infrastructure card image','card_3_id'=>'Project delivery card image'] : []))
@foreach($assetFields as $assetKey=>$assetLabel)
<label for="section-{{ $sectionIndex }}-{{ $assetKey }}">{{ $assetLabel }}</label><select id="section-{{ $sectionIndex }}-{{ $assetKey }}" name="section_assets[{{ $sectionIndex }}][{{ $assetKey }}]"><option value="">Default concept artwork / no override</option>
@if($assetKey==='cta_id')
@foreach($ctas as $cta)<option value="{{ $cta['id'] }}" @selected(old('section_assets.'.$sectionIndex.'.'.$assetKey,data_get($revision->payload,$group.'.'.$assetKey))==$cta['id'])>{{ $cta['title'] }}</option>@endforeach
@else
@foreach($media->filter(fn($item)=>$assetKey==='video_id' ? $item->mime==='video/mp4' : str_starts_with($item->mime,'image/')) as $asset)<option value="{{ $asset->id }}" @selected(old('section_assets.'.$sectionIndex.'.'.$assetKey,data_get($revision->payload,$group.'.'.$assetKey))==$asset->id)>{{ $asset->title }}</option>@endforeach
@endif</select>@endforeach
@endif
@foreach($items as $key=>$value) @if(!str_ends_with($key,'.id') && !str_ends_with($key,'_id'))
@php($fieldName='content['.str_replace('.','][',$key).']')
<div><label for="field-{{ str_replace('.','-',$key) }}">{{ str(substr($key,strlen($group)+1))->replace('items.','Item ')->replace('.',' / ')->replace('_',' ')->title() }}</label>
@if(is_bool($value))<select id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}"><option value="1" @selected(old('content.'.$key,$value))>Enabled</option><option value="0" @selected(!old('content.'.$key,$value))>Hidden</option></select>
@elseif(is_int($value))<input type="number" min="0" max="1000" id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}" value="{{ old('content.'.$key,$value) }}">
@else<textarea rows="2" id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}">{{ old('content.'.$key,$value) }}</textarea>@endif</div>@endif @endforeach</details>@endforeach<button>Save draft</button></form>
@include('admin.content.workflow')@endsection
