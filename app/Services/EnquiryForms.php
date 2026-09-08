<?php

namespace App\Services;

use App\Models\SiteSetting;

class EnquiryForms
{
    public const TYPES = ['Construction', 'Infrastructure', 'Industrial', 'Project Enquiry', 'Development', 'Land/JV', 'Vendor', 'Career', 'General'];

    public const ALIASES = ['Project delivery' => 'Project Enquiry', 'Development opportunity' => 'Development', 'Vendor / partner' => 'Vendor'];

    public const FIELDS = ['company' => 'Company', 'project_type' => 'Project type', 'land_area' => 'Land / built-up area', 'ownership' => 'Ownership', 'property_type' => 'Property type', 'development_intent' => 'Development intent', 'expected_start' => 'Expected start', 'requirement' => 'Requirement / scope'];

    public static function current(): array
    {
        $stored = SiteSetting::where('key', 'enquiry_forms')->first()?->data ?? [];
        $forms = [];
        foreach (self::TYPES as $type) {
            $forms[$type] = array_replace(['enabled' => true, 'title' => $type.' enquiry', 'intro' => 'Tell us about your requirement. Our team will review your enquiry.', 'assigned_to' => null, 'notify_owner' => false, 'fields' => match ($type) {
                'Development', 'Land/JV' => ['land_area', 'ownership', 'property_type', 'development_intent', 'expected_start'], 'Vendor' => ['company', 'requirement'], 'Career', 'General' => ['requirement'], default => ['company', 'project_type', 'land_area', 'expected_start', 'requirement']
            }, 'required_fields' => []], $stored['forms'][$type] ?? []);
        }

        return ['version' => $stored['version'] ?? 0, 'forms' => $forms];
    }

    public static function enabled(): array
    {
        return array_filter(self::current()['forms'], fn ($form) => $form['enabled']);
    }
}
