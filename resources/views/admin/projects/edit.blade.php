@extends('layouts.admin')
@section('title', $entry->exists ? 'Edit project' : 'Add project')
@section('content')
<p class="eyebrow">PROJECTS & PROGRESS</p><h1>{{ $entry->exists ? $entry->title : 'Add a project' }}</h1><p><a href="{{ route('admin.projects.index') }}">← All projects</a></p>
@if($entry->exists && $entry->status!=='published')
<section role="status"><h2>These changes are not live yet</h2>
@if($entry->publishedRevision)<p>Visitors currently see: <strong>{{ $entry->publishedRevision->payload['title'] }}</strong>. Saving this form updates your draft only.</p>@else<p>This project has not been published. Saving this form creates a private draft.</p>@endif
<p>Save your changes, then use <a href="#project-publication">Review & publication</a>: Submit for review → Approve project → Publish project. Publication updates the website automatically.</p></section>
@endif
<form method="post" enctype="multipart/form-data" action="{{ $entry->exists ? route('admin.projects.update',$entry) : route('admin.projects.store') }}">@csrf @if($entry->exists) @method('PUT') @endif
<input type="hidden" name="version" value="{{ $revision?->version ?? 0 }}">
<section><h2>Project overview</h2><div class="form-grid">
<div><label for="title">Project name</label><input id="title" name="title" value="{{ old('title',$payload['title']??'') }}" required   maxlength="180"></div>
<div><label for="location">Site location / Address</label><input id="location" name="location" value="{{ old('location',$payload['location']??'') }}"    maxlength="255"></div>
<div><label for="city">City</label><input id="city" name="city" value="{{ old('city',$payload['city']??'') }}" required   maxlength="255"></div>
<div><label for="state">State</label><input id="state" name="state" value="{{ old('state',$payload['state']??'') }}"    maxlength="255"></div>
<div><label for="country">Country</label><input id="country" name="country" value="{{ old('country',$payload['country']??'') }}"    maxlength="255"></div>
<div><label for="project_area">Project area (include unit)</label><input id="project_area" name="project_area" value="{{ old('project_area',$payload['project_area']??'') }}"    maxlength="255"></div>
<div><label for="built_up_area">Built-up area (include unit)</label><input id="built_up_area" name="built_up_area" value="{{ old('built_up_area',$payload['built_up_area']??'') }}"    maxlength="255"></div>
<div><label for="client">Client / partner</label><input id="client" name="client" value="{{ old('client',$payload['client']??'') }}"    maxlength="255"></div>
<div><label for="project_value">Project value (include currency)</label><input id="project_value" name="project_value" value="{{ old('project_value',$payload['project_value']??'') }}"    maxlength="255"></div>

</div></section><section><h2>Status & dates</h2><div class="form-grid"><div><label for="status">Project status</label><select id="status" name="status">@foreach(\App\Services\ProjectContent::STATUSES as $option)<option @selected(old('status',$payload['status']??'')===$option)>{{ $option }}</option>@endforeach</select></div>
<div><label for="sector">Sector</label><select id="sector" name="sector">@foreach(\App\Services\ProjectContent::SECTORS as $option)<option @selected(old('sector',$payload['sector']??'')===$option)>{{ $option }}</option>@endforeach</select></div>
<div><label for="stage">Current construction stage</label><select id="stage" name="stage">@foreach(\App\Services\ProjectContent::STAGES as $option)<option @selected(old('stage',$payload['stage']??'')===$option)>{{ $option }}</option>@endforeach</select></div>
<div><label for="progress">Progress percentage</label><input type="number" id="progress" name="progress" min="0" max="100" value="{{ old('progress',$payload['progress']??0) }}" required></div>
<div><label for="start_date">Start date</label><input type="date" id="start_date" name="start_date" value="{{ old('start_date',$payload['start_date']??'') }}" ></div>

