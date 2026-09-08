# Architecture — Modular Monolith, MVC

## Style

**Modular monolith**: one PHP application, one MySQL database, one deployment
unit. Each PBMS business module owns its Models, Views, Controllers and
self-registers its routes — no module reaches into another module's internals;
cross-module reads go through the other module's Models (e.g. Billing reads
lease data via `Modules\Leases\Models\Lease`). Shared plumbing lives in a thin
kernel (`core/`), so a module can later be extracted to a service without
rewriting the domain logic.

**MVC** per module: Controllers validate input + enforce RBAC, Models own SQL
and invariants (transactions wrap multi-table changes), Views are plain PHP
templates rendered through `Core\View` with the `app`/`auth` layouts. There is
no ORM and no framework — the stack is HTML5 + PHP 8 + vanilla JS + MySQL, in
line with the project direction (XAMPP-friendly).

## Layout

```
pbms/
├── index.php                # single front controller (all requests)
├── core/                    # shared kernel
│   ├── autoloader.php       # PSR-4-ish: Core\ → core/, Modules\ → modules/
│   ├── bootstrap.php        # session, timezone, loads
│   ├── Config.php           # dot-notation config + config/config.ini overrides
│   ├── Database.php         # PDO singleton + query helpers + tx()
│   ├── Request.php          # route (?r=), input (JSON/form), typed accessors
│   ├── Response.php         # redirect+flash, JSON, HTML download
│   ├── Router.php           # pattern routes, module self-registration
│   ├── View.php             # 'Module::view' resolution + layouts
│   ├── Controller.php       # base: view(), requireAuth/requireRole, CSRF
│   ├── Auth.php             # sessions, login, RBAC roles, locale
│   ├── helpers.php          # url(), asset(), e(), t(), fmt_xaf(), badge(), csrf
│   └── translations.php     # EN/FR dictionary
├── layouts/                 # app.php (sidebar shell), auth.php (login)
├── modules/
│   ├── Auth/       # login/logout/locale + login view
│   ├── Dashboard/  # KPI read-model (Stats) + 404
│   ├── Properties/ # portfolios, buildings, floors, units
│   ├── Tenants/    # tenant profiles + lease history
│   ├── Leases/     # lifecycle, documents (agreement, notice to quit), recovery
│   ├── Billing/    # invoices, payments, ledger, late fees, payouts
│   ├── Maintenance/# tickets, work orders, vendors, PM schedules
│   ├── Utilities/  # meters, readings, shared-bill allocation
│   └── Access/     # visitor passes, amenities, bookings
│       └── (each: routes.php + Controllers/ + Models/ + Views/)
├── assets/          # css/app.css, js/app.js (progressive enhancement)
├── database/        # schema.mysql.sql + seed.mysql.sql
├── config/          # config.ini.example (copy to config.ini)
└── docs/            # this file, SPEC-PBMS.md, legal/, ROADMAP.md
```

## Request flow

1. `index.php` → `core/bootstrap.php` (autoloader, session, config).
2. `Router::loadModules()` includes every enabled module's `routes.php`
   (module list lives in `Config['modules']`).
3. `Router::dispatch(Request::route())` — the route comes from `?r=` so the
   app runs on any Apache config without mod_rewrite; `url()` always
   generates `index.php?r=...` links.
4. Controller (instantiated by the router) checks RBAC + CSRF, calls Models,
   renders a View through a layout. Mutations use POST + redirect + flash
   (PRG); `data-confirm` buttons add a JS confirm layer on top.

## Key decisions

- **Money** = `BIGINT` XAF integers everywhere; `fmt_xaf()` formats. No floats.
- **Ledger** = every invoice/payment posts balanced `journal_lines`
  (SYSCOHADA-shaped GL codes); posting happens inside `Database::tx()`.
- **CSRF** = per-session token on every POST form; JSON endpoints accept the
  token in the body.
- **RBAC** = role allow-lists per controller action; HTML requests redirect
  with a flash, AJAX requests get 401/403 JSON.
- **Documents** = agreement/notice rendered from PHP templates, stored in
  `lease_documents.content` and served as HTML downloads (printable to PDF).
- **i18n** = `t('key')` with EN/FR dictionaries; locale in session, switchable
  from the top bar.

## From the PDF spec's stack to this one

The source specification suggested React/Next + Node/Python + PostgreSQL +
Redis + S3. This build follows the project direction (plain PHP + MySQL on
XAMPP). The concepts map 1:1 — modular boundaries, ledger, RBAC — so a later
migration (e.g. PostgreSQL via PDO DSN change + API-first frontend) is
incremental, not a rewrite.
