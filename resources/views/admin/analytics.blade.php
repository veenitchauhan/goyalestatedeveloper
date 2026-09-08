@extends('layouts.admin')
@section('title','Analytics')
@section('content')
<p class="eyebrow">LAST 30 DAYS</p><h1>Website & campaign performance</h1><p>Traffic figures include only opted-in browser sessions. They are not a count of all visitors. Lead totals include received enquiries; campaign labels are attribution hints.</p><div class="cards"><section><h2>{{ $pageViews }}</h2><p>Consented page views</p></section><section><h2>{{ $sessions }}</h2><p>Consented browser sessions</p></section><section><h2>{{ $conversions }}</h2><p>Consented enquiry events</p></section></div>
@foreach(['pages'=>'Top page paths','sources'=>'Page-view sources','campaignLeads'=>'Campaign enquiries','leadStatuses'=>'Lead pipeline','leadTypes'=>'Enquiry types'] as $key=>$label)<section><h2>{{ $label }}</h2>@forelse($$key as $name=>$count)<p>{{ $name }} <strong>{{ $count }}</strong></p>@empty<p>No data for this period.</p>@endforelse</section>@endforeach
@endsection
