@extends('layouts.admin')
@section('title','Candidate pipeline')
@section('content')
<p class="eyebrow">RECRUITMENT</p><h1>Candidate pipeline</h1><form method="get" class="form-grid"><div><label for="q">Search name, email or opening</label><input id="q" name="q" value="{{ request('q') }}"></div><div><label for="status">Status</label><select id="status" name="status"><option value="">All stages</option>@foreach(\App\Models\Candidate::STATUSES as $status)<option @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select></div><button>Filter candidates</button></form>
@can('candidates.export')<p><a href="{{ route('admin.candidates.export',request()->only('q','status')) }}">Export filtered candidates (CSV) ↓</a></p>@endcan
<section><table><thead><tr><th>Name</th><th>Opening</th><th>Status</th><th>Applied</th></tr></thead><tbody>@forelse($candidates as $candidate)<tr><td><a href="{{ route('admin.candidates.show',$candidate) }}">{{ $candidate->name }}</a></td><td>{{ $candidate->job_title }}</td><td>{{ $candidate->status }}</td><td>{{ $candidate->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="4">No applications in this view.</td></tr>@endforelse</tbody></table></section>{{ $candidates->links('pagination') }}
@endsection
