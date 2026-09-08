# Delivery plan: 14 modules

Current sequence: **13/14 checkpoints reached; 1/14 remains open for final acceptance and launch readiness.** Module 14's local build, automated tests, public viewport checks and isolated backup restoration have passed. Remaining implementation/integration and approved-content requirements are listed below. Checkpoint sequence is not full PDF acceptance; the accepted-module count remains unchanged.

Status corrected after the 8 September PDF audit: **14 total, 1 fully complete, 13 remaining.** Module 1 is complete. Modules 2, 3 and 4 are partial. The user accepted the homepage's visual direction, not the entire Module 4 specification; the earlier count of two completed modules was inaccurate. The first administrator exists, but authentication setup remains a usability hurdle. The full traceability review is in [PDF requirements audit](PDF-REQUIREMENTS-AUDIT.md), covering all 138 sections across 94 pages.

Keep the accepted appearance and defer real imagery as requested. Finish the Module 2 access/permission/audit gaps, then Module 3 CMS/settings/media, then close Module 4's outstanding controls and verification. Dedicated public pages belong to Module 5; homepage anchors do not fulfill them. The 14-module total is unchanged.

Each row is a separate development and testing checkpoint. Completion requires implementation, verification and resolution of the user testing checkpoint. Stop after every module and wait for the user to test and authorize the next one. Do not change the total silently if scope changes.

| # | Module | Scope / PDF sections | Acceptance test |
|---|---|---|---|
| 1 | Architecture and local foundation | Requirements, domain boundaries, schema design, permission matrix, vhost, local checkpoint; 1–7, 77, 111–112, 130–138 | Domain opens, full company name, responsive checkpoint, health response, private files inaccessible |
| 2 | Application, authentication and permissions | Framework, database migrations, admin shell, login, roles, policies, audit trail; 63–67, 76, 98–99 | Sign in/out, denied unauthorized actions, scoped records, audited changes |
| 3 | CMS, settings and media | Pages, menus, approvals, previews, scheduling, reusable blocks, statistics, contact settings, media/documents, watermark derivatives; 9, 17–18, 21, 53, 68–71, 78–81, 95, 100, 106–107, 122–123 | Edit once and reuse; draft hidden; upload/reorder/brand media without altering original |
| 4 | Design system and homepage | Responsive corporate shell, hero, editable home sections, interaction and accessible navigation; 5–8, 72–75, 107, 114–116, 118, 123, 135 | Review desktop/mobile visual direction, keyboard navigation and reduced motion |
| 5 | Company, business and capabilities | About, leadership/journey, service details, process, equipment, quality, safety, sustainability; 10–11, 19–26, 30, 41, 95, 130 | Create and publish approved service/equipment/team content from CMS |
| 6 | Projects and progress | Filters, details, stages, milestones, progress, galleries/video, comparisons and related equipment; 12–17, 103, 117 | Create a project, change progress, filter and inspect its public detail/gallery |
| 7 | Locations and expansion | Country/state/region/city hierarchy, verified presence, maps, project/service relationships; 4, 27–28, 48–49, 97, 127 | Add a city and project without code; future areas stay out of active presence |
| 8 | Careers and recruitment | Career pages, jobs, applications, private resumes, HR pipeline/interviews, internships, employee stories; 35–41, 128, 133 | Apply, validate resume, shortlist and interview with HR-only candidate access |
| 9 | Insights, knowledge and FAQs | Editorial content, answers, topic clusters, reviewers, related content and search; 42–46, 50–51, 82–83, 101–105, 125–126, 134 | Draft/review/publish article and FAQ; search only authorized/published content |
| 10 | Enquiries, CTAs and CRM | Configurable forms, contact, vendor/JV enquiries, lead routing, notes, follow-ups, sources and email queue; 34, 54, 56, 59–61, 90–94, 121, 132 | Submit enquiry, validate/rate limit, assign lead, follow up and verify routing |
| 11 | Campaigns, analytics and integrations | Landing builder, attribution, consent-aware analytics, call provider adapter, WhatsApp qualification adapter and approved-knowledge AI boundary; 55, 57–58, 62, 88–89, 109, 119, 121 | Campaign attribution persists; signed/idempotent webhook; consent respected; provider sandbox verified when available |
| 12 | SEO and discovery | SEO admin, metadata, canonicals, redirects, structured data, sitemaps, robots and quality checks; 45–53, 84–87, 106, 124, 128, 134 | Published pages in sitemap, valid content-matching schema, redirect and indexing controls |
| 13 | Future developments | Inactive-by-default CMS module: developments, towers/floors/units, inventory, plans, amenities, site visits, AI property adapter; 29–33, 96, 120, 129 | Enable in local test, manage inventory and site visits; disable and hide public routes/navigation |
| 14 | Final validation and handover | Approved content/legal pages, security, responsiveness, performance, backup/restore, monitoring and production-readiness report; 100–104, 108–113, 136–138 | End-to-end role tests, all target widths, restore rehearsal, content audit and handover; deployment needs separate instruction |

