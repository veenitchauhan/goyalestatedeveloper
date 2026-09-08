@extends('layouts.admin')
@section('title','Search workspace')
@section('content')
<p class="eyebrow">WORKSPACE</p><h1>Search records</h1><section><form method="get"><label for="q">Search titles, media names and enquiry names</label><input name="q" id="q" value="{{ $q }}" minlength="2" maxlength="150" required><button>Search</button></form><p>Results are limited to records you can access. Refine your search if needed.</p></section><div class="cards">@forelse($results as $item)<section><p class="eyebrow">{{ str($item['type'])->headline() }} · {{ str($item['status'])->headline() }}</p><h2>{{ $item['title'] }}</h2><a href="{{ $item['url'] }}">Open module →</a></section>@empty<p>No matching accessible records.</p>@endforelse</div>
@endsection
