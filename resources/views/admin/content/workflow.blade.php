<section class="cms-workflow"><h2>Review & publication</h2><p>Status: <strong>{{ str($entry->status)->headline() }}</strong> · Draft version {{ $revision->version }}@if($entry->scheduled_at) · Scheduled: {{ $entry->scheduled_at->timezone(config('app.timezone'))->format('d M Y H:i') }} {{ config('app.timezone') }}@endif</p>
<p><a href="{{ route('admin.content.preview',$entry) }}" target="_blank" rel="noopener">Preview saved draft ↗</a> · Save your changes before previewing or publishing.</p>
<form method="post" action="{{ route('admin.content.transition',$entry) }}">@csrf<input type="hidden" name="version" value="{{ $revision->version }}">
<label for="review-note">Review note (optional)</label><textarea name="note" id="review-note" rows="2" maxlength="1000"></textarea>
@if(in_array($entry->status,['draft','unpublished']))<button name="action" value="review">Submit for review</button>@endif
@can('pages.approve')
@if($entry->status==='review')<button name="action" value="approve">Approve saved draft</button>@endif
@if(in_array($entry->status,['review','approved','scheduled']))<button class="quiet" name="action" value="return">Return to draft / cancel schedule</button>@endif
@endcan
@can('pages.publish') @if($entry->status==='approved')
<button name="action" value="publish">Publish approved draft</button>
<label for="scheduled-at">Schedule publication ({{ config('app.timezone') }})</label><input type="datetime-local" id="scheduled-at" name="scheduled_at"><button name="action" value="schedule">Schedule approved draft</button>
@endif @endcan
@if(!in_array($entry->type,['homepage','settings']))
@can('pages.unpublish') @if($entry->published_revision_id)<button class="quiet" name="action" value="unpublish">Unpublish</button>@endif @endcan
@can('pages.archive') @if($entry->status!=='archived')<button class="quiet" name="action" value="archive">Archive and remove from website</button>@endif @endcan
@if($entry->status==='archived')<button name="action" value="restore">Restore archived content as draft</button>@endif
@endif
</form>
<details><summary>Revision history</summary><ul>@foreach($entry->revisions()->latest('version')->get() as $item)<li>Version {{ $item->version }} · {{ $item->created_at }} @if($entry->published_revision_id===$item->id) · Published @endif<form method="post" action="{{ route('admin.content.restore',$entry) }}">@csrf<input type="hidden" name="version" value="{{ $revision->version }}"><input type="hidden" name="revision_id" value="{{ $item->id }}"><button class="quiet">Restore version {{ $item->version }} as draft</button></form></li>@endforeach</ul>
<ul>@foreach(\Illuminate\Support\Facades\DB::table('approval_events')->whereIn('content_revision_id',$entry->revisions()->select('id'))->latest('id')->get() as $event)<li>{{ str($event->action)->headline() }} · {{ $event->created_at }}@if($event->note) — {{ $event->note }}@endif</li>@endforeach</ul></details></section>
