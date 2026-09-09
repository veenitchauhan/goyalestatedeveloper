@extends('layouts.admin')
@section('title','Overview')
@section('content')<p class="eyebrow">YOUR WORKSPACE</p><h1>Welcome, {{ auth()->user()->name }}.</h1><p>Manage your website content, media and enquiries from one place.</p><div class="cards">
@if(auth()->user()->can('projects.view') || auth()->user()->can('projects.view-assigned'))<section><h2>Projects & progress</h2><p>Manage project information, progress stages and site galleries.</p><a href="{{ route('admin.projects.index') }}">Open projects →</a></section>@endif
@can('pages.view')<section><h2>Locations & expansion</h2><p>Manage verified operating areas and planned expansion.</p><a href="{{ route('admin.locations.index') }}">Open locations →</a></section>@endcan
@can('jobs.view')<section><h2>Careers & openings</h2><p>Create roles, internships and graduate opportunities.</p><a href="{{ route('admin.jobs.index') }}">Manage openings →</a></section>@endcan
@if(auth()->user()->can('candidates.view') || auth()->user()->can('candidates.view-assigned'))<section><h2>Candidate pipeline</h2><p>Review applications and coordinate recruitment.</p><a href="{{ route('admin.candidates.index') }}">Review candidates →</a></section>@endif
@can('media.manage')<section><h2>Media library</h2><p>Manage images and documents, visibility and branded image versions.</p><a href="{{ route('admin.media.index') }}">Open media →</a></section>@endcan
@if(auth()->user()->can('leads.view') || auth()->user()->can('leads.view-assigned'))<section><h2>Website enquiries</h2><p>Read enquiries submitted through the website.</p><a href="{{ route('admin.enquiries') }}">View enquiries →</a></section>@endif
@can('users.manage')<section><h2>Users</h2><p>Create users, assign roles and manage account access.</p><a href="{{ route('admin.users.index') }}">Manage users →</a></section>@endcan
@can('roles.view')<section><h2>Roles & permissions</h2><p>Review access boundaries and manage permitted actions.</p><a href="{{ route('admin.roles') }}">View roles →</a></section>@endcan
@can('audit.view')<section><h2>Activity history</h2><p>Review who changed access, content and publication settings.</p><a href="{{ route('admin.audit') }}">View activity →</a></section>@endcan
@can('pages.view')<section><h2>Knowledge & FAQs</h2><p>Publish practical guides and answers to common questions.</p><a href="{{ route('admin.knowledge.index') }}">Manage knowledge →</a></section>@endcan
@can('seo.manage')<section><h2>Search visibility</h2><p>Review page metadata, indexing and redirects.</p><a href="{{ route('admin.seo.index') }}">Review SEO →</a></section>@endcan
@can('campaigns.manage')<section><h2>Campaigns & analytics</h2><p>Manage landing pages and review consented website activity.</p><a href="{{ route('admin.campaigns.index') }}">Manage campaigns →</a><a href="{{ route('admin.analytics') }}">View analytics →</a></section>@endcan
@can('settings.manage')<section><h2>Future developments</h2><p>Prepare development pages, inventory and site-visit enquiries.</p><a href="{{ route('admin.developments.index') }}">Manage developments →</a></section>@endcan
<section><h2>Personal settings</h2><p>View your account details and update your own password.</p><a href="{{ route('admin.account') }}">Open settings →</a></section></div>
@endsection
