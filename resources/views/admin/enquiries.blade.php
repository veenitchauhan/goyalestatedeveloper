@extends('layouts.admin')
@section('title','Website enquiries')
@section('content')<h1>Website enquiries.</h1><p>These submissions are stored locally. Email and external CRM delivery are not connected.</p>@forelse($enquiries as $enquiry)<section><h2>{{ $enquiry->name }} · {{ $enquiry->type }}</h2><p>{{ $enquiry->email }} · {{ $enquiry->phone }} · {{ $enquiry->location }}</p><p>{{ $enquiry->message }}</p><small>{{ $enquiry->created_at }} UTC · {{ $enquiry->status }}</small></section>@empty<p>No enquiries received yet.</p>@endforelse{{ $enquiries->links('pagination') }}@endsection
