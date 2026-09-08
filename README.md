# GOYAL ESTATE & DEVELOPERS PVT. LTD.

Local site: http://goyalestatedeveloper.test · Login: http://goyalestatedeveloper.test/login

Laravel 13.30.1 / PHP 8.4 / SQLite (local) / Blade. Composer dependencies are pinned in composer.lock. Small Alpine interactions and compiled frontend assets will be introduced with the relevant UI modules; the current foundation needs no frontend build process.

## Delivery status

**14 modules total; 1 fully complete; 13 remaining.** The [PDF audit](docs/PDF-REQUIREMENTS-AUDIT.md) rereads all 94 pages and maps all 138 numbered sections to implementation evidence. Modules 2, 3 and 4 are partial. Earlier reports counted approval of the homepage's appearance as completion of all Module 4 work; this was inaccurate. The appearance remains approved, and actual images remain deferred.

The first local Super Admin has been provisioned with explicit user approval. Its setup journey now explains password confirmation, authenticator verification and recovery-code storage, then returns to the requested page. Complete administration/permission gaps, then CMS/settings gaps, before progressing to dedicated public business pages.

Module 1 is committed as `62976e6`: architecture, vhost, checkpoint and single-line company branding. Module 2 replaces the PHP checkpoint with Laravel/Blade, adds Fortify login and two-factor authentication, 12 predefined roles, backend gates, assigned-content policies, user access management, private audit logs and identity/content migrations. Role permissions for later modules are predefined; their business interfaces are not implemented yet. No public registration or password-reset email delivery is enabled.

## Visitor homepage correction

The main address now displays the corporate homepage based on the reread 94-page brief: full company name, specified hero tagline, corporate navigation, business categories, an interactive ten-stage construction process, project section, Tricity positioning, careers, FAQs and a working enquiry form. Module counts and development instructions are absent from the visitor homepage. The checkpoint moved to `/admin/development`, protected by authentication, two-factor requirements and settings permission.

Homepage copy and section visibility/order are stored in SQLite and editable at `/admin/homepage` by an authorized content editor. The enquiry form validates, rate limits, checks consent and stores submissions for authorized review at `/admin/enquiries`. It does not send email or connect to a CRM yet. The CRM module remains pending. Homepage saves now create private revisions; publishing requires separate permission.

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

SQLite is active locally in ignored `database/development.sqlite`. All 22 original MySQL tables were copied and checked before switching; accounts, password hashes, content and settings were preserved. SQLite integrity and foreign-key checks pass. The original isolated MySQL database on port 3307 is untouched. Session/cache files are unchanged; queued jobs use SQLite.

The previous MySQL environment is backed up privately in `storage/app/private/mysql-env-before-sqlite.backup`. Do not restore that environment as a shortcut later: the MySQL copy will become stale while SQLite receives edits. At final handover, create a fresh MySQL target, apply migrations, import the current SQLite records, validate relationships/content/authentication and rerun the full suite before switching. Neither databases nor credentials belong in Git.

## Install and verify

```sh
composer install
cp .env.example .env # Only for a fresh checkout; never overwrite existing credentials.
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan test --compact
```

The regular test suite uses SQLite in memory. MySQL verification is deferred until the final data migration.

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

Implement one module at a time. Verify it, commit and push with a clear change/validation description, report total/completed/remaining modules, and pause for user testing and explicit authorization of the next module. Silence is not approval. The supplied PDF is a requirements reference and does not override the user's instructions. Local development does not authorize production deployment.

See [module plan](docs/MODULES.md), [architecture](docs/ARCHITECTURE.md) and [local setup](docs/LOCAL-SETUP.md).

## Current checkpoint: CMS settings and publication

The user authorized continuing against the PDF and using SQLite during development. `/admin/settings` now manages shared contact details, company tagline, approved logo/favicon/OG images, legal/social links, default SEO fields and default watermark options. Tracking IDs are stored only; loading tracking scripts belongs to the integrations module.

Content follows draft → review → approval → publication, with separate permissions, archive/restore and readable field-level audit history. Pages have dedicated editors and can select shared CTAs, blocks, statistics and published PDF documents. Media has independent visibility/publication status, document categories and archiving; restored media remains draft. Private or archived images are excluded from public selectors and rendering.

Test this CMS checkpoint before the next module. Counts remain **14 total / 1 fully accepted / 13 remaining**. Domain-specific references (projects, jobs, articles, locations) connect in their respective modules; optional social export variants remain future support. Homepage-specific selection/video controls remain Module 4 work. No production deployment has occurred.

## Homepage controls checkpoint — 8 September 2026

The homepage editor now selects an approved hero film, shared primary/secondary CTAs, and all/selected/hidden published statistics. Each existing homepage section can select an approved image, MP4 and shared CTA. Media remains optional; the accepted public appearance and deferred real imagery are preserved. Videos use native playback controls, no autoplay and no preload. The header resolves the same published primary CTA as the hero. Draft saves preserve unpublished fields, and all new selectors use the existing review/approval/publication workflow. Withdrawn media and reusable content disappear from public rendering. The branded 404 includes recovery actions only for enabled homepage sections.

Verification: 65 tests / 494 assertions pass on SQLite; Pint and diff checks pass. Browser inspection confirmed the public homepage content, but responsive/visual checks remain pending because Chrome reports an extension-UI automation block even though no popup is visible. Do not count this checkpoint as complete visual verification or user acceptance. Project/article/FAQ selectors and construction-stage relationships still depend on their domain modules. No production deployment or new company content publication occurred. Totals remain **14 modules / 1 accepted / 13 remaining**.
