@extends('layouts.admin')
@section('title',$definition['singular'])
@section('content')
<p><a href="{{ route('admin.corporate.index',['type'=>$entry->type]) }}">← {{ $definition['label'] }}</a></p><h1>{{ $entry->exists ? 'Edit' : 'Create' }} {{ strtolower($definition['singular']) }}.</h1>
<p>Use approved first-party information. Leave unverified quantities, dates and claims empty. Published pages remain unchanged while you edit a draft.</p>
<form method="post" action="{{ $entry->exists ? route('admin.corporate.update',$entry) : route('admin.corporate.store') }}">
@csrf @if($entry->exists) @method('PUT') @endif
<input type="hidden" name="version" value="{{ $revision?->version??0 }}"><input type="hidden" name="type" value="{{ $entry->type }}">
<label for="title">{{ $entry->type==='team_member' ? 'Full name' : 'Title' }}</label><input id="title" name="title" value="{{ old('title',$payload['title']??'') }}" maxlength="180" required>
<label for="slug">URL slug</label><input id="slug" name="slug" value="{{ old('slug',$entry->slug??'') }}" maxlength="150" pattern="[a-zA-Z0-9_-]+" @readonly($entry->exists) required><p>Use a short descriptive slug. Published addresses stay stable; redirects will be managed through SEO.</p>
<label for="summary">Short introduction</label><textarea id="summary" name="summary" rows="3" maxlength="600">{{ old('summary',$payload['summary']??'') }}</textarea>
<label for="body">Detailed content</label><textarea id="body" name="body" rows="10" maxlength="30000">{{ old('body',$payload['body']??'') }}</textarea><p>Write useful, specific paragraphs. Publication requires an introduction and at least 80 characters of detail.</p>
@if($definition['fields'])<fieldset><legend>{{ $definition['singular'] }} details</legend>
@foreach($definition['fields'] as $field=>$options)
<label for="fact-{{ $field }}">{{ $options['label'] }}{{ ($options['required']??false) ? ' *' : '' }}</label>
@php($value=old('facts.'.$field,$payload['facts'][$field]??''))
@if(in_array($options['kind'],['select','relation']))<select id="fact-{{ $field }}" name="facts[{{ $field }}]" @required($options['required']??false)><option value="">Select {{ strtolower($options['label']) }}</option>
@if($options['kind']==='relation') @foreach($references[$field] as $reference)<option value="{{ $reference['id'] }}" @selected($value==$reference['id'])>{{ $reference['title'] }}</option>@endforeach
@else @foreach($options['options'] as $key=>$label)<option value="{{ $key }}" @selected($value===$key)>{{ $label }}</option>@endforeach @endif</select>
@if($options['kind']==='relation')<p>Only published records are available. Publish the related {{ strtolower(config('corporate.'.$options['related_type'].'.singular')) }} first.</p>@endif
@elseif($options['kind']==='textarea')<textarea id="fact-{{ $field }}" name="facts[{{ $field }}]" rows="4" @required($options['required']??false)>{{ $value }}</textarea>
@else<input type="{{ $options['kind'] }}" id="fact-{{ $field }}" name="facts[{{ $field }}]" value="{{ $value }}" @if($options['kind']==='number') min="{{ $options['min'] }}" max="{{ $options['max'] }}" @endif @required($options['required']??false)>@endif
@endforeach</fieldset>@endif
<details class="editor-group" open><summary>Images, film & downloads</summary>
@foreach(['cover_media_id'=>'Cover image','video_media_id'=>'Video'] as $key=>$label)<label for="{{ $key }}">{{ $label }}</label><select name="{{ $key }}" id="{{ $key }}"><option value="">None</option>@foreach($media->filter(fn($item)=>$key==='cover_media_id' ? str_starts_with($item->mime,'image/') : $item->mime==='video/mp4') as $asset)<option value="{{ $asset->id }}" @selected(old($key,$payload[$key]??null)==$asset->id)>{{ $asset->title }}</option>@endforeach</select>@endforeach
<p>Only public, published media appears here. Add image alt text, captions and a film description in Media. Real project and machinery imagery must be client-approved.</p>
@foreach(['gallery_ids'=>'Image gallery','document_ids'=>'Downloads'] as $key=>$label)<label for="{{ $key }}">{{ $label }}</label><select id="{{ $key }}" name="{{ $key }}[]" multiple size="5">@foreach($media->filter(fn($item)=>$key==='gallery_ids' ? str_starts_with($item->mime,'image/') : $item->mime==='application/pdf') as $asset)<option value="{{ $asset->id }}" @selected(in_array($asset->id,old('_form_submitted') ? old($key,[]) : ($payload[$key]??[])))>{{ $asset->title }}</option>@endforeach</select>@endforeach
</details>
<details class="editor-group"><summary>Related content & calls to action</summary>
<label for="related_ids">Related company records</label><select id="related_ids" name="related_ids[]" multiple size="5">@foreach($related as $relatedEntry)<option value="{{ $relatedEntry->id }}" @selected(in_array($relatedEntry->id,old('_form_submitted') ? old('related_ids',[]) : ($payload['related_ids']??[])))>{{ $relatedEntry->publishedRevision->payload['title'] }}</option>@endforeach</select>
<label for="cta_ids">Shared calls to action</label><select id="cta_ids" name="cta_ids[]" multiple size="4">@foreach($ctas as $cta)<option value="{{ $cta['id'] }}" @selected(in_array($cta['id'],old('_form_submitted') ? old('cta_ids',[]) : ($payload['cta_ids']??[])))>{{ $cta['title'] }}</option>@endforeach</select>
<p>Use Ctrl or Command to select or clear multiple items. Project and knowledge associations will connect in their respective modules.</p></details>
<details class="editor-group"><summary>Search appearance & ordering</summary>
<label for="seo_title">SEO title (company name added automatically)</label><input id="seo_title" name="seo_title" value="{{ old('seo_title',$payload['seo_title']??'') }}" maxlength="180">
<label for="seo_description">Meta description</label><textarea id="seo_description" name="seo_description" rows="3" maxlength="500">{{ old('seo_description',$payload['seo_description']??'') }}</textarea>
<label for="order">Display order</label><input id="order" name="order" type="number" min="0" max="1000" value="{{ old('order',$payload['order']??0) }}" required>
<label for="featured">Feature this record</label><select id="featured" name="featured"><option value="0" @selected(!old('featured',$payload['featured']??false))>No</option><option value="1" @selected(old('featured',$payload['featured']??false))>Yes</option></select></details>
<label for="source_note">Internal source / approval reference</label><textarea id="source_note" name="source_note" rows="3" maxlength="2000">{{ old('source_note',$payload['source_note']??'') }}</textarea><p>This note is private and never appears on the public page.</p><input type="hidden" name="_form_submitted" value="1"><button>Save draft</button>
</form>
@if($entry->exists) @include('admin.content.workflow') @endif
@endsection