## Inputs needed as relevant modules approach

Approved logo and real project/media assets; company facts and leadership; verified services/equipment/locations; contact details; jobs; privacy/legal copy; provider accounts and sandbox credentials. Never replace missing inputs with invented claims, projects, numbers or contact information. Missing provider credentials mean integration readiness can be tested locally but live integration remains explicitly pending.

## Checkpoint log

| Module | Implementation | User acceptance | Next module authorized |
|---|---|---|---|
| 1 | Complete; see MODULE-1-VERIFICATION.md | Accepted by user | Yes, Module 2 |
| 2 | Remediation implemented: guided setup, destination return, usable overview, protected role editing, granular current-action gates and before/after audit details | Awaiting user testing; not counted complete | Pause before Module 3 |
| 4 | Partial: approved visual direction; full section/media controls, footer/404 and verification incomplete | Visual appearance accepted only; real imagery deferred | CMS work was authorized; full module remains open |
| 3 | Partial: baseline revisions/pages/media work; global settings, approval/archive, document controls and broader reuse incomplete | No module acceptance | Finish scoped gaps after Module 2 remediation |

## Revised module acceptance gates

Use [the section-by-section audit](PDF-REQUIREMENTS-AUDIT.md) alongside each module row, not merely the abbreviated scope column. No work is complete because its permission names or database foundations exist.

- **Module 2:** A new Super Admin can understand and complete setup, return to the intended page, and use available screens. Test role/permission edits, separate action gates and readable old/new audit details without exposing secrets. Do not remove PDF-required security to avoid explaining it.
- **Module 3:** A nondeveloper can find central settings, manage menus/statistics/documents/media, edit reusable content and move it through review/approval/publication/archive. Verify published content survives draft edits, relation/visibility checks hold, and schedules have a working operational setup.
- **Module 4:** Keep the approved appearance, complete the specified homepage/footer/404 controls and test the listed responsive/accessibility cases. Connect references as domain modules land; record these dependencies rather than claiming their selectors already work. Obtain an explicit decision on optional storytelling elements.
- **Modules 5–14:** Demonstrate the specified public page and corresponding administration workflow with the appropriate role. Separate missing client facts/assets from missing software. Keep future developments inactive until authorized, while proving their architecture works.

Commit and push each verified change with its scope and validation. Pause for testing at every completed module; no implicit acceptance from a Git push or a passing automated suite.

## Module 2 remediation checkpoint — 8 September 2026

Implemented guided authenticator enrollment with recent-login confirmation, recovery-code acknowledgement and return to the requested CMS page. Unfinished setup shows setup navigation instead of repeatedly exposing gated CMS links. Security secrets remain hidden when password confirmation expires. A working overview links to actual available tools.

Super Admin can create custom roles, edit non-Super-Admin role labels/permissions and delete only unassigned custom roles. Super Admin privileges and identity administration are protected; stale role edits are rejected. Seed reruns preserve edited permissions. Separate create/edit and media upload/edit/publish plus page-unpublish checks apply to the current routes. Page approval/archive permissions are catalogued; enforcement of the complete editorial state machine belongs to the next CMS checkpoint, not claimed here.

Audit entries now show actor names, affected record types and before/after access, role-permission and selected media/status values; content revisions identify the relevant draft versions. Full field-by-field content/SEO comparison remains a Module 3 requirement. Passwords, authenticator secrets and recovery codes are outside the audit allowlist.

