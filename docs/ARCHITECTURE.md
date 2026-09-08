# Platform architecture

## Foundation decision

Use the existing local Apache/PHP-FPM environment and a dedicated public document root. Use a Laravel modular monolith with server-rendered Blade pages, small Alpine interactions and compiled CSS/JS. Use MySQL for the application, independent of other local projects, with database-backed jobs initially and a queue adapter that can later use Redis. Module 2 pins Laravel 13.30.1 and Fortify 1.39.0. The local project uses an isolated MySQL 8.4 instance on port 3307; the original server remains unchanged. Module 1 installed no framework and created no database or accounts.

The checkpoint is now a Blade view served by the Laravel entry point. It is development tooling, not the production CMS, and is unavailable in production.

## Information architecture

Public routes: `/`, `/about/{page?}`, `/business/{service?}`, `/capabilities/{capability?}`, `/projects`, `/projects/{slug}`, `/careers`, `/careers/{slug}`, `/insights/{slug?}`, `/knowledge/{slug?}`, `/faqs`, `/locations/{slug}`, `/contact`, `/campaign/{slug}` and approved legal routes. Use query parameters for project filters to avoid conflicts with project slugs. Legacy alternate service URLs redirect to a single canonical route.

`/admin` has permission-scoped module navigation and search. `/developments` and all descendant routes remain feature-gated and unpublished initially. Unknown and unpublished content returns 404. Draft previews use short-lived signed URLs plus authorized preview access. Local environments send noindex headers and block crawling.

## Domain boundaries

Identity, Publishing, Media, Corporate, Projects, Locations, Recruitment, Knowledge, Enquiries, Marketing, Discovery and Developments each own their controllers, validation, policies, services, models and tests. Cross-domain writes happen through application services and events. Lead intake is shared by forms and provider adapters; retries are idempotent. No provider credentials or private applicant data enter public HTML.

## Relational schema blueprint

All mutable entities have primary keys and timestamps. Foreign keys enforce ownership; pivots have unique composite keys. Slugs are unique within their route namespace. Query indexes cover status, published_at, owner/assignee, location and common filters. Money uses decimal plus currency; progress is constrained to 0–100; dates are timezone-aware at the boundary and stored consistently in UTC. Private records never share public media paths.

| Domain | Tables and key relationships |
|---|---|
| Identity | users, roles, permissions, role_user, permission_role; project_user for assignment; audit_logs(actor, action, entity, redacted before/after, timestamp) |
| Publishing | content_entries(type, slug, title, status, scheduled_at, published_at, author_id, approver_id), content_revisions, approval_events; pages(entry_id), page_sections(page_id, component_type, position, enabled, validated configuration); menus, menu_items(parent_id, entry_id or safe URL); settings(group, key unique, typed value); statistics(key unique, value, unit, verified_at, published); ctas(destination type, setting/form/entry reference) |
| Media | media(storage key, original key, MIME, byte size, width/height, visibility, alt, caption, source, uploader); media_derivatives(media_id, variant, watermark settings, storage key); media_associations(media_id, content_entry_id, order, category); documents(media_id, visibility, entry_id) |
| Corporate | business_units, services(entry_id, business_unit_id), capabilities(entry_id), equipment(entry_id, category_id, model, manufacturer, capacity, verified quantity), team_members, company_milestones, employee_stories; no fabricated records seeded as published |
| Locations | locations(parent_id, type country/state/region/city/area, slug, name, latitude, longitude, operating_status, verified_at, entry_id); project_location, service_location |
| Projects | projects(entry_id, sector_id, location_id, approved client, scope, areas, start/expected/actual completion, status, progress, manager_id, approved value/currency, featured); project_stages(project_id, order, label, status, percent, dates); project_updates(project_id, stage_id, summary, occurred_at); project_media(project_id, media_id, stage_id, category, order, cover); project_equipment; project_service |
| Recruitment | departments, jobs(entry_id, department_id, location_id, type, requirements, deadline, salary visibility, open/closed status); candidates(private identity/contact); applications(candidate_id, job_id nullable, consent_version, submitted_at, status, private resume_media_id); interviews(application_id, interviewer_id, scheduled_at, status); candidate_notes; recruitment_events; programs |
| Knowledge | posts(entry_id, category_id), knowledge_articles(entry_id, direct_answer, detailed_body, author_id, reviewer_id), faqs(entry_id, question, answer, schema_enabled), categories(parent_id), tags, content_tags; content_relations(from_entry_id, to_entry_id, type) for projects/services/locations/FAQ connections |
| Enquiries | form_definitions, form_fields(form_id, type, validation schema, order), routing_rules; leads(type, owner_id, assigned_to, status, contact, requirement, consent, source_id, landing URL, attribution); lead_notes, lead_events, lead_followups; lead_sources; private lead_documents; notification_routes; outbox_jobs |
| Marketing | campaigns, landing_pages(entry_id, campaign_id), conversion_events(event_id unique, consent state, campaign_id, CTA, anonymized context), integration_configs(secret reference only), webhook_receipts(provider, external_id unique, status), conversations(lead_id, provider reference), consent_records(version, categories, timestamp) |
| Discovery | seo_metadata(entry_id unique, title, description, canonical, robots, OG media, focus topic, validated schema type); redirects(source_path unique, target, status); search index of authorized content; sitemap generated from published indexable entries |
| Developments | development_projects(entry_id, location_id), development_towers(project_id), development_floors(tower_id), development_units(floor_id, number unique per tower, type, area, facing, beds, baths, approved price/currency, inventory status), floor_plans(media_id), amenities, development_amenity, site_visits(project_id, lead_id, requested_at, status); feature_flags |

