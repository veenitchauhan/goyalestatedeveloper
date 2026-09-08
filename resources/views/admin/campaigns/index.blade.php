@extends('layouts.admin')
@section('title','Campaigns')
@section('content')
<p class="eyebrow">MARKETING</p><h1>Campaign landing pages</h1><p>Create focused pages using approved company content.</p><a class="button" href="{{ route('admin.campaigns.create') }}">Create campaign</a><section><form method="get"><label for="q">Search campaigns</label><input id="q" name="q" maxlength="150" value="{{ request('q') }}"><button>Search</button></form></section><div class="cards">@forelse($entries as $entry)<section><h2>{{ $entry->title }}</h2><p>{{ str($entry->status)->headline() }}</p><a href="{{ route('admin.campaigns.edit',$entry) }}">Manage campaign →</a></section>@empty<section><h2>No campaigns yet</h2><p>New campaigns stay private until approved and published.</p></section>@endforelse</div>{{ $entries->links('pagination') }}
@endsection