User acceptance: complete setup and reach the requested page; open Roles, create a custom read-only role, assign it to a test account and verify edit denial; change that role and verify immediate enforcement; inspect its before/after audit entry. No new production users, passwords or authenticator secrets were changed by development. Total remains **14 / 1 complete / 13 remaining** until acceptance.

Verification for this checkpoint: **53 tests / 337 assertions pass on SQLite and the isolated MySQL test database.** The live browser review was interrupted; the complete setup journey is covered by feature tests and awaits the user's visual/interactive review.

### CMS checkpoint — 8 September 2026

User authorized continuing from the PDF and SQLite for development. Central settings, strict editorial approval/archive, field-level audit diffs, typed editors, reusable CTAs, PDF references, document categories and media archive/publication controls are implemented. A session-local scheduler worker is running; automatic restart after a reboot is not installed. Existing MySQL data is retained unchanged, with final migration deferred. SQLite checks: 58 tests / 419 assertions. User testing is pending; totals remain 14 / 1 / 13. Optional social export variants and domain references remain linked to future domain work; homepage-specific controls remain Module 4.

### Module 5 implementation checkpoint — 8 September 2026

User authorized pushing to `main` and proceeding. Corporate CMS and dedicated company/business/capability/equipment/people/journey/story pages are implemented, with typed relational facts, publication isolation, shared media/CTAs, parent visibility rules, private approval references and draft-only seed outlines. See the latest README checkpoint for routes and review steps. Project and knowledge associations remain dependent on the corresponding domain modules.

Validation: 81 tests / 741 assertions on SQLite; Pint and diff checks pass. Desktop and 390px visual review plus the PDF's 13 width/overflow checks passed on the new business hub. Chrome automation became blocked again before completing the homepage recheck. User acceptance and populated-page visual review remain pending; no completion count is advanced. Total remains 14 / 1 accepted / 13 remaining.

## Projects and progress checkpoint — 8 September 2026

The user authorized continuing the PDF after the CMS interface changes. Module 6 now has `/admin/projects` and `/projects`, using content revisions and the existing assigned-record policies. Projects support facts, approved-only client/value disclosure, status/progress/dates, featured homepage placement, equipment/related-project/document references, configurable ordered stages with milestones/images, ordered gallery items with metadata/visibility and image/video categories, FAQs, before/after image comparison, category filtering and a construction-stage slider. Public location filters derive only from published project records.

Publishing requires verified facts with an internal approval/source reference and revalidates media/relations. Saving preserves the published version; review, approval, publication, scheduling, unpublication and archive transitions are protected by project permissions. Assignment changes are audited and take effect immediately. No live project data was created; test facts remain isolated to the test database.

Verification: 99 tests / 889 assertions passed; Pint and diff checks passed. Chrome desktop review covered the project editor, adding/removing an unsaved stage and the public empty portfolio. Populated-page visual review with approved assets and the full responsive matrix remain pending.

This is a Module 6 checkpoint, not acceptance of the entire PDF. Remaining project work includes direct gallery uploads within the project editor (currently uses the central media library), 360-degree media support once its approved format is selected, and deeper visualization review with real assets. Location hierarchy, Knowledge Bank relationships, project-specific contact routing and structured data connect in Modules 7, 9, 10 and 12. User acceptance remains pending; no accepted-module count is advanced.

## MySQL cutover and project uploads — 8 September 2026

At the user's request, migrated 31 current SQLite tables into the empty MySQL database on port 3306, compared all records and checked 27 foreign-key relationships. Kept private pre-cutover backups and verified the CMS session after switching. Application credentials remain uncommitted.

Saved projects now offer an inline media uploader, retaining unsaved project edits through an asynchronous upload. The existing media validation, public/private gates, original preservation and watermark generation are reused. Public approved uploads become available immediately in cover, gallery, timeline and document selectors; private uploads remain unavailable until reviewed. Uploading does not silently publish or revise a project. Project access and media access are both required.

Verification: 102 tests / 904 assertions pass using isolated SQLite, Pint passes, MySQL migration status is current and the authenticated project list opens in Chrome. The 360-degree viewer and populated-asset visual review remain outstanding; this does not mark Module 6 fully accepted.

## Project panorama checkpoint — 8 September 2026

