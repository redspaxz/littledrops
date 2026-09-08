# PBMS — Functional Specification (Bamenda Edition)

Property & Building Management System for **Bamenda, North-West Region, Cameroon**,
adapted from the core PBMS modules specification. This edition localises every
module for Cameroonian practice and factors in the **Common Law** legal system
that governs the Anglophone regions.

## Localisation summary

| Concern | Decision |
|---|---|
| Currency | XAF / FCFA — integer amounts only (`BIGINT` columns, `fmt_xaf()` display) |
| Payments | MTN MoMo and Orange Money first; bank transfer, card, cash, cheque supported |
| Language | Bilingual EN/FR with English default (Bamenda is anglophone) |
| Legal system | Common Law (North-West / South-West); Civil Law selectable for francophone portfolios |
| Accounting | Double-entry ledger with SYSCOHADA-shaped chart of accounts (OHADA space) |
| Location model | Quarter (Nkwen, Mankon, Old Town, Azire, Up Station…) → City (Bamenda) → Region |
| Phones / identity | +237 numbers; national ID, passport, certificate of incorporation |

## Module map (spec → implementation)

### 1 · Core Property & Asset Directory
Portfolio → Building → Floor/Block → Unit hierarchy; unit attributes (type, m²,
bedrooms, amenities JSON, initial condition report); occupancy status engine
(`vacant / leased / under_maintenance / reserved / out_of_service`) kept in sync
with lease lifecycle events.
**Tables:** `portfolios`, `buildings`, `floors`, `units` · **Module:** `Properties`

### 2 · Tenant & User Management
Staff RBAC (System Admin, Property Manager, Accounting, Technician; Owner and
Tenant portal roles reserved), tenant profiles (individual/corporate), identity
documents, emergency contacts, lease history per tenant.
**Tables:** `users`, `tenants`, `owners` · **Module:** `Auth`, `Tenants`

### 3 · Lease & Contract Lifecycle (Common Law factored)
Lease creation with configurable billing cycle, fixed vs dynamic rent with
escalation clauses, grace days and penalty %, deposits. **Common Law fields:**
`legal_system`, `jurisdiction`, `notice_to_quit_days`, witnesses, stamp duty.
Generates the tenancy agreement (quiet enjoyment, forfeiture via notice,
court-supervised recovery, witnessed execution) and the formal **notice to
quit**. The recovery pipeline is modelled as an ordered case:
`demand_letter → notice_to_quit_issued → notice_expired → court_filing →
judgment → enforcement → closed`. **Self-help eviction is not representable.**
**Tables:** `leases`, `lease_events`, `lease_documents`, `recovery_cases` · **Module:** `Leases`

### 4 · Accounting & Financial Management
Invoices (rent / utility / late fee / amenity) with lines; payments via MoMo,
OM, bank, card, cash, cheque; **double-entry journal** (invoice: DR 411 / CR 706;
payment: DR 521/571 / CR 411); late-fee automation on a grace rule (5% after
7 days by default); owner payouts with management-fee deduction.
**Tables:** `invoices`, `invoice_lines`, `payments`, `journal_entries`,
`journal_lines`, `gl_accounts`, `late_fee_rules`, `owner_payouts` · **Module:** `Billing`

### 5 · Maintenance, FM & Work Orders
Tenant-submitted tickets with severity (urgent/high/low), dispatch to internal
technicians or vendors with SLA hours, status flow (open → assigned →
in_progress → resolved → closed), preventative-maintenance schedules
(generator servicing, fire audits, HVAC).
**Tables:** `maintenance_tickets`, `work_orders`, `vendors`, `pm_schedules` · **Module:** `Maintenance`

### 6 · Utilities & Smart Building
Meters (water, electricity, gas, generator fuel) per unit or building with
manual or IoT-flagged readings; shared bills split by occupancy or m²
(e.g. plaza generator fuel across shops).
**Tables:** `meters`, `meter_readings`, `utility_bills`, `utility_allocations` · **Module:** `Utilities`

### 7 · Security, Access & Visitor Management
Visitor gate passes with QR tokens and check-in log; amenity booking
(conference room, parking) with hourly rates in XAF.
**Tables:** `visitor_passes`, `amenities`, `amenity_bookings` · **Module:** `Access`

### 8 · Reporting & Analytics Dashboard
Occupancy rate and unit status mix, monthly rent roll (cycle-normalised),
collections this month, outstanding arrears, open tickets by severity,
recovery watchlist, payment-channel mix (MoMo vs OM vs …), days-on-market and
turnover metrics planned next.
**Module:** `Dashboard`

## Source

Derived from *Property & Building Management System — Core System Architecture
& Functional Specifications* (pbms_modules_specification.pdf, 3 pp.), which
recommends React/Node/PostgreSQL stacks. This implementation deliberately
targets a XAMPP-friendly **HTML5 + PHP 8 + vanilla JS + MySQL/MariaDB** stack
per project direction — see `docs/ARCHITECTURE.md` for the mapping and the
migration path if the system later outgrows a single server.
