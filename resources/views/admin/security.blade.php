@extends('layouts.admin')
@section('title','Account security')
@section('content')
<p class="eyebrow">ACCOUNT SECURITY</p>
<h1>{{ auth()->user()->two_factor_confirmed_at ? 'Your account security.' : 'Finish setting up your access.' }}</h1>
@if(auth()->user()->hasRole('super-admin') && !auth()->user()->two_factor_confirmed_at)
<p>Your sign-in worked. Complete this one-time authenticator setup to open the CMS. Your requested page will open when setup is complete.</p>
<ol class="setup-steps"><li>Confirm your password @if($recentPassword) — complete @endif</li><li>Connect an authenticator and verify a code @if(auth()->user()->two_factor_confirmed_at) — complete @endif</li><li>Save recovery codes and open your workspace</li></ol>
@endif
@if(!$recentPassword)
<section><h2>Confirm your current password</h2><p>Confirm once to view security details or change security settings. You do not need to change your password.</p><a class="button" href="{{ route('password.confirm') }}">Confirm current password →</a></section>
@else
<section><h2>Authenticator setup</h2>
@if($errors->confirmTwoFactorAuthentication->any())<div class="errors" role="alert">@foreach($errors->confirmTwoFactorAuthentication->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(!auth()->user()->two_factor_secret)
<p>Open an authenticator app on your phone. Select “Add account” or “Scan QR code” after clicking the button below. This protects your Super Admin account.</p><form method="post" action="{{ route('two-factor.enable') }}">@csrf<button>Show setup QR code</button></form>
@elseif(!auth()->user()->two_factor_confirmed_at)
<p>Scan this QR code using your authenticator app, then enter the six-digit code it displays. Keep this page open while you do this.</p><div class="qr">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div><form method="post" action="{{ route('two-factor.confirm') }}">@csrf<label for="code">Six-digit authenticator code</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required><button>Verify authenticator</button></form>
@else
<p class="notice">Your authenticator is connected.</p>
<details @if(session('admin.setup_recovery_pending')) open @endif><summary>Save your recovery codes</summary><p>Keep these codes somewhere private, outside this website. Each code can be used once if you cannot access your authenticator.</p><ul class="codes">@foreach(auth()->user()->recoveryCodes() as $code)<li>{{ $code }}</li>@endforeach</ul></details>
<form method="post" action="{{ route('admin.security.continue') }}">@csrf<label><input type="checkbox" name="recovery_saved" value="1" required> I have saved my recovery codes privately.</label><button>Open my workspace →</button></form>
<details><summary>Advanced security actions</summary><form method="post" action="{{ route('two-factor.regenerate-recovery-codes') }}">@csrf<button>Replace all recovery codes</button></form><form method="post" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')<p>Super Admins must connect an authenticator again before returning to the CMS if this is disabled.</p><button class="quiet">Disable authenticator</button></form></details>
@endif
</section>
<details><summary>Change password (optional)</summary><section><h2>Change password</h2><p>Changing your password does not complete authenticator setup.</p>
@if($errors->updatePassword->any())<div class="errors" role="alert">@foreach($errors->updatePassword->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="post" action="{{ route('user-password.update') }}">@csrf @method('PUT')<label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required><label for="new-password">New password (8+ characters, letters and numbers)</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" required><label for="confirmation">Confirm new password</label><input id="confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button>Update password</button></form></section></details>
@endif
@endsection
