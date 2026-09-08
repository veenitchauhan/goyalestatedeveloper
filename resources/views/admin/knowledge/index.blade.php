@extends('layouts.admin')
@section('title','Insights, Knowledge & FAQs')
@section('content')
<p class="eyebrow">EDITORIAL WORKSPACE</p><h1>Insights, Knowledge & FAQs</h1><p>Publish useful answers and project stories, supported by approved information.</p>
@can('pages.create')<a class="button" href="{{ route('admin.knowledge.create') }}">Add content</a>@endcan
<section><form method="get" class="form-grid"><div><label for="q">Search titles</label><input id="q" name="q" value="{{ request('q') }}" maxlength="150"></div><div><label for="type">Content type</label><select id="type" name="type"><option value="">All types</option>@foreach(\App\Services\KnowledgeContent::TYPES as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div><button>Search</button></form></section>
<div class="cards">@forelse($entries as $entry)<section><p class="eyebrow">{{ \App\Services\KnowledgeContent::TYPES[$entry->type] }}</p><h2>{{ $entry->title }}</h2><p>{{ str($entry->status)->headline() }}</p>@can('pages.edit')<a href="{{ route('admin.knowledge.edit',$entry) }}">Edit content →</a>@endcan <a href="{{ route('admin.knowledge.preview',$entry) }}">Preview</a></section>@empty<section><h2>No content found</h2><p>Create a draft to get started.</p></section>@endforelse</div>{{ $entries->links('pagination') }}
@endsection
