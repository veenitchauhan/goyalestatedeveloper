@extends('layouts.admin')
@section('title','Website settings')
@section('content')
<h1>Website settings.</h1><p>Manage shared company details, contact information and branding. {{ \App\Services\ContentPublisher::immediate() ? 'Saving updates the website immediately.' : 'Saving creates a private draft for review and publication.' }}</p>
<form method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
<input type="hidden" name="version" value="{{ $revision->version }}">
@foreach($settings as $group=>$fields)
<fieldset><legend>{{ str($group)->headline() }}</legend>
@if($group==='seo')<p>Tracking IDs are saved for the integrations module. Saving an ID does not load tracking scripts.</p>@endif
@if($group==='branding')<p>Choose a public image from the media library. Images can be added later.</p>@endif
@foreach($fields as $key=>$value)
@php($field='settings.'.$group.'.'.$key)
<label for="{{ $group }}-{{ $key }}">{{ str($key)->replace('_id',' image')->replace('_url',' link')->headline() }}</label>
@if($group==='branding')
<select id="{{ $group }}-{{ $key }}" name="settings[{{ $group }}][{{ $key }}]"><option value="">No image selected</option>@foreach($media as $image)<option value="{{ $image->id }}" @selected(old($field,$value)==$image->id)>{{ $image->title }}</option>@endforeach</select>
@elseif($key==='enabled')
<select id="{{ $group }}-{{ $key }}" name="settings[{{ $group }}][{{ $key }}]"><option value="0" @selected(!old($field,$value))>Disabled</option><option value="1" @selected(old($field,$value))>Enabled</option></select>
@elseif($key==='robots'||$key==='position')
<select id="{{ $group }}-{{ $key }}" name="settings[{{ $group }}][{{ $key }}]">@foreach($key==='robots'?['noindex,nofollow','index,follow']:['bottom-left','bottom-right','top-left','top-right','center'] as $option)<option @selected(old($field,$value)===$option)>{{ $option }}</option>@endforeach</select>
@elseif(in_array($key,['address','description','legal_information']))
<textarea id="{{ $group }}-{{ $key }}" name="settings[{{ $group }}][{{ $key }}]" rows="3">{{ old($field,$value) }}</textarea>
@else
<input id="{{ $group }}-{{ $key }}" name="settings[{{ $group }}][{{ $key }}]" value="{{ old($field,$value) }}" @readonly($group==='company'&&$key==='name') type="{{ in_array($key,['opacity','size','padding'])?'number':(str_contains($key,'email')?'email':'text') }}">
@endif
@endforeach</fieldset>
@endforeach
<button>{{ \App\Services\ContentPublisher::immediate() ? 'Save settings' : 'Save settings draft' }}</button></form>
@include('admin.content.workflow')
@endsection
