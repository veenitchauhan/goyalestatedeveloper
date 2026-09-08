<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
        Storage::fake('local');
    }

    private function login(string $role): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function job(): ContentEntry
    {
        $this->login('hr-manager');
        $this->post('/admin/jobs', ['title' => 'Test Engineer', 'slug' => 'test-engineer', 'version' => 0, 'department' => 'Engineering', 'location' => 'Test city', 'experience' => 'Two years', 'job_type' => 'Permanent', 'employment_type' => 'Full-time', 'salary' => 'Private salary', 'salary_public' => false, 'verified' => true, 'description' => 'Approved test description', 'responsibilities' => 'Approved duties', 'requirements' => 'Approved requirements', 'source_note' => 'Internal approval', 'deadline' => now()->addDays(5)->toDateString()])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'test-engineer')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/jobs/'.$entry->id.'/status', ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }

        return $entry;
    }

    private function application(array $extra = []): array
    {
        return array_replace(['name' => 'Test Applicant', 'email' => 'applicant@example.test', 'phone' => '1234567890', 'location' => 'Test city', 'qualification' => 'Engineering', 'experience' => 'Two years', 'consent' => 1, 'resume' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF")], $extra);
    }

    public function test_public_job_and_application_preserve_private_resume(): void
    {
        $entry = $this->job();
        $this->get('/careers')->assertOk()->assertSee('Test Engineer');
        $this->get('/careers/test-engineer')->assertOk()->assertSee('Approved duties')->assertDontSee('Private salary')->assertDontSee('Internal approval');
        $this->get('/admin/jobs/'.$entry->id.'/edit')->assertOk();
        $this->get('/admin/jobs/'.$entry->id.'/preview')->assertOk()->assertDontSee('Submit application');
        $this->post('/logout');
        $this->post('/careers/test-engineer/apply', $this->application())->assertRedirect('/careers')->assertSessionHas('application_received');
        $candidate = Candidate::firstOrFail();
        $this->assertSame($entry->id, $candidate->content_entry_id);
        Storage::disk('local')->assertExists($candidate->resume_path);
        $this->get('/storage/'.$candidate->resume_path)->assertNotFound();
        $this->get('/admin/candidates/'.$candidate->id.'/resume')->assertRedirect('/login');
        $this->login('hr-manager');
        $this->get('/admin/candidates/'.$candidate->id.'/resume')->assertOk()->assertDownload('resume-'.$candidate->id.'.pdf');
        $this->get('/admin/candidates/'.$candidate->id)->assertOk()->assertSee('Test Applicant');
    }

    public function test_closed_and_expired_jobs_reject_applications(): void
    {
        $entry = $this->job();
        $this->post('/admin/jobs/'.$entry->id.'/status', ['version' => 1, 'action' => 'unpublish'])->assertSessionHasNoErrors();
        $this->post('/careers/test-engineer/apply', $this->application())->assertStatus(410);
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/jobs/'.$entry->id.'/status', ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->travel(6)->days();
        $this->get('/careers/test-engineer')->assertNotFound();
        $this->post('/careers/test-engineer/apply', $this->application())->assertStatus(410);
        $this->assertDatabaseCount('candidates', 0);
    }

    public function test_general_application_validates_consent_and_resume_contents(): void
    {
        $this->post('/careers/apply', $this->application(['consent' => 0]))->assertSessionHasErrors('consent');
        $this->post('/careers/apply', $this->application(['resume' => UploadedFile::fake()->createWithContent('resume.pdf', 'not a PDF')]))->assertSessionHasErrors('resume');
        $this->post('/careers/apply', $this->application())->assertRedirect('/careers');
        $this->assertSame('General application', Candidate::firstOrFail()->job_title);
    }

    public function test_assigned_recruiter_cannot_see_or_download_other_candidates(): void
    {
        $recruiter = $this->login('hr-executive');
        $assigned = Candidate::factory()->create(['assigned_to' => $recruiter->id]);
        $other = Candidate::factory()->create(['name' => 'Other private candidate']);
        $this->get('/admin/candidates')->assertOk()->assertSee($assigned->name)->assertDontSee($other->name);
        $this->get('/admin/candidates/'.$other->id)->assertForbidden();
        $this->get('/admin/candidates/'.$other->id.'/resume')->assertForbidden();
        $this->put('/admin/candidates/'.$other->id, ['version' => 1, 'status' => 'Rejected'])->assertForbidden();
        $this->put('/admin/candidates/'.$assigned->id, ['version' => 1, 'status' => 'Shortlisted', 'assigned_to' => $recruiter->id])->assertSessionHasErrors('assigned_to');
        $this->put('/admin/candidates/'.$assigned->id, ['version' => 1, 'status' => 'Shortlisted'])->assertSessionHasNoErrors();
        $this->assertSame('Shortlisted', $assigned->fresh()->status);
        $this->get('/admin/candidates/export')->assertForbidden();
    }

    public function test_interviews_assignments_and_conflict_checks(): void
    {
        $manager = $this->login('hr-manager');
        $candidate = Candidate::factory()->create();
        $recruiter = $this->login('hr-executive');
        $this->actingAs($manager)->put('/admin/candidates/'.$candidate->id, ['version' => 1, 'status' => 'Interview', 'assigned_to' => $recruiter->id, 'interview_at' => now()->addDay()->format('Y-m-d H:i:s'), 'interview_notes' => 'Private interview notes'])->assertSessionHasNoErrors();
        $this->assertSame($recruiter->id, $candidate->fresh()->assigned_to);
        $this->assertNotNull($candidate->fresh()->interview_at);
        $this->put('/admin/candidates/'.$candidate->id, ['version' => 1, 'status' => 'Rejected'])->assertSessionHasErrors('version');
        $this->actingAs($recruiter)->get('/admin/candidates/'.$candidate->id)->assertOk();
        $this->actingAs($manager)->put('/admin/candidates/'.$candidate->id, ['version' => 2, 'status' => 'Interview', 'assigned_to' => null])->assertSessionHasNoErrors();
        $this->actingAs($recruiter)->get('/admin/candidates/'.$candidate->id)->assertForbidden();
    }

    public function test_viewer_is_denied_and_exports_escape_spreadsheet_formulas(): void
    {
        Candidate::factory()->create(['name' => '=1+1']);
        $this->login('viewer');
        $this->get('/admin/jobs')->assertForbidden();
        $this->get('/admin/candidates')->assertForbidden();
        $this->get('/admin/candidates/export')->assertForbidden();
        $this->login('hr-manager');
        $response = $this->get('/admin/candidates/export')->assertOk()->assertDownload('candidates.csv');
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
    }
}
