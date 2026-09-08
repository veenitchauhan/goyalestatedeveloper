# Delivery plan: 14 modules

Status: 14 total, 2 complete (Modules 1 and 4), 12 remaining. The user approved the homepage and deferred real images to later CMS uploads. Module 3 CMS implementation is ready for testing, and the first local administrator has now been provisioned with explicit authorization. Modules 2 and 3 now await user testing. No next module has started.

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
| 2 | Laravel/MySQL foundation implemented; tests pass | First local administrator provisioned with explicit approval; user testing pending | User explicitly prioritized homepage correction |
| 4 | Approved corporate homepage; real imagery deferred by user | Accepted | Yes, carry on with CMS |
| 3 | Revision workflow, pages, reusable blocks/statistics, menus, homepage settings and private-original media library implemented and tested; local scheduler requires a running worker | Awaiting CMS testing; local administrator provisioned | No |
