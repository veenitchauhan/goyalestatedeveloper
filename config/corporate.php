<?php

use App\Models\CompanyMilestone;
use App\Models\CompanyPage;
use App\Models\EmployeeStory;
use App\Models\TeamMember;

return [
    'company_page' => ['label' => 'Company pages', 'singular' => 'Company page', 'model' => CompanyPage::class, 'route' => 'corporate.company', 'group' => 'about', 'fields' => [
        'topic' => ['label' => 'Topic', 'kind' => 'select', 'options' => ['story' => 'Our story', 'approach' => 'Our approach', 'quality' => 'Quality', 'safety' => 'Safety', 'sustainability' => 'Sustainability', 'future-vision' => 'Future vision'], 'required' => true],
    ]],
    'team_member' => ['label' => 'Leadership & people', 'singular' => 'Team member', 'model' => TeamMember::class, 'route' => 'corporate.person', 'group' => 'about', 'fields' => [
        'designation' => ['label' => 'Designation', 'kind' => 'text', 'required' => true],
        'department' => ['label' => 'Department', 'kind' => 'text'],
        'experience' => ['label' => 'Leadership / individual experience (distinct from company history)', 'kind' => 'textarea'],
        'joining_year' => ['label' => 'Joining year', 'kind' => 'number', 'min' => 1900, 'max' => (int) date('Y')],
    ]],
    'company_milestone' => ['label' => 'Journey & milestones', 'singular' => 'Milestone', 'model' => CompanyMilestone::class, 'route' => 'corporate.milestone', 'group' => 'about', 'fields' => [
        'occurred_on' => ['label' => 'Date', 'kind' => 'date', 'required' => true],
        'timeline' => ['label' => 'Timeline', 'kind' => 'select', 'options' => ['company' => 'Company journey', 'leadership' => 'Leadership / promoter experience'], 'required' => true],
    ]],
    'employee_story' => ['label' => 'Employee stories', 'singular' => 'Employee story', 'model' => EmployeeStory::class, 'route' => 'corporate.story', 'group' => 'about', 'fields' => [
        'team_member_id' => ['label' => 'Employee', 'kind' => 'relation', 'related_type' => 'team_member', 'required' => true],
    ]],
];
