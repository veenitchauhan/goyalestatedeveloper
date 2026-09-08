@php
    $cardImageIds = array_filter([$card['cover_media_id'] ?? null, ...($card['gallery_ids'] ?? [])]);
    $cardImage = \App\Models\Media::whereIn('id', $cardImageIds)->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'like', 'image/%')->get()->sortBy(fn ($image) => array_search($image->id, $cardImageIds))->first();
@endphp
@if($cardImage)
<a class="listing-card-image" href="{{ $card['url'] }}" tabindex="-1" aria-hidden="true"><img src="{{ route('media.show', $cardImage) }}" alt="{{ $cardImage->alt ?: $card['title'] }}" width="1200" height="800" loading="lazy"></a>
@endif
