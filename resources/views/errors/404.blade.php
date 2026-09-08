@extends('layouts.admin')
@section('title','Page not found')
@section('content')<section><p class="eyebrow">404</p><h1>This page isn’t here.</h1><p>The page may not exist or may not be published yet.</p><a href="{{ route('checkpoint') }}">Back to checkpoint</a></section>@endsection
