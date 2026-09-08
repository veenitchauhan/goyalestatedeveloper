<?php

namespace App\Http\Controllers;

use App\Models\ContentEntry;
use App\Services\CorporateContent;
use App\Services\KnowledgeContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->route('type');
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'category' => 'nullable|string|max:100', 'topic' => 'nullable|string|max:150']);
        $all = KnowledgeContent::items($type);
        $items = $all->filter(fn ($item) => (! ($filters['category'] ?? null) || $item['category'] === $filters['category']) && (! ($filters['topic'] ?? null) || ($item['topic'] ?? '') === $filters['topic']) && (! ($filters['q'] ?? null) || str_contains(mb_strtolower(implode(' ', array_intersect_key($item, array_flip(['title', 'short_answer', 'body', 'category', 'topic'])))), mb_strtolower($filters['q']))));

        return view('knowledge.index', CorporateContent::layout(KnowledgeContent::TYPES[$type]) + compact('items', 'all', 'type', 'filters') + ['canonical' => route('knowledge.'.$type.'.index')]);
    }

    public function show(Request $request, string $slug): View
    {
        $entry = ContentEntry::where('type', $request->route('type'))->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();

        return KnowledgeContent::detail($entry, $entry->publishedRevision->payload);
    }
}
