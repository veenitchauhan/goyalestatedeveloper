@extends('layouts.admin')
@section('title','Careers & openings')
@section('content')
<p class="eyebrow">RECRUITMENT</p><h1>Careers & openings</h1><p>Create approved permanent, contract, internship and graduate openings.</p>@can('jobs.edit')<a class="button" href="{{ route('admin.jobs.create') }}">Add opening</a>@endcan
<div class="cards">@forelse($entries as $entry)<section><h2>{{ $entry->title }}</h2><p>{{ str($entry->status)->headline() }}</p>@can('jobs.edit')<a href="{{ route('admin.jobs.edit',$entry) }}">Manage opening →</a>@endcan</section>@empty<section><h2>No openings added</h2><p>Draft openings stay private until approved and published.</p></section>@endforelse</div>{{ $entries->links('pagination') }}
@endsection
