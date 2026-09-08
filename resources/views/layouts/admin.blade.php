<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>@yield('title', 'Administration') | {{ config('app.name') }}</title><link rel="stylesheet" href="{{ asset('assets/admin.css') }}"><script src="{{ asset('assets/password-toggle.js') }}" defer></script><script src="{{ asset('assets/admin-shell.js') }}" defer></script></head>
<body class="@auth cms-shell @else auth-shell @endauth">
<a class="skip" href="#main">Skip to content</a>
@auth
<aside class="sidebar">
<a class="workspace-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">G</span><span>Website workspace<small>CONTENT MANAGEMENT</small></span></a>
<details class="sidebar-menu" open><summary>Workspace menu</summary><nav aria-label="Administration">
<a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▦</span>Overview</a>
@can('pages.edit')
<a href="{{ route('admin.homepage.edit') }}" @if(request()->routeIs('admin.homepage.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">⌂</span>Homepage</a>
@endcan
@can('pages.view')
<a href="{{ route('admin.content.index') }}" @if(request()->routeIs('admin.content.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▤</span>Pages & reusable content</a>
@endcan
@can('pages.view')
<a href="{{ route('admin.corporate.index') }}" @if(request()->routeIs('admin.corporate.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◇</span>Company & capabilities</a>
@endcan
@if(auth()->user()->can('projects.view') || auth()->user()->can('projects.view-assigned'))
<a href="{{ route('admin.projects.index') }}" @if(request()->routeIs('admin.projects.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▥</span>Projects & progress</a>
@endif
@can('pages.view')
<a href="{{ route('admin.locations.index') }}" @if(request()->routeIs('admin.locations.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◎</span>Locations & expansion</a>
@endcan
@can('pages.view')<a href="{{ route('admin.knowledge.index') }}" @if(request()->routeIs('admin.knowledge.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▤</span>Insights, Knowledge & FAQs</a>@endcan
@can('jobs.view')<a href="{{ route('admin.jobs.index') }}" @if(request()->routeIs('admin.jobs.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▣</span>Careers & openings</a>@endcan
@if(auth()->user()->can('candidates.view') || auth()->user()->can('candidates.view-assigned'))<a href="{{ route('admin.candidates.index') }}" @if(request()->routeIs('admin.candidates.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">♙</span>Candidate pipeline</a>@endif
@can('seo.manage')<a href="{{ route('admin.seo.index') }}" @if(request()->routeIs('admin.seo.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◎</span>SEO & discovery</a>@endcan
@can('campaigns.manage')<a href="{{ route('admin.campaigns.index') }}" @if(request()->routeIs('admin.campaigns.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◇</span>Campaigns</a><a href="{{ route('admin.analytics') }}" @if(request()->routeIs('admin.analytics')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▥</span>Analytics</a>@endcan
@can('media.manage')
<a href="{{ route('admin.media.index') }}" @if(request()->routeIs('admin.media.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▧</span>Media library</a>
@endcan
@if(auth()->user()->can('leads.view') || auth()->user()->can('leads.view-assigned'))
<a href="{{ route('admin.enquiries') }}" @if(request()->routeIs('admin.enquiries*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">✉</span>Enquiries & CRM</a>
@endif
@can('settings.manage')
<a href="{{ route('admin.developments.index') }}" @if(request()->routeIs('admin.developments.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◇</span>Future developments</a>
<a href="{{ route('admin.integrations') }}" @if(request()->routeIs('admin.integrations*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◎</span>Integrations</a>
<a href="{{ route('admin.enquiry-forms.edit') }}" @if(request()->routeIs('admin.enquiry-forms.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">▤</span>Forms & routing</a>
<a href="{{ route('admin.settings.edit') }}" @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">⚙</span>Website settings</a>
@endcan
@can('users.manage')
<a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">♙</span>Users</a>
@endcan
@can('roles.view')
<a href="{{ route('admin.roles') }}" @if(request()->routeIs('admin.roles*')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">◎</span>Roles & permissions</a>
@endcan
@can('audit.view')
<a href="{{ route('admin.audit') }}" @if(request()->routeIs('admin.audit')) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">≡</span>Audit log</a>
@endcan
</nav></details>
<div class="sidebar-bottom"><span class="status-dot"></span> Your website workspace</div>
</aside>
@endauth
<div class="workspace-main">
<header class="topbar"><a class="brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
@auth
<nav aria-label="Account" class="top-menu"><a href="{{ route('admin.search') }}" @if(request()->routeIs('admin.search')) aria-current="page" @endif>Search</a><a href="{{ route('home') }}">View website ↗</a><a href="{{ route('admin.account') }}" @if(request()->routeIs('admin.account')) aria-current="page" @endif>Settings</a><form method="post" action="{{ route('logout') }}">@csrf<button class="quiet">Sign out</button></form></nav>
@endauth
</header>
<main id="main">@if(session('status'))<p class="notice" role="status">{{ match(session('status')) { 'two-factor-authentication-enabled' => 'Scan the code below to finish setting up two-factor authentication.', 'two-factor-authentication-confirmed' => 'Two-factor authentication confirmed. You can now open the overview.', 'two-factor-authentication-disabled' => 'Two-factor authentication disabled.', 'recovery-codes-generated' => 'New recovery codes generated. Previous codes no longer work.', 'password-updated' => 'Your password was updated.', default => session('status') } }}</p>@endif
@if($errors->any())<div class="errors" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main></div></body></html>
