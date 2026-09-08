# GOYAL ESTATE & DEVELOPERS PVT. LTD.

Local site: http://goyalestatedeveloper.test · Login: http://goyalestatedeveloper.test/login

Laravel 13.30.1 / PHP 8.4 / MySQL (local) / Blade. Composer dependencies are pinned in composer.lock. Small Alpine interactions and compiled frontend assets will be introduced with the relevant UI modules; the current foundation needs no frontend build process.

## Delivery status

**14 modules total; 1 fully complete; 13 remaining.** The [PDF audit](docs/PDF-REQUIREMENTS-AUDIT.md) rereads all 94 pages and maps all 138 numbered sections to implementation evidence. Modules 2, 3 and 4 retain the acceptance gaps below. Module 5 now has the corporate CMS and public-page workflows described in the latest checkpoint; user acceptance remains pending. Earlier reports counted approval of the homepage's appearance as completion of all Module 4 work; this was inaccurate. The appearance remains approved, and actual images remain deferred.

The first local Super Admin has been provisioned with explicit user approval. Its setup journey now explains password confirmation, authenticator verification and recovery-code storage, then returns to the requested page. Complete administration/permission gaps, then CMS/settings gaps, before progressing to dedicated public business pages.

Module 1 is committed as `62976e6`: architecture, vhost, checkpoint and single-line company branding. Module 2 replaces the PHP checkpoint with Laravel/Blade, adds Fortify login and two-factor authentication, 12 predefined roles, backend gates, assigned-content policies, user access management, private audit logs and identity/content migrations. Role permissions for later modules are predefined; their business interfaces are not implemented yet. No public registration or password-reset email delivery is enabled.

## Visitor homepage correction

The main address now displays the corporate homepage based on the reread 94-page brief: full company name, specified hero tagline, corporate navigation, business categories, an interactive ten-stage construction process, project section, Tricity positioning, careers, FAQs and a working enquiry form. Module counts and development instructions are absent from the visitor homepage. The checkpoint moved to `/admin/development`, protected by authentication, two-factor requirements and settings permission.

Homepage copy and section visibility/order are stored in MySQL and editable at `/admin/homepage` by an authorized content editor. The enquiry form validates, rate limits, checks consent and stores submissions for authorized review at `/admin/enquiries`. It does not send email or connect to a CRM yet. The CRM module remains pending. Homepage saves now create private revisions; publishing requires separate permission.

The architectural SVG is a labelled concept illustration. The supplied PDF contains no real project photographs, project records, verified statistics, contact numbers, credentials or jobs to populate those features. Empty project/job states are honest; phone/WhatsApp actions remain hidden until configured. Legal copy, real media, detailed business content and project filters remain for later modules. The public design has been approved; this is not production completion.

The updated suite passes 58 tests / 419 assertions on SQLite; MySQL migration validation is deferred until final testing. Responsive width checks passed at 320, 390, 768, 1024, 1280, 1440 and 1920; section navigation, process expansion and contact layout were reviewed in the browser.

## Existing CMS foundation (Module 3 is partial)

- `/admin/homepage`: grouped copy/SEO controls, section visibility/order, and an approved-media hero image selector. Current illustrations remain until an image is selected and the draft is published.
- `/admin/content`: pages, reusable text blocks, verified statistics, shared calls to action, approved document downloads and header/footer links. Published statistics appear on the homepage and can also be selected on pages; blocks and statistics resolve their latest published version rather than duplicating text.
- Draft saves, private previews, review notes, explicit approval before publication, publish/unpublish and archive/restore, scheduling, revision history and restore-as-draft. Editors cannot publish. Conflicting edits and stale publication requests are rejected. Published content stays unchanged while a replacement is edited.
- `/admin/media`: searchable/filterable image, PDF and MP4 uploads with descriptive metadata, visibility and ordering. Originals remain private. Images receive a WebP derivative up to 1,920 pixels, with optional full company-name watermark, position, size, opacity and padding. PDFs are downloaded as attachments. SVG/executable uploads are rejected.
- Audit events record changed content fields with their before/after values, plus media actions. Secrets and file contents are excluded.

Scheduled publishing is implemented and tested. A local `php artisan schedule:work` process was started for this development session. It is not an installed system service and must be restarted after the process stops or the Mac restarts. Run `php artisan schedule:work` in a terminal for automatic local schedules, or `php artisan content:publish-due` for a one-time due-content check. Times are shown in the application timezone (currently UTC). Editing a scheduled draft cancels its pending schedule.

