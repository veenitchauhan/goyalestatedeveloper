@extends('layouts.admin')
@section('title','Company & capabilities')
@section('content')
<h1>Company & capabilities.</h1>
<p>Manage company pages, business areas, services, equipment and people. Drafts remain private until approved and published.</p>
@can('pages.create')<div class="corporate-admin-actions">@foreach(config('corporate') as $type=>$definition)<a class="button" href="{{ route('admin.corporate.create',['type'=>$type]) }}">Add {{ strtolower($definition['singular']) }} →</a>@endforeach</div>@endcan
<form method="get" action="{{ route('admin.corporate.index') }}" class="corporate-filter">
<label for="search">Search titles</label><input id="search" name="q" value="{{ $filters['q']??'' }}" maxlength="150">
<label for="type">Record type</label><select id="type" name="type"><option value="">All types</option>@foreach(config('corporate') as $type=>$definition)<option value="{{ $type }}" @selected(($filters['type']??'')===$type)>{{ $definition['label'] }}</option>@endforeach</select>
<label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['draft','review','approved','published','scheduled','unpublished','archived'] as $status)<option value="{{ $status }}" @selected(($filters['status']??'')===$status)>{{ str($status)->headline() }}</option>@endforeach</select><button>Filter</button></form>
<div class="corporate-records">@forelse($entries as $entry)<article><p>{{ config('corporate.'.$entry->type.'.singular') }} · {{ str($entry->status)->headline() }}</p><h2>{{ $entry->title }}</h2><p>/{{ $entry->slug }}</p>@can('pages.edit')<a href="{{ route('admin.corporate.edit',$entry) }}">Edit & review →</a>@endcan</article>@empty<p>No records match these filters. Create a draft using approved company information.</p>@endforelse</div>
{{ $entries->links() }}
@endsection