<div><label for="actual_completion">Actual completion</label><input type="date" id="actual_completion" name="actual_completion" value="{{ old('actual_completion',$payload['actual_completion']??'') }}" ></div>
</div></section><details class="editor-group" open><summary>Scope & project story</summary><label for="description">Project description</label><textarea id="description" name="description" rows="4" maxlength="15000">{{ old('description',$payload['description']??'') }}</textarea>
<label for="scope">Our scope of work</label><textarea id="scope" name="scope" rows="4" maxlength="15000">{{ old('scope',$payload['scope']??'') }}</textarea>
<label for="engineering">Engineering</label><textarea id="engineering" name="engineering" rows="4" maxlength="15000">{{ old('engineering',$payload['engineering']??'') }}</textarea>
<label for="construction">Construction</label><textarea id="construction" name="construction" rows="4" maxlength="15000">{{ old('construction',$payload['construction']??'') }}</textarea>
<label for="management">Project management</label><textarea id="management" name="management" rows="4" maxlength="15000">{{ old('management',$payload['management']??'') }}</textarea>
<label for="technical_highlights">Technical highlights</label><textarea id="technical_highlights" name="technical_highlights" rows="4" maxlength="15000">{{ old('technical_highlights',$payload['technical_highlights']??'') }}</textarea>
<label for="quality_safety">Quality & safety</label><textarea id="quality_safety" name="quality_safety" rows="4" maxlength="15000">{{ old('quality_safety',$payload['quality_safety']??'') }}</textarea>
<label for="outcome">Project outcome</label><textarea id="outcome" name="outcome" rows="4" maxlength="15000">{{ old('outcome',$payload['outcome']??'') }}</textarea>
</details><section><h2>Project images</h2><p>Click a card to add or replace a photo. Up to 5 images; the first image is the cover. Save the project to apply changes.</p>
<input type="hidden" name="image_selection" value="1"><input type="hidden" name="image_slots" value="1">
@php($canUpload=auth()->user()->can('media.manage') && auth()->user()->can('media.upload'))
@php($hasEmptyPhotoCard=false)
<div class="project-photo-grid">
@for($slot=0;$slot<5;$slot++)
@php($photo=old('image_selection')!==null ? $projectImages->firstWhere('id',old('keep_images.'.$slot)) : $projectImages->values()->get($slot))
<div class="project-photo-card" data-photo-card @if(!$photo && $hasEmptyPhotoCard) hidden @endif>
@php($hasEmptyPhotoCard=$hasEmptyPhotoCard || !$photo)
<input type="hidden" name="keep_images[{{ $slot }}]" value="{{ $photo?->id }}" data-keep-photo @disabled(!$photo)>
<input class="project-photo-input" type="file" id="project-photo-{{ $slot }}" name="images[{{ $slot }}]" accept=".jpg,.jpeg,.png,.webp" data-photo-input @disabled(!$canUpload)>
<button type="button" class="project-photo-picker" data-photo-picker aria-label="{{ $photo?'Replace':'Add' }} project image {{ $slot+1 }}" @disabled(!$canUpload)>
<img data-photo-preview src="{{ $photo ? route('media.show',$photo) : '' }}" alt="{{ $photo?->alt ?? '' }}" @if(!$photo) hidden @endif>
<span class="project-photo-empty" data-photo-empty @if($photo) hidden @endif><span aria-hidden="true">＋</span>Add image</span>
<span class="project-photo-replace" data-photo-replace @if(!$photo) hidden @endif>Click to replace</span>
</button>
<button type="button" class="project-photo-delete" data-photo-delete aria-label="Remove project image {{ $slot+1 }}" @if(!$photo) hidden @endif><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>
<p class="project-photo-caption" data-photo-caption>{{ $photo?->original_name ?? 'JPEG, PNG or WebP' }}</p>
</div>
@endfor
</div>
<p class="muted">Up to 20 MB per image. Removing a photo here keeps its original in the media library. After a validation error, select new files again.</p>
<p data-photo-status role="status" aria-live="polite"></p>
</section>
<details class="editor-group" open data-repeater="faqs"><summary>Project FAQs</summary><p class="muted">Add, remove or reorder items. Changes take effect after publication.</p><div data-rows>
@foreach(old('faqs',$payload['faqs']??[]) as $index=>$row)
<fieldset data-row><div class="form-grid"><div><label for="faqs-{{ $index }}-question">Question</label><input id="faqs-{{ $index }}-question" name="faqs[{{ $index }}][question]" type="text" value="{{ $row['question']??'' }}" ></div><div><label for="faqs-{{ $index }}-answer">Answer</label><textarea id="faqs-{{ $index }}-answer" name="faqs[{{ $index }}][answer]" rows="3">{{ $row['answer']??'' }}</textarea></div></div><button type="button" class="quiet" data-move="up">Move up</button> <button type="button" class="quiet" data-move="down">Move down</button> <button type="button" class="quiet" data-remove>Remove item</button></fieldset>
@endforeach</div>
@php($row=[])<template><fieldset data-row><div class="form-grid"><div><label for="faqs-__INDEX__-question">Question</label><input id="faqs-__INDEX__-question" name="faqs[__INDEX__][question]" type="text" value="{{ $row['question']??'' }}" ></div><div><label for="faqs-__INDEX__-answer">Answer</label><textarea id="faqs-__INDEX__-answer" name="faqs[__INDEX__][answer]" rows="3">{{ $row['answer']??'' }}</textarea></div></div><button type="button" class="quiet" data-move="up">Move up</button> <button type="button" class="quiet" data-move="down">Move down</button> <button type="button" class="quiet" data-remove>Remove item</button></fieldset></template><button type="button" data-add>Add item</button></details>
<section><h2>Publication options</h2><label for="client_approved">Client name approved for public display</label><select id="client_approved" name="client_approved"><option value="0" @selected(!old('client_approved',$payload['client_approved']??false))>No</option><option value="1" @selected(old('client_approved',$payload['client_approved']??false))>Yes</option></select>
<label for="value_approved">Project value approved for public display</label><select id="value_approved" name="value_approved"><option value="0" @selected(!old('value_approved',$payload['value_approved']??false))>No</option><option value="1" @selected(old('value_approved',$payload['value_approved']??false))>Yes</option></select>
<label for="featured">Feature this project</label><select id="featured" name="featured"><option value="0" @selected(!old('featured',$payload['featured']??false))>No</option><option value="1" @selected(old('featured',$payload['featured']??false))>Yes</option></select>
</section>
<section><h2>Verification & search</h2><label for="seo_title">SEO title</label><input id="seo_title" name="seo_title" value="{{ old('seo_title',$payload['seo_title']??'') }}"><label for="seo_description">SEO description</label><textarea id="seo_description" name="seo_description" rows="2">{{ old('seo_description',$payload['seo_description']??'') }}</textarea><label for="source_note">Source / approval reference (internal)</label><textarea id="source_note" name="source_note" rows="3">{{ old('source_note',$payload['source_note']??'') }}</textarea><input type="hidden" name="verified" value="0"><label><input type="checkbox" name="verified" value="1" @checked(old('verified',$payload['verified']??false))> These project facts are verified and approved for publication.</label><button>Save project draft</button></section></form>
@if($entry->exists)
<section id="project-publication"><h2>Review & publication</h2><p>{{ str($entry->status)->headline() }} · Version {{ $revision->version }}</p><a href="{{ route('admin.projects.preview',$entry) }}" target="_blank" rel="noopener">Preview saved draft ↗</a>
<form method="post" action="{{ route('admin.projects.transition',$entry) }}">@csrf<input type="hidden" name="version" value="{{ $revision->version }}"><label for="note">Review note</label><textarea id="note" name="note" rows="2"></textarea>
@if(in_array($entry->status,['draft','unpublished']))<button name="action" value="review">Submit for review</button>@endif
@can('publish',$entry)
@if($entry->status==='review')<button name="action" value="approve">Approve project</button>@endif
@if($entry->status==='approved')<button name="action" value="publish">Publish project</button><label for="schedule">Schedule ({{ config('app.timezone') }})</label><input type="datetime-local" name="scheduled_at" id="schedule"><button name="action" value="schedule">Schedule publication</button>@endif
@if(in_array($entry->status,['review','approved','scheduled']))<button name="action" value="return" class="quiet">Return to draft</button>@endif
@if($entry->published_revision_id)<button name="action" value="unpublish" class="quiet">Unpublish project</button>@endif
@if($entry->status!=='archived')<button name="action" value="archive" class="quiet">Archive project</button>@else<button name="action" value="restore">Restore as draft</button>@endif
@endcan
</form><details><summary>Revision history</summary>@foreach($entry->revisions()->latest('version')->get() as $item)<p>Version {{ $item->version }} · {{ $item->created_at }} @if($entry->published_revision_id===$item->id) · Published @endif</p>@endforeach</details></section>
@can('publish',$entry)<section><h2>Assigned project editors</h2><p>Assignments grant access only to users whose role includes assigned-project permissions.</p><form method="post" action="{{ route('admin.projects.assign',$entry) }}">@csrf<label for="assignees">Team members</label><select id="assignees" name="assignees[]" multiple size="5">@foreach($users as $user)<option value="{{ $user->id }}" @selected($entry->assignees->contains($user))>{{ $user->name }}</option>@endforeach</select><button>Save assignments</button></form></section>@endcan
@endif
<script src="{{ asset('assets/project-editor.js') }}?v={{ filemtime(public_path('assets/project-editor.js')) }}" defer></script>
@endsection