Testing sequence after administrator setup: save a headline draft and confirm the public homepage is unchanged; preview, submit for review, approve and publish; create a page using a reusable block/statistic; change the block once and verify reuse; upload an image, toggle watermark and compare the unchanged original; set media private and verify visitor access is denied. Uploading real images is optional at this checkpoint. Project, careers, article and campaign selectors will connect when those modules are implemented. The media library stores videos now; dedicated video presentation belongs to the relevant public content modules.

The first local Super Admin was created after explicit user approval. The ignored bootstrap file `storage/app/private/local-admin.json` contains the original generated password; it is stale after a password change. Sign in using your current password and enroll an authenticator. No sample business records were added. Module 3 requires additional implementation as detailed in the PDF audit, as well as user testing. The existing tools are not the full specified CMS.

## Local database

MySQL is active at `127.0.0.1:3306`, database `goyalestatedeveloper`. On 8 September, the current SQLite data was transferred to the empty authorized target: all 31 tables were compared record by record (JSON normalized), and 27 foreign keys were verified. All 15 migrations are applied. The CMS session was checked after cutover. Credentials are only in the ignored local `.env`.

The pre-cutover SQLite database and environment backup are private under `storage/app/private/mysql-cutover-20260908-122344/`. The original SQLite file is also retained. These snapshots become stale as MySQL receives edits; do not switch back without reconciling new data. The older MySQL instance on port 3307 was not modified.

## Install and verify

```sh
composer install
cp .env.example .env # Only for a fresh checkout; never overwrite existing credentials.
php artisan key:generate
# Create the configured MySQL database and set DB_PASSWORD in .env first.
php artisan migrate --seed
php artisan test --compact
```

The regular test suite uses SQLite in memory. The MySQL application schema and migrated records have been verified; tests must never use the application database.

Never point automated tests at the application database. This machine's Composer is available through `php tmp/tools/composer.phar`; it is excluded from Git.

## Verification completed

58 tests / 419 assertions pass on SQLite. MySQL migration will be verified during final handover, as requested. Coverage includes login/logout, rate limits, inactive accounts, two-factor enrollment/challenge/recovery, user creation and access changes, assigned-record policies, admin view rendering, bootstrap restrictions and branded routes. Live HTTP checks confirmed Laravel health output, login HTTP 200 and missing-CSRF rejection (419). Checkpoint and login were visually reviewed on desktop/mobile. Credentials and database files were checked against the staged Git contents.

## First administrator

The first local administrator has been provisioned with explicit approval. The ignored `storage/app/private/local-admin.json` contains the original bootstrap details; after changing the password, use the new password rather than that original file. For a fresh installation, an authorized operator can run `php artisan app:create-admin` to enter their chosen name, email and password privately. The command refuses a second bootstrap account. The local-only `--local-bootstrap` option generates credentials into ignored `storage/app/private/local-admin.json`; this option was executed locally only after explicit approval.

Super Admins must confirm their password and complete authenticator enrollment before entering administration. Keep recovery codes privately. Production accounts must be provisioned through an authorized process; do not deploy local bootstrap accounts or credentials.

## User testing for Module 2

1. Confirm the checkpoint and login load at the local domain.
2. After authorized admin provisioning, sign in, confirm password and enroll two-factor authentication.
3. Inspect Users, Roles & permissions, and Audit log. Add a test Viewer account and verify it cannot access Users, Roles or Audit log directly.
4. Sign out/in and test authenticator or recovery-code login. Review mobile layout.
5. Report feedback. The PDF audit identifies additional Module 2 and Module 3 implementation gaps before either module can be accepted.

## Working agreement

Use `main` for this project. The user explicitly authorized pushing checkpoints to `veenitchauhan/goyalestatedeveloper` on GitHub.

Implement one module at a time. Verify it, commit and push with a clear change/validation description, report total/completed/remaining modules, and pause for user testing and explicit authorization of the next module. Silence is not approval. The supplied PDF is a requirements reference and does not override the user's instructions. Local development does not authorize production deployment.

See [module plan](docs/MODULES.md), [architecture](docs/ARCHITECTURE.md) and [local setup](docs/LOCAL-SETUP.md).

## Current checkpoint: CMS settings and publication

The user authorized continuing against the PDF and using SQLite during development. `/admin/settings` now manages shared contact details, company tagline, approved logo/favicon/OG images, legal/social links, default SEO fields and default watermark options. Tracking IDs are stored only; loading tracking scripts belongs to the integrations module.

