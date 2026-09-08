# GOYAL ESTATE & DEVELOPERS PVT. LTD.

Local site: http://goyalestatedeveloper.test · Login: http://goyalestatedeveloper.test/login

Laravel 13.30.1 / PHP 8.4 / MySQL 8.4 / Blade. Composer dependencies are pinned in composer.lock. Small Alpine interactions and compiled frontend assets will be introduced with the relevant UI modules; the current foundation needs no frontend build process.

## Delivery status

**14 modules total; 1 fully complete; 13 remaining.** The [PDF audit](docs/PDF-REQUIREMENTS-AUDIT.md) rereads all 94 pages and maps all 138 numbered sections to implementation evidence. Modules 2, 3 and 4 are partial. Earlier reports counted approval of the homepage's appearance as completion of all Module 4 work; this was inaccurate. The appearance remains approved, and actual images remain deferred.

The first local Super Admin has been provisioned with explicit user approval. Its setup journey still needs clearer guidance. Complete administration/permission gaps, then CMS/settings gaps, before progressing to dedicated public business pages.

Module 1 is committed as `62976e6`: architecture, vhost, checkpoint and single-line company branding. Module 2 replaces the PHP checkpoint with Laravel/Blade, adds Fortify login and two-factor authentication, 12 predefined roles, backend gates, assigned-content policies, user access management, private audit logs and identity/content migrations. Role permissions for later modules are predefined; their business interfaces are not implemented yet. No public registration or password-reset email delivery is enabled.

## Visitor homepage correction

The main address now displays the corporate homepage based on the reread 94-page brief: full company name, specified hero tagline, corporate navigation, business categories, an interactive ten-stage construction process, project section, Tricity positioning, careers, FAQs and a working enquiry form. Module counts and development instructions are absent from the visitor homepage. The checkpoint moved to `/admin/development`, protected by authentication, two-factor requirements and settings permission.

Homepage copy and section visibility/order are stored in MySQL and editable at `/admin/homepage` by an authorized content editor. The enquiry form validates, rate limits, checks consent and stores submissions for authorized review at `/admin/enquiries`. It does not send email or connect to a CRM yet. The CRM module remains pending. Homepage saves now create private revisions; publishing requires separate permission.

The architectural SVG is a labelled concept illustration. The supplied PDF contains no real project photographs, project records, verified statistics, contact numbers, credentials or jobs to populate those features. Empty project/job states are honest; phone/WhatsApp actions remain hidden until configured. Legal copy, real media, detailed business content and project filters remain for later modules. The public design has been approved; this is not production completion.

The updated suite passes 45 tests / 268 assertions on both SQLite and isolated MySQL. Responsive width checks passed at 320, 390, 768, 1024, 1280, 1440 and 1920; section navigation, process expansion and contact layout were reviewed in the browser.

## Existing CMS foundation (Module 3 is partial)

- `/admin/homepage`: grouped copy/contact/SEO controls, section visibility/order, and an approved-media hero image selector. Current illustrations remain until an image is selected and the draft is published.
- `/admin/content`: pages, reusable text blocks, verified statistics and header/footer links. Published statistics appear on the homepage and can also be selected on pages; blocks and statistics resolve their latest published version rather than duplicating text.
- Draft saves, private previews, review notes, publish/unpublish, scheduling, revision history and restore-as-draft. Editors cannot publish. Conflicting edits and stale publication requests are rejected. Published content stays unchanged while a replacement is edited.
- `/admin/media`: searchable/filterable image, PDF and MP4 uploads with descriptive metadata, visibility and ordering. Originals remain private. Images receive a WebP derivative up to 1,920 pixels, with optional full company-name watermark, position, size, opacity and padding. PDFs are downloaded as attachments. SVG/executable uploads are rejected.
- Audit events record content/media actions without copying complete text or file contents into the log.

Scheduled publishing is implemented and tested, but no persistent scheduler service has been installed on this Mac. Run `php artisan schedule:work` in a terminal for automatic local schedules, or `php artisan content:publish-due` for a one-time due-content check. Times are shown in the application timezone (currently UTC). Editing a scheduled draft cancels its pending schedule.

Testing sequence after administrator setup: save a headline draft and confirm the public homepage is unchanged; preview and publish; create a page using a reusable block/statistic; change the block once and verify reuse; upload an image, toggle watermark and compare the unchanged original; set media private and verify visitor access is denied. Uploading real images is optional at this checkpoint. Project, careers, article and campaign selectors will connect when those modules are implemented. The media library stores videos now; dedicated video presentation belongs to the relevant public content modules.

The first local Super Admin was created after explicit user approval. Credentials are in ignored `storage/app/private/local-admin.json`; sign in and enroll an authenticator. No sample business records were added. Module 3 requires additional implementation as detailed in the PDF audit, as well as user testing. The existing tools are not the full specified CMS.

## Local database

This project uses its own loopback-only MySQL instance on port 3307. The existing MySQL server is unchanged. Database files are in ignored `storage/local-mysql`; application credentials are in ignored `.env`. The application user is scoped to `goyalestatedeveloper` and its separate test database. Session and cache drivers currently use local files; jobs use the database.

```sh
sh infra/mysql-local.sh status
sh infra/mysql-local.sh start
sh infra/mysql-local.sh stop
```

Start it after a machine reboot if needed. This is a local development service, not a production database deployment. A new checkout can use an existing MySQL installation with its own database/user by setting DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in `.env`; the start script only manages the instance already initialized on this machine.

## Install and verify

```sh
composer install
cp .env.example .env # Only for a fresh checkout; never overwrite existing credentials.
php artisan key:generate
php artisan migrate --seed
php artisan test --compact
```

The regular test suite uses SQLite in memory. MySQL validation uses a separately provisioned, disposable database:

```sh
DB_CONNECTION=mysql DB_DATABASE=goyalestatedeveloper_testing php artisan test --compact
```

Never point automated tests at the application database. This machine's Composer is available through `php tmp/tools/composer.phar`; it is excluded from Git.

## Verification completed

45 tests / 268 assertions pass on SQLite and the isolated MySQL test database. Coverage includes login/logout, rate limits, inactive accounts, two-factor enrollment/challenge/recovery, user creation and access changes, assigned-record policies, admin view rendering, bootstrap restrictions and branded routes. Live HTTP checks confirmed Laravel health output, login HTTP 200 and missing-CSRF rejection (419). Checkpoint and login were visually reviewed on desktop/mobile. Credentials and database files were checked against the staged Git contents.

## First administrator

The first local administrator has been provisioned with explicit approval. Its login details are stored in ignored `storage/app/private/local-admin.json` (never committed). For a fresh installation, an authorized operator can run `php artisan app:create-admin` to enter their chosen name, email and password privately. The command refuses a second bootstrap account. The local-only `--local-bootstrap` option generates credentials into ignored `storage/app/private/local-admin.json`; this option was executed locally only after explicit approval.

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
