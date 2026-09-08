# Local setup and checkpoint

Target: `http://goyalestatedeveloper.test` on existing Apache port 80 and PHP-FPM port 9000. Only loopback/local requests are allowed. Only `public/` is served.

The vhost source is `infra/apache/goyalestatedeveloper.test.conf`. Install it under `/opt/homebrew/etc/httpd/extra/` and add one Include in `/opt/homebrew/etc/httpd/httpd.conf`. Add `127.0.0.1 goyalestatedeveloper.test` to `/etc/hosts`. Back up the affected files first, validate Apache using `/opt/homebrew/opt/httpd/bin/httpd -t`, then gracefully reload. Preserve existing hosts and sites.

## Module 1 user test

1. Open http://goyalestatedeveloper.test and confirm the full company name and development checkpoint appear.
2. Confirm the total/completed/remaining module counts and all 14 module names.
3. Click “Check local connection”; expect JSON containing `status: ok`.
4. Test a narrow mobile window and desktop window for readable layout without horizontal scrolling.
5. Open `/not-a-page`; expect a branded 404 with a working return link.
6. Send feedback or explicitly approve Module 2. No further module is authorized automatically.

The checkpoint is intentionally not the finished website. Laravel and MySQL are now installed, and /login serves the Fortify login page. First-administrator provisioning is pending approval; CMS comes in Module 3. See README.md for MySQL start/stop commands and Module 2 test instructions.

## Current database — 8 September 2026

The application now uses MySQL at `127.0.0.1:3306`, database `goyalestatedeveloper`. The local `.env` holds credentials. The 31-table SQLite snapshot and configuration backup are retained privately; see README for the cutover and recovery boundary. The older port-3307 instance is untouched. All 15 migrations are applied. Automated tests continue to use isolated in-memory SQLite.
