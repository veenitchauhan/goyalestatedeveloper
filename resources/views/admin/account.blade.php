@extends('layouts.admin')
@section('title', 'Personal settings')
@section('content')
<p class="eyebrow">YOUR ACCOUNT</p><h1>Personal settings</h1><p class="page-intro">Manage your own sign-in details.</p>
<div class="account-grid"><section><h2>Profile</h2><dl class="profile-details"><dt>Name</dt><dd>{{ auth()->user()->name }}</dd><dt>Email address</dt><dd>{{ auth()->user()->email }}</dd></dl></section>
<section><h2>Change password</h2><p class="muted">Use at least 8 characters, including letters and numbers.</p>
@if($errors->updatePassword->any())<div class="errors" role="alert">@foreach($errors->updatePassword->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="post" action="{{ route('user-password.update') }}">@csrf @method('PUT')
<label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
<label for="new-password">New password</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" required>
<label for="confirmation">Confirm new password</label><input id="confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
<button>Update password</button></form></section></div>
@endsection
