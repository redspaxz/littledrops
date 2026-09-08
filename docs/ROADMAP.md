# Roadmap

## Phase 1 — Foundation (DONE, this build)

- [x] Modular-monolith MVC skeleton (core kernel + 9 modules)
- [x] Property hierarchy, units, occupancy engine
- [x] Tenants & staff RBAC (admin, manager, accountant, technician)
- [x] Lease lifecycle: draft → activate → terminate; escalation fields
- [x] Common Law factoring: legal_system/jurisdiction/notice fields, agreement
      generator, notice-to-quit generator, recovery-case pipeline (court-based)
- [x] Billing: invoices, payments (MoMo/OM/bank/card/cash), double-entry
      ledger, late-fee automation, owner payouts table
- [x] Maintenance tickets + work orders + PM schedules
- [x] Utilities meters/readings + shared-bill allocation
- [x] Visitor passes + amenity bookings
- [x] Dashboard KPIs (occupancy, rent roll, collections, arrears, recovery)
- [x] EN/FR bilingual UI, XAF formatting, Bamenda seed data

## Phase 2 — Legal depth

- [ ] Proof-of-service capture (photo/bailiff affidavit) on notices
- [ ] Distress-for-rent case type (Common Law remedy, Anglophone regions)
- [ ] Move-in / move-out checklists as generated documents
- [ ] Renewal wizard with escalation calculator (per lease escalation_pct)
- [ ] Deposit refund workflow with inspection-linked deductions
- [ ] Counsel-reviewed agreement/notice templates (see docs/legal)

## Phase 3 — Payments & comms integration

- [ ] MTN MoMo API (collection WebRTC/API) — sandbox → production
- [ ] Orange Money API
- [ ] Payment webhook endpoint + reconciliation report (bank statement match)
- [ ] SMS/email alerts: rent due, receipt, notice reminders (Twilio or local
      gateway e.g. Camtel/MTN bulk SMS)
- [ ] Owner statements (PDF) + payout scheduling

## Phase 4 — Portals & ops

- [ ] Tenant portal (login, invoices, MoMo payment link, ticket submission,
      visitor invites with QR)
- [ ] Owner/landlord portal (statements, payouts)
- [ ] Cron jobs (Windows Task Scheduler / cron): late fees, renewal notices,
      PM due reminders, escalation application
- [ ] File uploads (ticket photos, tenant IDs, meter photos) with storage
      outside docroot
- [ ] IoT meter ingestion endpoint (token-authenticated JSON)

## Phase 5 — Scale (only when needed)

- [ ] PostgreSQL port (PDO DSN + schema dialect)
- [ ] API-first JSON endpoints for a React Native / Flutter tenant app
- [ ] Audit log hardening (immutable events table, export)
- [ ] Backups: nightly mysqldump + off-site copy
