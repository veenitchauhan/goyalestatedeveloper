@extends('layouts.admin')
@section('title','Projects & progress')
@section('content')
<p class="eyebrow">PROJECT PORTFOLIO</p><h1>Projects & progress</h1><p>Manage project facts, construction stages and approved site media.</p>
@can('projects.edit')<a class="button" href="{{ route('admin.projects.create') }}">Add project</a>@endcan
<div class="cards">@forelse($entries as $entry)<section><h2>{{ $entry->title }}</h2><span class="tag">{{ str($entry->status)->headline() }}</span>@if($entry->publishedRevision)
<p><strong>Live website title:</strong> {{ $entry->publishedRevision->payload['title'] }}</p>
@if($entry->status!=='published')<p>Your latest changes are not live yet. Visitors still see the last published version.</p>@else<p>This version is live on the website.</p>@endif
<a href="{{ route('projects.show',$entry->slug) }}" target="_blank" rel="noopener">View live project ↗</a>
@else<p>Not visible on the website yet.</p>@endif
@can('update',$entry) @if($entry->status!=='published')<p><a href="{{ route('admin.projects.edit',$entry) }}#project-publication">Review and publish changes →</a></p>@endif @endcan
@can('update',$entry)<a href="{{ route('admin.projects.edit',$entry) }}">Manage project →</a>@else<a href="{{ route('admin.projects.preview',$entry) }}">View project →</a>@endcan
</section>@empty<section><h2>Your portfolio starts here</h2><p>Add a project using approved details. Drafts stay private until reviewed and published.</p></section>@endforelse</div>{{ $entries->links('pagination') }}
@endsection