Content follows draft → review → approval → publication, with separate permissions, archive/restore and readable field-level audit history. Pages have dedicated editors and can select shared CTAs, blocks, statistics and published PDF documents. Media has independent visibility/publication status, document categories and archiving; restored media remains draft. Private or archived images are excluded from public selectors and rendering.

Test this CMS checkpoint before the next module. Counts remain **14 total / 1 fully accepted / 13 remaining**. Domain-specific references (projects, jobs, articles, locations) connect in their respective modules; optional social export variants remain future support. Homepage-specific selection/video controls remain Module 4 work. No production deployment has occurred.

## Homepage controls checkpoint — 8 September 2026

The homepage editor now selects an approved hero film, shared primary/secondary CTAs, and all/selected/hidden published statistics. Each existing homepage section can select an approved image, MP4 and shared CTA. Media remains optional; the accepted public appearance and deferred real imagery are preserved. Videos use native playback controls, no autoplay and no preload. The header resolves the same published primary CTA as the hero. Draft saves preserve unpublished fields, and all new selectors use the existing review/approval/publication workflow. Withdrawn media and reusable content disappear from public rendering. The branded 404 includes recovery actions only for enabled homepage sections.

Verification: 65 tests / 494 assertions pass on SQLite; Pint and diff checks pass. Browser inspection confirmed the public homepage content, but responsive/visual checks remain pending because Chrome reports an extension-UI automation block even though no popup is visible. Do not count this checkpoint as complete visual verification or user acceptance. Project/article/FAQ selectors and construction-stage relationships still depend on their domain modules. No production deployment or new company content publication occurred. Totals remain **14 modules / 1 accepted / 13 remaining**.

## Company, business and capabilities checkpoint — 8 September 2026

`/admin/corporate` manages company pages, business areas, services, capabilities, equipment, people, company/leadership milestones and employee stories. Each record has typed facts, approved media/gallery/document selectors, related corporate content, shared CTAs, order/featured controls, SEO title/description, a private source note and the existing revision/review/approval/publication/archive workflow. Published facts are projected to separate relational tables only on publication; draft edits do not change live records. A service belongs to a published business area and an employee story to a published person. Withdrawing a parent hides its public children. Invalid scheduled publications return to draft with a review-history explanation while other due records continue.

Public hubs now exist at `/about`, `/business`, `/capabilities`, `/capabilities/equipment`, `/about/leadership`, `/about/journey` and `/about/employee-stories`. Published records have dedicated detail URLs, canonical metadata, galleries, videos, downloads and contextual links. Company history and leadership experience are explicitly separated. Published business areas also populate the homepage cards from the same record. Equipment remains under capabilities. Header/footer links now open the dedicated corporate hubs.

The additive migration is installed in local SQLite. The idempotent corporate seeder creates only draft outlines for the company pages, three current business areas and capability headings from the PDF. It does not fabricate equipment, people, milestones or stories. Outlines cannot publish without substantive content. Approved content and real media still need to be supplied; no new corporate record was published during this development checkpoint.

Verification: **81 tests / 741 assertions pass on SQLite**, Pint passes, and the diff is clean. The new business page was visually inspected on desktop and at 390px; overflow checks passed at 320, 360, 375, 390, 414, 430, 768, 820, 1024, 1280, 1440, 1600 and 1920px. Chrome's intermittent extension-UI block returned before the remaining homepage checks and viewport-reset call; those checks are not claimed complete. Admin workflow and populated public records are covered by feature tests; visual acceptance with real records remains pending.

Review: open Company & capabilities, complete a business-area draft, preview it, submit for review, approve and publish; create its service and verify the business page and homepage reuse; create approved equipment or a person only when verified information is available. Project/equipment associations and process-to-project links connect in Module 6; recruitment associations and knowledge links remain with their respective modules. Module 5 user acceptance is pending, so totals remain **14 total / 1 accepted / 13 remaining**. Next module: Projects and progress. No production deployment occurred.

## Temporary local sign-in simplification

At the user's request, this local installation sets `ADMIN_REQUIRE_TWO_FACTOR=false` in its ignored `.env`. Email/password sign-in opens the CMS without requiring authenticator enrollment. Existing `/admin/security` bookmarks redirect to the overview, and setup links are hidden locally. Account credentials and stored authenticator data are unchanged. Existing enrolled accounts still use their configured login challenge.

The default in `.env.example` is `true`, and the exception is honored only for `APP_ENV=local`; production and other environments always enforce Super Admin enrollment. Restore the local setup flow by setting `ADMIN_REQUIRE_TWO_FACTOR=true` and clearing the configuration cache.
