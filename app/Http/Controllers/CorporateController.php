<?php

namespace App\Http\Controllers;

use App\Services\CorporateContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorporateController extends Controller
{
    public function index(Request $request): View
    {
        $group = $request->route('group');
        [$title, $introduction, $types] = match ($group) {
            'about' => ['Strong foundations. A bigger vision.', 'Discover the company, our approach and the people behind the work.', ['company_page', 'team_member', 'company_milestone', 'employee_story']],
            'business' => ['Built for complex projects.', 'Construction, infrastructure and project delivery.', ['business_unit', 'service']],
            'capabilities' => ['From plan to project.', 'Explore the capabilities that support project execution.', ['capability']],
            'equipment' => ['Equipment & machinery.', 'Supporting project execution with specialised construction equipment deployed according to project requirements.', ['equipment']],
            'leadership' => ['Leadership & people.', 'The people and experience behind the company.', ['team_member']],
            'journey' => ['Our journey.', 'Company milestones and leadership experience, presented as separate timelines.', ['company_milestone']],
            'stories' => ['Employee stories.', 'First-person perspectives from our people.', ['employee_story']],
        };
        $groups = collect($types)->mapWithKeys(fn (string $type) => [$type => CorporateContent::items($type)]);

        return view('corporate-index', CorporateContent::layout($title, $introduction) + compact('title', 'introduction', 'groups', 'group') + ['canonical' => $request->url()]);
    }

    public function show(Request $request, string $slug): View
    {
        $type = $request->route('type');
        $record = CorporateContent::query($type)->whereHas('entry', fn ($query) => $query->where('slug', $slug))->firstOrFail();

        return CorporateContent::detail($record->entry, $record->entry->publishedRevision->payload);
    }
}
