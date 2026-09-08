@extends('layouts.admin')
@section('title','Locations & expansion')
@section('content')
<p class="eyebrow">GEOGRAPHIC PRESENCE</p><h1>Locations & expansion</h1><p>Build the hierarchy in order: country → state → region → city. Future locations remain outside the active public presence.</p>
@can('pages.create')<a class="button" href="{{ route('admin.locations.create') }}">Add location</a>@endcan
<div class="cards">@forelse($entries as $entry)<section><h2>{{ $entry->title }}</h2><p>{{ str($entry->status)->headline() }}</p>@can('pages.edit')<a href="{{ route('admin.locations.edit',$entry) }}">Manage location →</a>@endcan</section>@empty<section><h2>No locations added</h2><p>Start with a country and add its states, regions and cities. Only enter verified company information.</p></section>@endforelse</div>{{ $entries->links('pagination') }}
@endsection
