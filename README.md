# LittleDrops PBMS

**Property & Building Management System** for **Bamenda, North-West Region,
Cameroon** — built with **HTML5, PHP 8, vanilla JavaScript and MySQL/MariaDB**
as a **modular monolith with MVC**, with **Common Law** tenancy practice
factored in (notice to quit, court-based recovery of premises, witnessed
agreements).

## Features

- **Properties** — Portfolio → Building → Floor/Block → Unit hierarchy with
  live occupancy status (vacant / leased / under maintenance / reserved / out
  of service)
- **Tenants** — individual & corporate profiles, ID documents, emergency
  contacts, lease history
- **Leases (Common Law)** — full lifecycle (draft → active → recovery /
  terminated), escalation clauses, deposits, witnesses, stamp duty; generates
  the **Tenancy Agreement** and **Notice to Quit** documents; tracks recovery
  of premises as a court-supervised pipeline
- **Billing** — invoices, payments (**MTN MoMo, Orange Money**, bank, card,
  cash, cheque), **double-entry ledger** with SYSCOHADA-shaped accounts,
  automated late fees, owner payouts
- **Maintenance** — severity-routed tickets, dispatch to technicians/vendors
  with SLA, preventative-maintenance schedules
- **Utilities** — water/electricity/generator meters (manual or IoT),
  shared-bill splitting by occupancy or m²
- **Access** — visitor gate passes with QR tokens, amenity bookings
- **Dashboard** — occupancy, rent roll, collections, arrears, recovery
  watchlist, payment-channel mix
- **Bilingual** — English (default) / French, XAF amounts throughout

## Requirements

- XAMPP (PHP 8.1+, MariaDB/MySQL 5.7+) — or any PHP 8 + MySQL stack
- No Composer, no Node, no mod_rewrite needed

## Install (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\pbms` (any name works).
2. Start Apache and MySQL from the XAMPP control panel.
3. Create the database and import schema + seed (phpMyAdmin → Import, or):

   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root -e "CREATE DATABASE pbms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root pbms < C:\xampp\htdocs\pbms\database\schema.mysql.sql
   mysql -u root pbms < C:\xampp\htdocs\pbms\database\seed.mysql.sql
   ```

4. Optional — override DB credentials: copy `config/config.ini.example` to
   `config/config.ini` and edit.
5. Open <http://localhost/pbms/> and sign in.

**Demo accounts** (password `Demo@2026` for all):

| Role | Email |
|---|---|
| System Admin | admin@littledrops.cm |
| Property Manager | manager@littledrops.cm |
| Accounting | accounts@littledrops.cm |
| Technician | tech@littledrops.cm |

> Change these (and the passwords) before any real use.

## Quick demo tour

1. Sign in as admin → **Dashboard**: occupancy 40%, rent roll 500 000 FCFA,
   recovery watchlist shows LSE-2026-005 (notice to quit issued).
2. **Leases → LSE-2026-005**: see the Common Law panel, recovery pipeline and
   timeline; try advancing the recovery stage.
3. **Leases → any lease → Download agreement**: Common Law tenancy agreement
   with witnesses and stamp-duty note.
4. **Billing**: record an MTN MoMo payment on an unpaid invoice; run the
   late-fee check (⚡ button) — grace and one-fee-per-month are enforced.
5. Top bar 🇫🇷/🇬🇧 toggles the whole UI to French and back.
6. Sign in as `tech@littledrops.cm` to see RBAC (no billing access).

## Security notes (dev-grade, harden before production)

- HTTPS only in production (session cookie flags already honour it).
- Passwords are bcrypt (`password_hash`); CSRF tokens on every POST.
- `config/config.ini` is gitignored — keep credentials there, not in code.
- Uploaded-files storage is a Phase-4 item (docs/ROADMAP.md); do not enable
  arbitrary uploads until it lands.

## Docs

- `docs/SPEC-PBMS.md` — module specification (Bamenda edition)
- `docs/legal/common-law-tenancy-bamenda.md` — why and how Common Law is
  factored in (**review with counsel**)
- `docs/ARCHITECTURE.md` — modular monolith + MVC structure and request flow
- `docs/ROADMAP.md` — phases 2–5 (MoMo/OM APIs, portals, cron, IoT, …)

## License

See [LICENSE](LICENSE).