Module 6 now supports an approved equirectangular 360-degree project image. The CMS selector validates published/public media and its 2:1 dimensions at draft save and publication. The public viewer is opened on demand, with drag, direction buttons, keyboard navigation, zoom and reset. A flat-image fallback remains available without WebGL/JavaScript; no autoplay, remote embeds or new dependencies are introduced. Panorama media follows the existing private/archive visibility checks and can also be selected after an inline project upload.

Verification: 105 tests / 928 assertions pass on isolated SQLite; Pint and diff checks pass. The renderer, navigation, zoom and return to the flat image were visually checked in Chrome against a temporary latitude/longitude test pattern served only on loopback. That server was stopped; no test panorama or project was written to the application database. Real-asset acceptance and the full mobile/browser matrix remain pending. The next domain checkpoint is Module 7, Locations and expansion, with location/service/project links; no accepted-module count is advanced automatically.

## Locations and expansion checkpoint — 8 September 2026

Module 7 now offers `/admin/locations`, `/locations` and `/locations/{slug}`. Locations are revisioned ContentEntry records with a strict published country → state → region → city hierarchy, current/future presence, useful local content, approved service references, optional verified coordinates and internal evidence. Hierarchy levels are stable after creation; parent changes must respect the preceding level. Public routes require a fully published, verified current ancestor chain; future or unpublished parents suppress descendant presence. No locations were seeded or published.

Projects can select an active city record. Saving derives city/state/country from its hierarchy, while published location pages include only published linked projects, including descendant projects on parent pages. Project and location pages link in both directions. The presence section links to the location directory. A dynamic latitude/longitude overview plots supplied coordinates; detailed maps open OpenStreetMap only when requested. This is a coordinate overview, not a hosted street-map embed.

Verification: 111 tests / 1,042 assertions pass on isolated SQLite; Pint and diff checks pass. The MySQL-backed editor was visually inspected in Chrome. Coverage includes hierarchy enforcement, coordinate boundaries, publication verification, draft isolation, parent unpublication, future-location exclusion, current-project associations and permission denial. Real content, populated map/mobile review and user acceptance remain pending. Location FAQs/Knowledge Bank links and structured-data output depend on Modules 9 and 12. Next domain checkpoint: Careers and recruitment (Module 8). The accepted-module count is unchanged.

## Careers and recruitment checkpoint — 8 September 2026

Module 8 now provides `/careers`, job detail/application pages, general résumé submission, `/admin/jobs` and `/admin/candidates`. Openings support department/location/experience/employment/job-type filters; permanent, contract, internship and graduate roles; approved description/responsibilities/requirements; optional public salary; deadlines; and draft/review/approve/publish/close/reopen/archive. Public careers navigation and the homepage link point to the dedicated area. Published employee stories are reused; optional career introduction content uses existing published reusable blocks `careers-why` and `careers-life`.

Applications capture candidate details, cover letter and recruitment consent. PDF résumés are limited to 5 MB with MIME and signature validation, randomized private storage and authorized attachment-only downloads. The job is locked and rechecked for publication/deadline during submission. Failed transactions remove uploaded files. General applications are supported. The additive `candidates` migration is applied on local MySQL; no applications or job openings were seeded.

HR can search/filter candidates, assign recruiters, update the nine specified pipeline stages, record interview date/time and notes, download résumés and export scoped CSVs. Assigned-only recruiters cannot access other candidates or reassign/export records. Version checks prevent lost updates; audit events track review status/assignment changes and résumé downloads without placing application text or notes in audit data. CSV formula cells are escaped. Interview scheduling records an appointment only; no emails or calendar invitations are sent.

Verification: 117 tests / 1,112 assertions pass on isolated SQLite; Pint and diff checks pass. The MySQL-backed public careers page/application controls loaded in Chrome. Final screenshot inspection was blocked by Chrome's extension-UI issue. Populated content and full responsive review remain pending. The public project CSP was also corrected to permit its self-hosted viewer scripts, with a regression assertion. JobPosting structured data, approved career copy/legal retention procedures and communication integrations remain for their scoped checkpoints. Next domain: Insights, Knowledge Bank and FAQs (Module 9). Accepted-module count is unchanged.

## Insights, Knowledge Bank and FAQs checkpoint — 8 September 2026

