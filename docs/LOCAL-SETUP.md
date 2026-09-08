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

The checkpoint is intentionally not the finished website, and there is no database/CMS/login yet. The production stack is planned in `ARCHITECTURE.md`.
