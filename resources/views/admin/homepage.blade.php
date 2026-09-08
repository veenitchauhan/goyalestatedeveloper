@extends('layouts.admin')
@section('title','Homepage content')
@section('content')<h1>Homepage content.</h1><p>Edit copy, contact details and section order. Blank phone and WhatsApp values hide those actions. Saving creates a private draft.</p>
<form method="post" action="{{ route('admin.homepage.update') }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $revision->version }}">
<label for="hero-media">Hero image</label><select name="hero_media_id" id="hero-media"><option value="">Keep architectural illustration</option>@foreach($media as $image)<option value="{{ $image->id }}" @selected(old('hero_media_id',$revision->payload['hero']['media_id']??null)==$image->id)>{{ $image->title }}</option>@endforeach</select><p>Publish an image in the media library to make it available here.</p>
@php($groups=collect($fields)->groupBy(fn($value,$key)=>str_starts_with($key,'sections.') ? 'sections.'.explode('.',$key)[1] : explode('.',$key)[0]))
@foreach($groups as $group=>$items)<details class="editor-group" open><summary>{{ str_starts_with($group,'sections.') ? (data_get($revision->payload,$group.'.nav') ?: data_get($revision->payload,$group.'.id')) : str($group)->headline() }}</summary>
@foreach($items as $key=>$value) @if(!str_ends_with($key,'.id') && !str_ends_with($key,'.media_id'))
@php($fieldName='content['.str_replace('.','][',$key).']')
<div><label for="field-{{ str_replace('.','-',$key) }}">{{ str(substr($key,strlen($group)+1))->replace('items.','Item ')->replace('.',' / ')->replace('_',' ')->title() }}</label>
@if(is_bool($value))<select id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}"><option value="1" @selected(old('content.'.$key,$value))>Enabled</option><option value="0" @selected(!old('content.'.$key,$value))>Hidden</option></select>
@elseif(is_int($value))<input type="number" min="0" max="1000" id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}" value="{{ old('content.'.$key,$value) }}">
@else<textarea rows="2" id="field-{{ str_replace('.','-',$key) }}" name="{{ $fieldName }}">{{ old('content.'.$key,$value) }}</textarea>@endif</div>@endif @endforeach</details>@endforeach<button>Save draft</button></form>
@include('admin.content.workflow')@endsection