Module 9 now provides `/admin/knowledge`, `/insights`, `/knowledge-bank` and `/faqs`, with dedicated public detail pages. All three content types use the existing revision, review, approval, scheduling, unpublication and archive workflow with distinct page permissions. Editors manage category/topic clusters, short direct answers, detailed explanations, public author/reviewer attribution, private verification evidence, basic SEO fields, featured placement and sort order. Publication requires verified attribution and substantive fields. No articles, claims or FAQs were invented or seeded into MySQL.

Published related records connect articles and FAQs to projects, active locations, services, pages and each other. Reverse links appear on those public detail pages; unpublished content and inactive location hierarchies are excluded. Publication revalidates references, including scheduled publication. Featured editorial content is reused on the homepage. Insights navigation now opens the dedicated library.

Public `/search` searches only published public fields across projects, services, locations, articles, knowledge, FAQs and current jobs. Category/topic/text filters are available within editorial libraries. CMS `/admin/search` limits content, media and enquiry results to current permissions, including assigned-only projects. Results are bounded; the current implementation uses application-side public matching and should be revisited for large-scale indexing. Private verification notes are not searchable publicly. Editorial title search is also available in the CMS library.

Verification: 123 tests / 1,262 assertions pass on isolated SQLite; Pint and diff checks pass. Chrome loaded the MySQL-backed public Knowledge Bank and authenticated editor, and a desktop screenshot was reviewed. Full populated-content/mobile acceptance remains pending. No database migration or dependencies were needed. Structured-data output, advanced SEO/canonical/OG controls and production indexing remain Module 12; the schema-eligibility preference is stored for that work. The existing scheduler still needs a persistent production operational setup. User acceptance remains pending and the accepted-module count is unchanged. Next domain: Enquiries, CTAs and CRM (Module 10).

## Enquiries, CTAs and CRM checkpoint — 8 September 2026

Module 10 now provides a dedicated `/contact` page, configurable typed forms at `/admin/enquiry-forms`, and an expanded CRM at `/admin/enquiries`. Construction, infrastructure, industrial, project, development, land/JV, vendor, career and general channels share validated capture. Earlier form labels are normalized for compatibility; existing enquiry records remain unchanged. Admins configure form availability, heading/introduction, enabled/required additional fields, default owner and optional owner notification. Core identity, message and consent fields remain mandatory. Contact navigation and relevant project/location/knowledge CTAs use the dedicated form; project enquiries preserve the published project reference and title, including after validation errors. Shared phone/WhatsApp settings remain the source for contact actions.

Leads support the nine requested pipeline statuses, qualified/unqualified/pending assessment, assignment, private append-only notes/change history, follow-up dates, due filters, search and filtered CSV exports with formula escaping. Assigned-only Business Development users can view/edit only their own leads, including CMS search. Assignment and export require their separate permissions. Version checks protect against lost updates, and audit metadata excludes private message/note contents. Additive MySQL migrations add CRM fields and the enquiry-notes table without changing existing submissions.

Submission captures supplied campaign/CTA hints, current referring path/host and a coarse device classification. These are visitor-supplied attribution hints, not verified analytics. First-touch/cross-page campaign attribution, call/WhatsApp conversation integration and campaign analytics remain Module 11. Enabled fields are allowlisted before storage. Optional supporting document uploads are not enabled in this checkpoint.

Owner email alerts are opt-in and queued through the database connection. The job rechecks current owner access and notification preference, sends only a CRM sign-in link, and records successful handling. Repeated successful handling is suppressed; delivery is not an exactly-once external-provider guarantee. Local mail remains the log mailer; no external messages, test leads or routing changes were created in live MySQL. Live SMTP delivery, persistent queue supervision and operational failure/retry review remain pending. Follow-up dates are internal scheduling records; automated reminders are not claimed.

Verification: 129 tests / 1,339 assertions pass on isolated SQLite; Pint and diff checks pass. MySQL migrations applied successfully. Chrome loaded the public Land/JV form and authenticated CRM list; screenshot capture was blocked by the recurring extension-UI issue. Full responsive/populated-data review and user acceptance remain pending. No new dependencies were added. Accepted-module count is unchanged. Next domain: Campaigns, analytics and integrations (Module 11).

## Campaigns, analytics and integration adapters checkpoint — 8 September 2026

