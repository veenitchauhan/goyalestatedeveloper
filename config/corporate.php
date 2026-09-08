<?php

use App\Models\BusinessUnit;
use App\Models\Capability;
use App\Models\CompanyMilestone;
use App\Models\CompanyPage;
use App\Models\EmployeeStory;
use App\Models\Equipment;
use App\Models\Service;
use App\Models\TeamMember;

return [
    'company_page' => ['label' => 'Company pages', 'singular' => 'Company page', 'model' => CompanyPage::class, 'route' => 'corporate.company', 'group' => 'about', 'fields' => [
        'topic' => ['label' => 'Topic', 'kind' => 'select', 'options' => ['story' => 'Our story', 'approach' => 'Our approach', 'quality' => 'Quality', 'safety' => 'Safety', 'sustainability' => 'Sustainability', 'future-vision' => 'Future vision'], 'required' => true],
    ]],
    'business_unit' => ['label' => 'Business areas', 'singular' => 'Business area', 'model' => BusinessUnit::class, 'route' => 'corporate.business', 'group' => 'business', 'fields' => []],
    'service' => ['label' => 'Services', 'singular' => 'Service', 'model' => Service::class, 'route' => 'corporate.service', 'group' => 'business', 'fields' => [
        'business_unit_id' => ['label' => 'Business area', 'kind' => 'relation', 'related_type' => 'business_unit', 'required' => true],
        'delivery_scope' => ['label' => 'Scope of delivery', 'kind' => 'textarea', 'required' => true],
        'requirements' => ['label' => 'Information needed to discuss a project', 'kind' => 'textarea'],
    ]],
    'capability' => ['label' => 'Capabilities', 'singular' => 'Capability', 'model' => Capability::class, 'route' => 'corporate.capability', 'group' => 'capabilities', 'fields' => [
        'focus_area' => ['label' => 'Focus area', 'kind' => 'text'],
        'approach' => ['label' => 'Our approach', 'kind' => 'textarea'],
    ]],
    'equipment' => ['label' => 'Equipment & machinery', 'singular' => 'Equipment', 'model' => Equipment::class, 'route' => 'corporate.equipment', 'group' => 'capabilities', 'fields' => [
        'category' => ['label' => 'Category', 'kind' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'kind' => 'text'],
        'manufacturer' => ['label' => 'Manufacturer', 'kind' => 'text'],
        'capacity' => ['label' => 'Capacity', 'kind' => 'text'],
        'application' => ['label' => 'Application', 'kind' => 'textarea'],
        'quantity' => ['label' => 'Approved quantity (leave blank if unverified)', 'kind' => 'number', 'min' => 1, 'max' => 100000],
        'operating_status' => ['label' => 'Status', 'kind' => 'text'],
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
