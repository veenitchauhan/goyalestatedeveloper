@extends('layouts.admin')
@section('title','Access denied')
@section('content')<section><h1>Access is restricted.</h1><p>Your role does not allow this action. Contact your administrator if you need access.</p><a href="{{ route('admin.dashboard') }}">Back to overview</a></section>@endsection
