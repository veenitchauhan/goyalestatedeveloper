@extends('layouts.admin')
@section('title','Audit log')
@section('content')<p class="eyebrow">ACCOUNTABILITY</p><h1>Activity history.</h1><p>Who changed access, content or media, and which values changed. Passwords, authenticator secrets and recovery codes are excluded. Content revision numbers refer to the saved revision history.</p>
@forelse($logs as $log)<section><h2>{{ str($log->action)->replace('.',' · ')->replace('_',' ')->headline() }}</h2><p>{{ $log->created_at->format('d M Y H:i:s') }} UTC · {{ $log->actor?->name ?? ($log->actor_id ? 'Former user #'.$log->actor_id : 'System') }} · {{ class_basename($log->subject_type??'Account') }} #{{ $log->subject_id??'—' }}</p>
@if($log->changes)
@php
$fields=collect(array_keys($log->changes))->map(fn($key)=>preg_replace('/^(before|after)_/','',$key))->unique();
$display=fn($value)=>is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):(is_bool($value)?($value?'Yes':'No'):($value??'—'));
@endphp
<table><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>@foreach($fields as $field)<tr><th>{{ str($field)->replace('_',' ')->headline() }}</th><td>{{ $display($log->changes['before_'.$field]??null) }}</td><td>{{ $display($log->changes['after_'.$field]??null) }}</td></tr>@endforeach</tbody></table>@endif</section>@empty<section><p>No activity recorded yet.</p></section>@endforelse{{ $logs->links('pagination') }}@endsection
