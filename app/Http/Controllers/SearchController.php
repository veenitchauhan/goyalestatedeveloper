<?php

namespace App\Http\Controllers;

use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Media;
use App\Services\CareerContent;
use App\Services\CorporateContent;
use App\Services\KnowledgeContent;
use App\Services\LocationContent;
use App\Services\ProjectContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->validate(['q' => 'nullable|string|max:150'])['q'] ?? '';
        $results = collect();
        if (mb_strlen(trim($q)) >= 2) {
            $pool = KnowledgeContent::items();
            foreach (['service' => CorporateContent::items('service'), 'project' => ProjectContent::items(), 'location' => LocationContent::active(), 'job' => CareerContent::openings()] as $type => $items) {
                foreach ($items as $item) {
                    $pool->push([...$item, 'type' => $type, 'url' => $type === 'job' ? route('careers.show', $item['slug']) : $item['url']]);
                }
            }
            $results = $pool->filter(fn ($item) => str_contains(mb_strtolower(implode(' ', array_intersect_key($item, array_flip(['title', 'short_answer', 'body', 'summary', 'description', 'category', 'topic'])))), mb_strtolower(trim($q))))->take(100);
        }

        return view('search', CorporateContent::layout('Search') + compact('q', 'results') + ['canonical' => route('search')]);
    }

    public function admin(Request $request): View
    {
        $q = $request->validate(['q' => 'nullable|string|max:150'])['q'] ?? '';
        $results = collect();
        if (mb_strlen(trim($q)) >= 2) {
            $user = $request->user();
            $types = [];
            if ($user->can('pages.view')) {
                $types = [...array_keys(KnowledgeContent::TYPES), ...array_keys(config('corporate')), 'location', 'page', 'block', 'statistic', 'menu', 'cta'];
            }
            if ($user->can('jobs.view')) {
                $types[] = 'job';
            }
            $entries = ContentEntry::where('title', 'like', '%'.trim($q).'%')->where(function ($query) use ($types, $user) {
                $query->whereIn('type', $types);
                if ($user->can('projects.view')) {
                    $query->orWhere('type', 'project');
                } elseif ($user->can('projects.view-assigned')) {
                    $query->orWhere(fn ($query) => $query->where('type', 'project')->whereHas('assignees', fn ($query) => $query->where('users.id', $user->id)));
                }
            })->limit(100)->get();
            foreach ($entries as $entry) {
                $results->push(['title' => $entry->title, 'type' => $entry->type, 'status' => $entry->status, 'url' => match ($entry->type) {
                    'project' => route('admin.projects.index'), 'job' => route('admin.jobs.index'), 'location' => route('admin.locations.index'),
                    default => KnowledgeContent::supports($entry->type) ? route('admin.knowledge.index', ['q' => $entry->title]) : (CorporateContent::supports($entry->type) ? route('admin.corporate.index') : route('admin.content.index')),
                }]);
            }
            if ($user->can('media.manage')) {
                foreach (Media::where('title', 'like', '%'.trim($q).'%')->limit(30)->get() as $media) {
                    $results->push(['title' => $media->title, 'type' => 'Media', 'status' => $media->publication_status, 'url' => route('admin.media.index')]);
                }
            }
            if ($user->can('leads.view') || $user->can('leads.view-assigned')) {
                foreach (Enquiry::where('name', 'like', '%'.trim($q).'%')->when(! $user->can('leads.view'), fn ($query) => $query->where('assigned_to', $user->id))->limit(30)->get() as $lead) {
                    $results->push(['title' => $lead->name, 'type' => 'Enquiry', 'status' => '', 'url' => route('admin.enquiries.show', $lead)]);
                }
            }
        }

        return view('admin.search', compact('q', 'results'));
    }
}
