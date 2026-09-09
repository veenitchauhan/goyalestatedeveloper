<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\ContentEntry;
use App\Models\DevelopmentSpace;
use App\Models\Enquiry;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\ContentPublisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    private array $entries = [];

    private const COPY = 'DEMO CONTENT — fictional example for layout review only. This is not a company project, credential, employee, vacancy or business claim. Replace it with approved information before launch.';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo content is restricted to local development.');
        }
        if (SiteSetting::where('key', 'demo_content')->exists()) {
            $this->command?->info('Demo content already exists; existing records were preserved.');

            return;
        }
        DB::transaction(function (): void {
            $images = [];
            foreach (['construction', 'infrastructure', 'delivery', 'presence', 'construction-journey'] as $name) {
                $path = 'demo-content/'.$name.'.webp';
                Storage::disk('local')->put($path, file_get_contents(public_path('assets/architecture/'.$name.'.webp')));
                $images[] = Media::create(['title' => 'Demo — '.Str::headline($name), 'original_name' => $name.'.webp', 'original_path' => $path, 'web_path' => $path, 'mime' => 'image/webp', 'category' => 'Company', 'alt' => 'Demo architectural concept — '.Str::headline($name), 'caption' => self::COPY, 'is_public' => true, 'publication_status' => 'published'])->id;
            }
            $corporate = [];
            foreach (config('corporate') as $type => $definition) {
                $variants = match ($type) {
                    'company_page' => ['Our story', 'Our approach', 'Quality', 'Safety', 'Sustainability', 'Future vision'],
                    'business_unit' => ['Construction', 'Infrastructure', 'Project delivery'],
                    'capability' => ['Engineering & planning', 'Site execution', 'Quality & safety'],
                    default => [$definition['singular']],
                };
                foreach ($variants as $index => $name) {
                    $facts = [];
                    foreach ($definition['fields'] as $field => $options) {
                        $facts[$field] = match ($options['kind']) {
                            'select' => array_keys($options['options'])[$index % count($options['options'])],
                            'number' => $options['min'],
                            'date' => now()->subYear()->toDateString(),
                            'relation' => $corporate[$options['related_type']],
                            default => 'Demo '.$options['label'],
                        };
                    }
                    $entry = $this->entry($type, $name, ['facts' => $facts, 'cover_media_id' => $images[$index % 3], 'gallery_ids' => [$images[0], $images[2]]]);
                    $corporate[$type] ??= $definition['model']::where('content_entry_id', $entry->id)->firstOrFail()->id;
                }
            }
            $parent = null;
            foreach (['country', 'state', 'region', 'city'] as $level) {
                $parent = $this->entry('location', 'Sample '.$level, ['level' => $level, 'presence' => 'current', ...($parent ? ['parent_id' => $parent->id] : []), 'service_ids' => []]);
            }
            foreach (['Commercial campus', 'River crossing', 'Residential structure'] as $index => $name) {
                $this->entry('project', $name, ['project_type' => 'Demo construction', 'status' => ['Ongoing', 'Completed', 'Upcoming'][$index], 'sector' => ['Commercial', 'Infrastructure', 'Residential'][$index], 'stage' => ['Structure', 'Completed', 'Planning'][$index], 'progress' => [55, 100, 10][$index], 'progress_date' => now()->toDateString(), 'city' => 'Demo Sample city', 'location' => 'Demo review location', 'location_entry_id' => $parent->id, 'client_approved' => false, 'value_approved' => false, 'description' => self::COPY, 'scope' => "Sample scope for reviewing the project detail page.\nPlanning, structural coordination and quality review.", 'cover_media_id' => $images[$index], 'gallery' => [['media_id' => $images[$index], 'category' => 'Construction', 'stage' => 'Demo stage', 'visible' => true]], 'timeline' => [], 'faqs' => [['question' => 'Is this a real company project?', 'answer' => 'No. This is fictional demo content for review.']]]);
            }
            foreach (['Site engineer', 'Planning coordinator', 'Graduate trainee'] as $index => $name) {
                $this->entry('job', $name, ['job_type' => $index === 2 ? 'Graduate opportunity' : 'Permanent', 'employment_type' => 'Full-time', 'department' => 'Demo Engineering', 'location' => 'Demo review location', 'experience' => 'Demo: 0–3 years', 'deadline' => now()->addMonths(3)->toDateString(), 'salary_public' => false, 'description' => self::COPY, 'responsibilities' => "Demo responsibilities:\nReview drawings and coordinate sample project activities.", 'requirements' => 'Demo requirements for reviewing this template. Not an actual vacancy.', 'skills' => 'Planning, communication, drawing review', 'education' => 'Demo qualification']);
            }
            foreach (['knowledge' => ['Understanding construction stages', 'Preparing a project brief'], 'faq' => ['How do I send an enquiry?', 'Are these actual company projects?']] as $type => $titles) {
                foreach ($titles as $title) {
                    $this->entry($type, $title, ['category' => 'Demo construction guide', 'topic' => 'Demo project planning', 'short_answer' => self::COPY, 'explanation' => 'Sample supporting explanation for layout review.', 'author' => 'Demo editorial team', 'reviewer' => 'Demo reviewer', 'schema_enabled' => false, 'related_ids' => []]);
                }
            }
            foreach (['page' => 'Sample information page', 'block' => 'Reusable introduction', 'cta' => 'Contact the team', 'statistic' => 'Sample review metric'] as $type => $title) {
                $this->entry($type, $title, ['url' => '/contact', 'value' => '12', 'label' => 'Demo only', 'placement' => 'footer']);
            }
            $this->entry('campaign', 'Project consultation', ['headline' => 'Demo — Let’s plan a project', 'subheadline' => self::COPY, 'form_type' => 'Construction', 'cta_label' => 'Review enquiry form', 'cover_media_id' => $images[0], 'related_ids' => [], 'statistic_ids' => []]);
            $development = $this->entry('development', 'Future neighbourhood', ['category' => 'Mixed Use', 'location' => 'Demo review location', 'progress' => 30, 'amenities' => 'Demo landscaped spaces and shared areas. Illustrative only.', 'gallery_ids' => [$images[3]], 'masterplan_id' => $images[3]]);
            $tower = DevelopmentSpace::create(['content_entry_id' => $development->id, 'kind' => 'tower', 'name' => 'Demo Tower A', 'status' => 'Available', 'is_public' => true, 'details' => ['x' => 50, 'y' => 50], 'version' => 1]);
            $floor = DevelopmentSpace::create(['content_entry_id' => $development->id, 'parent_id' => $tower->id, 'kind' => 'floor', 'name' => 'Demo Floor 1', 'status' => 'Available', 'is_public' => true, 'details' => [], 'version' => 1]);
            foreach (['Available', 'Hold', 'Booked'] as $index => $status) {
                DevelopmentSpace::create(['content_entry_id' => $development->id, 'parent_id' => $floor->id, 'kind' => 'unit', 'name' => 'Demo Unit '.($index + 101), 'status' => $status, 'is_public' => true, 'details' => ['type' => 'Demo apartment', 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 1200, 'area_unit' => 'sq ft', 'price_public' => false], 'version' => 1]);
            }
            $leads = [];
            foreach (Enquiry::STATUSES as $index => $status) {
                $leads[] = Enquiry::create(['name' => 'Demo Enquirer '.($index + 1), 'email' => 'demo-lead-'.$index.'@example.test', 'type' => 'Construction', 'location' => 'Demo city', 'message' => self::COPY, 'status' => $status, 'consented_at' => now(), 'consent_version' => 'synthetic-demo', 'details' => ['demo' => true], 'attribution' => ['utm_campaign' => 'Demo review'], 'follow_up_at' => now()->addDays($index - 3)])->id;
            }
            $candidates = [];
            $resumePath = 'demo-content/sample-resume.pdf';
            Storage::disk('local')->put($resumePath, $this->sampleResume());
            foreach (['New', 'Shortlisted', 'Interview', 'Selected'] as $index => $status) {
                $candidates[] = Candidate::create(['job_title' => 'Demo Site engineer', 'name' => 'Demo Candidate '.($index + 1), 'email' => 'demo-candidate-'.$index.'@example.test', 'phone' => '0000000000', 'profile' => ['experience' => 'Demo profile', 'skills' => 'Sample drawing review'], 'resume_path' => $resumePath, 'status' => $status, 'consented_at' => now(), 'internal_notes' => self::COPY])->id;
            }
            $previous = SiteSetting::where('key', 'future_developments')->first()?->data;
            SiteSetting::updateOrCreate(['key' => 'future_developments'], ['data' => ['enabled' => true, 'version' => ($previous['version'] ?? 0) + 1]]);
            SiteSetting::create(['key' => 'demo_content', 'data' => ['entry_ids' => $this->entries, 'media_ids' => $images, 'enquiry_ids' => $leads, 'candidate_ids' => $candidates, 'previous_developments' => $previous]]);
            $this->command?->info(count($this->entries).' demo content records, '.count($images).' images, '.count($leads).' enquiries and '.count($candidates).' candidates created.');
        });
    }

    private function sampleResume(): string
    {
        $stream = 'BT /F1 18 Tf 50 740 Td (DEMO RESUME - FICTIONAL CANDIDATE) Tj 0 -35 Td /F1 11 Tf (Sample document for CMS review. Not an actual applicant.) Tj ET';
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private function entry(string $type, string $name, array $extra = []): ContentEntry
    {
        $slug = 'demo-'.Str::slug($name);
        if (ContentEntry::where('type', $type)->where('slug', $slug)->exists()) {
            throw new \RuntimeException('A demo slug already exists; existing content was preserved.');
        }
        $entry = ContentEntry::create(['type' => $type, 'slug' => $slug, 'title' => 'Demo — '.$name]);
        $payload = array_replace(['type' => $type, 'slug' => $slug, 'title' => $entry->title, 'summary' => self::COPY, 'body' => self::COPY."\n\nThis example demonstrates the page structure, supporting imagery and related enquiry journey. Use the CMS to edit this material during review.", 'order' => 0, 'featured' => true, 'verified' => true, 'source_note' => 'User-authorized synthetic demo data, verified as fictional only.', 'seo_title' => $entry->title, 'seo_description' => self::COPY], $extra);
        $publisher = app(ContentPublisher::class);
        $publisher->save($entry, $payload, 0);
        foreach (['review', 'approve', 'publish'] as $action) {
            $publisher->transition($entry, $action, 1);
        }
        $this->entries[] = $entry->id;

        return $entry;
    }
}