Publishable records extend a content entry, so permissions, revisions, SEO, related content and publishing rules are consistent. Large structured facts remain typed relational columns; JSON is restricted to validated component/form configuration. In Module 2 implement identity and core content migrations first; each later module owns additive migrations for its domain.

## Authorization matrix

Backend policies deny by default. View/create/edit/delete/publish/unpublish/approve/export/assign/upload/manage-SEO/manage-settings are separate permissions. Assignment restrictions apply to reads, mutations, exports and search, including API requests.

| Role | Default boundary |
|---|---|
| Super Admin | Full control, users/roles/integrations; privileged actions audited; 2FA |
| Admin | Website management and approval; no implicit security/integration-secret administration |
| Project Manager | Projects, progress, milestones and project media; publishing separately granted |
| Project Editor | Assigned projects only; draft edits and uploads, no global publish |
| HR Manager | Jobs, candidates, interviews, recruitment assignment and permitted exports |
| HR Executive | Assigned/permitted candidate processing; no job publishing or bulk export by default |
| SEO Manager | Metadata, redirect/schema controls, content draft contributions; no candidate/lead data |
| Content Manager | Pages, articles, knowledge and FAQs; review/approval separately granted |
| Digital Marketing Manager | Campaigns, CTAs, attribution and aggregate analytics; no unrestricted script execution |
| Media Manager | Public media and watermarking; no private resumes or lead attachments |
| Business Development | Assigned leads, notes and follow-ups; assignment/export separately granted |
| Viewer | Explicitly permitted non-sensitive read-only records; no default access to candidates/leads |

## Publishing and reusable UI

Draft → review → approved → published, with optional scheduled publication, unpublish and archive. Editing a published entry creates a revision; the approved live revision stays visible until replacement is approved. Statistics, contacts, footer, menus and CTAs pull from central records. Empty/unverified facts stay hidden.

Reusable components: corporate header/mobile menu/footer, hero, statistic strip, service grid, project card/filter/detail facts, stage timeline, responsive gallery, equipment card, location map with accessible list, FAQ accordion, article/knowledge cards, job card, validated dynamic form and contextual CTA. Admin section controls support enabling, ordering and reusing references without duplicating business data. Corporate styling will be reviewed in Module 4.

## Media, recruitment and lead protection

Validate file extension, actual MIME, size and image decoding; randomize storage names; reject executable uploads. Preserve originals; background workers create responsive variants and optional full-name watermarks. Private documents require authorized streamed downloads or short-lived storage URLs. Resume/application limits, consent versions, retention and deletion policies are configurable. Logs redact candidate contact details, secrets and message bodies.

Use framework sessions, password hashing, CSRF, escaping, database parameter binding, throttling and object policies. Form fields use an allowlisted schema and server validation. Never accept arbitrary recipient addresses, executable templates or scripts from public form input. Queue notifications through configurable recipients. Use signed webhooks, external event IDs, timeout/retry limits and outbox delivery tracking.

## Discovery, integrations and future scope

Only published, approved first-party content powers website answers and WhatsApp qualification. Separate general guidance from company facts; do not invent capabilities, prices or availability. Provider implementations are replaceable adapters for CRM, WhatsApp, email, calls, analytics and AI. Local tests use explicit fakes; live verification requires actual provider access and is reported separately. Tracking initializes according to configured consent; click events are never claimed to prove connected phone calls.

SEO metadata and schema reflect visible content. Search indexes only published records for public users, and applies policies for admin users. Generate canonical sitemap URLs on publish/unpublish. Flag thin content, missing alt/title/canonical and duplicate slugs/titles. Do not promise rankings.

Future development activation is controlled by Super Admin, approved content and a feature gate shared by routes, navigation, search, sitemap and APIs. Inventory transitions use transactions and locking to prevent contradictory unit reservations. New projects, cities, services, jobs and developments are records, not new templates or deployments.

## Verification and release

Every module has behavior-focused checks and a user acceptance pause. Final QA covers widths 320, 360, 375, 390, 414, 430, 768, 820, 1024, 1280, 1440, 1600 and 1920+, keyboard access, reduced motion, role boundaries, uploads, forms, search, SEO, query performance and real backup restoration. Database/media/config backup, queue monitoring and sanitized error reporting are required before production. Local completion does not authorize deployment.
