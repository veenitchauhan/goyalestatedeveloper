<section class="cms-workflow"><h2>Visibility</h2><p>Administrator saves update the website immediately. No review or approval is required.</p><p>Status: {{ str($entry->status)->headline() }} · Version {{ $revision->version }}</p>
<form method="post" action="{{ route($transitionRoute ?? 'admin.content.transition',$entry) }}">@csrf<input type="hidden" name="version" value="{{ $revision->version }}">
@if(!$entry->published_revision_id && $entry->status!=='archived')<button name="action" value="publish">Publish saved content</button>@endif
@if(!in_array($entry->type,['homepage','settings']))
@if($entry->published_revision_id)<button name="action" value="unpublish" class="quiet">Hide from website</button>@endif
@if($entry->status==='archived')<button name="action" value="restore">Restore for editing</button>@else<button name="action" value="archive" class="quiet">Archive</button>@endif
@endif</form></section>
