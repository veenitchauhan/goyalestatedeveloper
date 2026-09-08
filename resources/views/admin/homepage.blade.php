@extends('layouts.admin')
@section('title','Homepage content')
@section('content')<h1>Homepage content.</h1><p>Edit the approved website copy and contact details. Enable or reorder sections using their controls. Blank phone and WhatsApp settings hide those actions.</p><form method="post" action="{{ route('admin.homepage.update') }}">@csrf @method('PUT')
@foreach($fields as $key=>$value)
@if(!str_ends_with($key,'.id'))
@php($fieldName='content['.str_replace('.','][',$key).']')
<div><label for="field-{{ $loop->index }}">{{ str($key)->replace('.',' / ')->replace('_',' ')->title() }}</label>
@if(is_bool($value))<select id="field-{{ $loop->index }}" name="{{ $fieldName }}"><option value="1" @selected($value)>Enabled</option><option value="0" @selected(!$value)>Hidden</option></select>
@elseif(is_int($value))<input type="number" min="0" max="1000" id="field-{{ $loop->index }}" name="{{ $fieldName }}" value="{{ old('content.'.$key,$value) }}">
@else<textarea rows="2" id="field-{{ $loop->index }}" name="{{ $fieldName }}">{{ old('content.'.$key,$value) }}</textarea>@endif</div>
@endif @endforeach<button>Save homepage</button></form>@endsection
