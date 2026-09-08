<?php

$roles = [
    'super-admin' => ['label' => 'Super Admin', 'permissions' => ['admin.view', 'users.manage', 'roles.view', 'audit.view', 'pages.view', 'pages.edit', 'pages.publish', 'projects.view', 'projects.edit', 'projects.publish', 'jobs.view', 'jobs.edit', 'jobs.publish', 'candidates.view', 'candidates.edit', 'candidates.export', 'seo.manage', 'campaigns.manage', 'media.manage', 'leads.view', 'leads.edit', 'leads.assign', 'leads.export', 'settings.manage']],
    'admin' => ['label' => 'Admin', 'permissions' => ['admin.view', 'pages.view', 'pages.edit', 'pages.publish', 'projects.view', 'projects.edit', 'projects.publish', 'jobs.view', 'jobs.edit', 'jobs.publish', 'seo.manage', 'media.manage']],
    'project-manager' => ['label' => 'Project Manager', 'permissions' => ['admin.view', 'projects.view', 'projects.edit']],
    'project-editor' => ['label' => 'Project Editor', 'permissions' => ['admin.view', 'projects.view-assigned', 'projects.edit-assigned']],
    'hr-manager' => ['label' => 'HR Manager', 'permissions' => ['admin.view', 'jobs.view', 'jobs.edit', 'jobs.publish', 'candidates.view', 'candidates.edit', 'candidates.export']],
    'hr-executive' => ['label' => 'HR Executive', 'permissions' => ['admin.view', 'candidates.view-assigned', 'candidates.edit-assigned']],
    'seo-manager' => ['label' => 'SEO Manager', 'permissions' => ['admin.view', 'seo.manage', 'pages.view', 'pages.edit']],
    'content-manager' => ['label' => 'Content Manager', 'permissions' => ['admin.view', 'pages.view', 'pages.edit']],
    'digital-marketing-manager' => ['label' => 'Digital Marketing Manager', 'permissions' => ['admin.view', 'campaigns.manage']],
    'media-manager' => ['label' => 'Media Manager', 'permissions' => ['admin.view', 'media.manage']],
    'business-development' => ['label' => 'Business Development', 'permissions' => ['admin.view', 'leads.view-assigned', 'leads.edit-assigned']],
    'viewer' => ['label' => 'Viewer', 'permissions' => ['admin.view']],
];

$actions = [
    'pages.edit' => ['pages.create'],
    'pages.publish' => ['pages.approve', 'pages.unpublish', 'pages.archive'],
    'media.manage' => ['media.upload', 'media.edit', 'media.publish'],
];
foreach ($roles as &$role) {
    foreach ($actions as $existing => $permissions) {
        if (in_array($existing, $role['permissions'])) {
            $role['permissions'] = [...$role['permissions'], ...$permissions];
        }
    }
}
unset($role);
$roles['super-admin']['permissions'][] = 'roles.manage';

return $roles;