Module 11 now provides campaign editors at `/admin/campaigns` and public `/campaign/{slug}` landing pages. Campaigns use existing revisions, approvals, scheduling and archive controls. Marketing users can prepare and submit drafts; publication and approval additionally require the corresponding page permissions. Editors configure headlines/copy, image or video, approved related services/projects/knowledge/FAQs/pages, reusable statistics, enquiry destination, CTA label and basic SEO fields. Relations and verification are revalidated at publication. Public campaign CTAs pass campaign context into the contact workflow. No campaigns, testimonials or marketing claims were invented or published. Arbitrary third-party scripts/pixels are not enabled.

First-party analytics is off until explicit opt-in at `/privacy-preferences`. Successful public page views then capture a random browser-session identifier, path, coarse device and supplied campaign/referrer hints. Private previews, admin routes, failed pages and raw search/query text are excluded. First/last consented touch survives subsequent page navigation and is attached to submitted enquiries; conversion events contain no customer contact fields. Withdrawal clears session tracking and deletes associated analytics events, while submitted CRM records remain. `/admin/analytics` displays 30-day consented traffic and aggregate enquiry/campaign counts, without exposing customer names or emails to marketing users. Counts are not represented as total visitors or verified ad-platform measurements. Retention/large-volume aggregation and production privacy-copy review remain final operational work.

The disabled-by-default `/integrations/lead-events` endpoint is a custom normalized lead bridge, not a direct Meta/call-provider webhook. It checks HMAC-SHA256 over timestamp plus raw body, rejects timestamps outside five minutes, validates explicit consent and lead fields, and records unique event IDs transactionally. Retries with identical bodies cannot duplicate or recreate deleted leads; changed payloads for the same ID return conflict. Accepted final call/WhatsApp lead submissions enter the CRM unassigned, with optional call duration/outcome/campaign. Only this signed endpoint is exempted from form CSRF protection. Credentials remain unset and no external traffic/messages were sent.

`/admin/integrations` exposes connection readiness and accepted-event metadata to settings administrators. Its authenticated published-source index excludes drafts and private evidence and describes the approved-knowledge boundary. This is source/adapter preparation only: no generative answers, WhatsApp conversation engine, automated qualification or outbound replies are claimed. Provider-specific translation, credentials, sandbox tests, Google/Meta tracking configuration, call-click instrumentation and live integrations remain pending.

Verification: 134 tests / 1,408 assertions pass on isolated SQLite; Pint and diff checks pass. The additive analytics/integration-event migrations are applied to MySQL. The campaign editor loaded and was visually inspected in Chrome, including its active sidebar state. Populated campaign/mobile review and user acceptance remain pending. No dependencies or live marketing content were added. Accepted-module count is unchanged. Next domain: SEO and discovery (Module 12).

## SEO and discovery checkpoint — 8 September 2026

Module 12 now provides `/admin/seo`, with a live inventory of published public pages, per-path search/social metadata, focus topics, indexing preference, canonical selection and structured-data controls. Metadata overrides are permission-gated, version-checked and audited; they apply immediately to published pages. Draft previews ignore overrides and remain noindex. Quality hints flag missing descriptions/topics, long or duplicate titles and missing cover alt text. This is not yet a complete rendered-page accessibility/thin-content audit.

The dynamic `/sitemap.xml` excludes drafts, hidden location hierarchies, expired jobs, noindex pages and pages canonicalized elsewhere. Canonical destinations must be existing public pages; canonical loops/chains are rejected at edit time and unavailable destinations fall back to the original page. Dynamic `/robots.txt` replaces the static file, retaining full blocking locally and respecting production’s global indexing switch. No live indexing setting was changed. Redirects accept only old internal paths and current public destinations, cannot replace active/system routes, and cannot create redirect chains or external redirects. Removed destinations stop redirecting. Unknown routes retain 404 behavior.

Public metadata includes canonical, robots, Open Graph and Twitter card fields. Safely encoded JSON-LD uses published content for Organization, WebPage, visible breadcrumbs, Article, Service and enabled FAQ pages. JobPosting appears only for open jobs with deadline and country/locality fields; job editors now collect and publicly show verified address details. Private salary/evidence and draft content are excluded. Preview schemas are suppressed. Structured data does not imply guaranteed search features, rankings or Google FAQ eligibility. Video/Person-specific enhancements, comprehensive source/content quality review, sitemap scaling and external Rich Results/Search Console verification remain pending where applicable.

