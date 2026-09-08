# Delivery plan: 14 modules

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
