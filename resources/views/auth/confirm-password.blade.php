@extends('layouts.admin')
@section('title','Confirm password')
@section('content')<section class="auth-card"><h1>Confirm it’s you.</h1><p>Enter your password to open account security or change user access.</p><form method="post" action="{{ route('password.confirm.store') }}">@csrf<label for="password">Current password</label><input id="password" type="password" name="password" required autocomplete="current-password" autofocus><button>Confirm password</button></form></section>@endsection