Verification: 140 tests / 1,468 assertions pass on isolated SQLite; Pint and diff checks pass. The additive SEO metadata/redirect migrations are applied to MySQL. Chrome loaded and visually displayed the SEO dashboard and active sidebar state. Tests cover local noindex, sitemap exclusions, stale SEO writes, canonical loops, internal redirects, markup escaping, private field exclusion, job expiry and authorization. No company claims, location facts or SEO copy were invented. Guidance checked against Google Search Central’s sitemap, canonical and structured-data documentation. Full populated-content/mobile review and user acceptance remain pending.

Progress: **12/14 reached; 2/14 main modules remain**, plus earlier review/integration gaps. Next: Future Developments (Module 13), inactive by default. Final Validation/Handover is Module 14. Accepted-module count remains unchanged.


## Future developments checkpoint — 8 September 2026

Module 13 provides `/admin/developments` with revisioned development descriptions, category/location, amenities, construction progress, public gallery, masterplan and brochure references. Publication uses the existing review/approval workflow and requires verified content. The public feature is disabled by default through a version-checked administrator setting; disabled developments are absent from public routes, navigation, search and sitemap inventory. No business content or inventory was fabricated in MySQL.

Inventory follows development → tower → floor → unit, with Available/Hold/Booked/Sold/Blocked states, version checks, validated parent relationships and explicit verification before public visibility. Hidden parents hide their descendants. Public unit details include approved dimensions, type, facing, room counts and plans; prices require a separate public-price choice. Masterplan tower markers link to the corresponding inventory. Site-visit submissions validate currently public, available units and create unassigned CRM requests requiring staff confirmation; they do not reserve units or automatically confirm appointments.

Verification: 144 tests / 1,524 assertions pass on isolated SQLite. Pint and diff checks pass. The additive inventory migration is applied to MySQL. The authenticated development editor was loaded and visually inspected in Chrome. Populated masterplan/inventory and mobile acceptance remain pending approved assets/data. No AI property assistant, external booking integration or automated appointment notifications are claimed; those remain integration gaps.

Progress: **13/14 reached; 1/14 main module remains** — Final Validation & Handover — plus earlier content, review and integration gaps. Accepted-module count remains unchanged.


## Final validation — initial pass, 8 September 2026

Module 14 is in progress. Public hub checks now verify rendered headings and existing stylesheet/script references across thirteen public routes, guard production diagnostic exposure and check dedicated 404 recovery links. The 404 retains the existing section-visibility rules while pointing visitors to actual Projects and Contact pages. Chrome confirmed those destinations. All 147 tests / 1,594 assertions pass on isolated SQLite; Blade compilation, Pint and diff checks pass.

The original PDF audit is clearly marked historical and has a current implementation/gap update. Vite build verification remains open: node_modules and a JavaScript lockfile are absent. Current pages serve committed public assets. No dependency changes were made. Backup restoration, broader responsive/accessibility/performance review, provider-specific integrations and production operations remain unfinished; approved company/legal content and assets remain required inputs. No production-readiness or full PDF-completion claim is made.

Progress: **13/14 checkpoints reached; 1/14 remains open**, with Module 14 underway. Accepted count remains unchanged.


## Final validation evidence and operational handover — 8 September 2026

- Full isolated SQLite suite: **147 tests / 1,594 assertions passed**. Blade templates compile; Composer validation and diff checks pass.
- Public browser matrix: **70 route/viewport checks passed**, covering fourteen public/login pages at 320, 390, 768, 1024 and 1440 pixels. No horizontal overflow or unexpected HTTP status was observed. Contact-page mobile and desktop screenshots were visually reviewed. This is not a populated-content or complete CMS accessibility audit.
- Declared frontend dependencies installed with pnpm 11.19.0 and committed lockfile. Production build passes on Node 24.19.0. Removed unused Bunny font fetching from the scaffold; the existing rendered site uses its existing system fonts and committed public assets. Both PHP and locked JS dependency advisory checks reported no advisories at verification time.
- Private snapshot at `storage/app/private/final-backup-20260908-141142` contains SQL, environment and storage archive with restrictive permissions. Restoration into a fresh MySQL 8.4 instance, accessible only through a temporary socket, matched **38 tables / 224 rows** and **6 storage files**, plus the environment checksum. mysqlcheck passed. The temporary server was shut down. The working database was not overwritten. Evidence/scripts remain in ignored `tmp/final-validation`; neither credentials nor customer records are committed. This local snapshot is not an off-site backup service.

