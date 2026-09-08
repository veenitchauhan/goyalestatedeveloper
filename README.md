# GOYAL ESTATE & DEVELOPERS PVT. LTD.

Local site: http://goyalestatedeveloper.test · Login: http://goyalestatedeveloper.test/login

Laravel 13.30.1 / PHP 8.4 / MySQL 8.4 / Blade. Composer dependencies are pinned in composer.lock. Small Alpine interactions and compiled frontend assets will be introduced with the relevant UI modules; the current foundation needs no frontend build process.

## Delivery status

14 modules total; 1 complete; 13 remaining. Module 2 implementation and automated verification are ready, but first-administrator provisioning and user acceptance remain pending. Do not start Module 3.

Module 1 is committed as `62976e6`: architecture, vhost, checkpoint and single-line company branding. Module 2 replaces the PHP checkpoint with Laravel/Blade, adds Fortify login and two-factor authentication, 12 predefined roles, backend gates, assigned-content policies, user access management, private audit logs and identity/content migrations. Role permissions for later modules are predefined; their business interfaces are not implemented yet. No public registration or password-reset email delivery is enabled.

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

25 tests / 108 assertions pass on SQLite and the isolated MySQL test database. Coverage includes login/logout, rate limits, inactive accounts, two-factor enrollment/challenge/recovery, user creation and access changes, assigned-record policies, admin view rendering, bootstrap restrictions and branded routes. Live HTTP checks confirmed Laravel health output, login HTTP 200 and missing-CSRF rejection (419). Checkpoint and login were visually reviewed on desktop/mobile. Credentials and database files were checked against the staged Git contents.

## First administrator

No real administrator has been provisioned. An authorized operator can run `php artisan app:create-admin` to enter their chosen name, email and password privately. The command refuses a second bootstrap account. The local-only `--local-bootstrap` option generates credentials into ignored `storage/app/private/local-admin.json`; executing this option is pending explicit approval.

Super Admins must confirm their password and complete authenticator enrollment before entering administration. Keep recovery codes privately. Production accounts must be provisioned through an authorized process; do not deploy local bootstrap accounts or credentials.

## User testing for Module 2

1. Confirm the checkpoint and login load at the local domain.
2. After authorized admin provisioning, sign in, confirm password and enroll two-factor authentication.
3. Inspect Users, Roles & permissions, and Audit log. Add a test Viewer account and verify it cannot access Users, Roles or Audit log directly.
4. Sign out/in and test authenticator or recovery-code login. Review mobile layout.
5. Report feedback and explicitly approve Module 3 before work continues.

## Working agreement

Implement one module at a time. Verify it, commit and push with a clear change/validation description, report total/completed/remaining modules, and pause for user testing and explicit authorization of the next module. Silence is not approval. The supplied PDF is a requirements reference and does not override the user's instructions. Local development does not authorize production deployment.

See [module plan](docs/MODULES.md), [architecture](docs/ARCHITECTURE.md) and [local setup](docs/LOCAL-SETUP.md).
