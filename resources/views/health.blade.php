@extends('layouts.admin')
@section('title','Local connection check')
@section('content')<section><h1>Local connection is working.</h1><p>This request was handled by Laravel.</p><pre>{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre><a href="{{ route('home') }}">Back to website</a></section>@endsection