### Repeatable build and verification

Use PHP 8.4, Composer, Node 24 and pnpm 11.19.0. On an existing installation, use `composer install`, `pnpm install --frozen-lockfile`, `pnpm run build`, and `php artisan test --compact`. Tests use isolated SQLite. Do not use the fresh-install setup command against an existing environment or regenerate an existing APP_KEY. Public pages currently reference versioned-in-Git `public/assets` files rather than the scaffold Vite bundle.

### Production operations still to configure on the selected host

Use the web root `public/`, HTTPS, production environment, debug disabled and secure session cookies. Retain the existing application key and protect the environment, private uploads and database credentials. Apply migrations only after a verified backup. Local diagnostic routes must stay unavailable in production. Two-factor enforcement is mandatory outside local development.

Run Laravel's scheduler every minute (`php artisan schedule:run`) through the host's scheduler. The registered scheduled task publishes approved due content. Supervise `php artisan queue:work --tries=3 --timeout=60`, restart workers after deployment, and review `php artisan queue:failed` and application logs. These commands are operational instructions; no persistent production services were installed or claimed. Configure real mail transport before enabling notifications. Monitor `/up`, application errors, queue failures, disk usage and backup age through the selected hosting provider.

For backups, use a private MySQL client option file or a secret manager; never put a password in command history. Capture a consistent `mysqldump --single-transaction --no-tablespaces --set-gtid-purged=OFF` plus `storage/app` and the existing environment/key. Coordinate file writes during production snapshots. Encrypt and retain backups off-site according to the approved retention policy. Rehearse restoration into an isolated database and file directory, compare records/checksums, then test the restored application before any cutover. Never test restoration by overwriting the active database.

### Requirements that prevent full PDF/launch completion

1. Approved company/contact/legal content, real assets and populated business records; publishing and acceptance by the owner.
2. Hosting/domain and live email/WhatsApp/call provider configuration, provider-specific adapters and end-to-end delivery verification; AI website/property assistants are not implemented by the normalized source/lead adapters.
3. Earlier software scope gaps: document-rich vendor/land intake, editorial calendar, social derivatives, advanced structured-data/content checks and analytics retention. These are not resolved merely by receiving credentials.
4. Populated CMS/public responsive and accessibility acceptance, production performance/monitoring, supervised services and ongoing off-site backups on the chosen host.

**13/14 checkpoints reached; final acceptance remains 1/14 open.** Local validation evidence is complete for the boundaries above; the full PDF is not declared complete and production deployment has not been performed.


### Confirmed hosting target and owner-managed inputs

The owner confirmed `goyalestatedeveloper.com` on Hostinger and will manage provider configuration and approved company/contact/legal content. Production `APP_URL` must be `https://goyalestatedeveloper.com`; local configuration remains unchanged. The exact Hostinger plan and deployment access have not been supplied. Verify PHP 8.4 CLI/web support, Composer platform requirements, MySQL, private storage and cron/worker support on that plan before uploading. Deploy this repository, not Hostinger's separate Laravel auto-installer skeleton.

For shared hosting without a persistent worker supervisor, assess a bounded scheduled queue worker (`queue:work --stop-when-empty --max-time=50 --tries=3 --timeout=45`) with overlap protection on the selected host; use a supervised worker on a VPS. Do not assume VPS administration commands are available on shared hosting. Keep the Laravel application and private storage outside public_html and expose only public assets/front controller using the host-supported document-root setup.

Hostinger references checked: https://www.hostinger.com/support/which-programming-languages-and-frameworks-are-supported-at-hostinger/ and https://www.hostinger.com/support/which-server-capabilities-are-supported-at-hostinger/. Plan-level verification and actual deployment remain pending access. Owner-managed content/provider inputs are deferred by the owner, not fabricated or silently configured. The software gaps listed above remain separate from that deferral.
