-- ============================================================
--  PBMS — Property & Building Management System
--  Schema for MySQL / MariaDB (XAMPP: import via phpMyAdmin or CLI)
--
--  Cameroon context baked in:
--   * All money columns are BIGINT (XAF has no minor units).
--   * leases.legal_system / jurisdiction / notice_to_quit_days encode the
--     bijural reality: Bamenda (North-West Region) is a Common Law
--     jurisdiction; recovery of premises follows the notice-to-quit ->
--     court-order pipeline, never self-help eviction.
--   * gl_accounts uses SYSCOHADA-shaped account codes (OHADA region).
--   * payments.channel covers MTN MoMo / Orange Money first.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Module 1 · Property & Asset Directory
--    Portfolio -> Building -> Floor/Block -> Unit
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  owner_id   INT UNSIGNED NULL,
  notes      TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buildings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  portfolio_id INT UNSIGNED NOT NULL,
  name         VARCHAR(120) NOT NULL,
  quarter      VARCHAR(80)  NOT NULL,           -- e.g. Nkwen, Mankon, Old Town
  city         VARCHAR(80)  NOT NULL DEFAULT 'Bamenda',
  region       VARCHAR(80)  NOT NULL DEFAULT 'North-West',
  address      VARCHAR(200) NULL,
  lat          DECIMAL(10,7) NULL,
  lng          DECIMAL(10,7) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_buildings_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_buildings_quarter (quarter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS floors (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id INT UNSIGNED NOT NULL,
  label       VARCHAR(40) NOT NULL,             -- 'Ground Floor', 'Block A', '1st Floor'...
  kind        ENUM('floor','block') NOT NULL DEFAULT 'floor',
  CONSTRAINT fk_floors_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS units (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id    INT UNSIGNED NOT NULL,
  floor_id       INT UNSIGNED NULL,
  code           VARCHAR(30) NOT NULL,
  unit_type      ENUM('apartment','duplex','studio','shop','office','store','duplex_flat','self_contain') NOT NULL DEFAULT 'apartment',
  sqm            DECIMAL(7,2) NULL,
  bedrooms       TINYINT UNSIGNED NULL,
  bathrooms      TINYINT UNSIGNED NULL,
  market_rent_xaf BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status         ENUM('vacant','leased','under_maintenance','reserved','out_of_service') NOT NULL DEFAULT 'vacant',
  amenities      TEXT NULL,                     -- JSON list
  condition_report TEXT NULL,                   -- initial condition report
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_units_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_units_floor FOREIGN KEY (floor_id) REFERENCES floors(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY uq_unit_code_per_building (building_id, code),
  INDEX idx_units_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Module 2 · Tenant & User Management (IAM + RBAC)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL,
  phone         VARCHAR(30)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','property_manager','accountant','technician','owner','tenant') NOT NULL DEFAULT 'tenant',
  locale        ENUM('en','fr') NOT NULL DEFAULT 'en',
  active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenants (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NULL,             -- optional portal login
  kind           ENUM('individual','corporate') NOT NULL DEFAULT 'individual',
  name           VARCHAR(160) NOT NULL,
  email          VARCHAR(160) NULL,
  phone          VARCHAR(30)  NULL,
  alt_phone      VARCHAR(30)  NULL,
  emergency_name  VARCHAR(120) NULL,
  emergency_phone VARCHAR(30)  NULL,
  id_type        ENUM('national_id','passport','certificate_incorporation','residence_permit') NULL,
  id_number      VARCHAR(60) NULL,
  notes          TEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tenants_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_tenants_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owners (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(160) NOT NULL,
  phone         VARCHAR(30) NULL,
  email         VARCHAR(160) NULL,
  mgmt_fee_pct  DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  payout_account VARCHAR(120) NULL,             -- bank / MoMo account for disbursements
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE portfolios
  ADD CONSTRAINT fk_portfolios_owner FOREIGN KEY (owner_id) REFERENCES owners(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
-- Module 3 · Lease & Contract Lifecycle (Common Law factored)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leases (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(20) NOT NULL UNIQUE,   -- LSE-2026-001
  unit_id        INT UNSIGNED NOT NULL,
  tenant_id      INT UNSIGNED NOT NULL,
  start_date     DATE NOT NULL,
  end_date       DATE NOT NULL,
  rent_xaf       BIGINT UNSIGNED NOT NULL,
  billing_cycle  ENUM('monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
  rent_type      ENUM('fixed','dynamic') NOT NULL DEFAULT 'fixed',
  escalation_pct DECIMAL(5,2) NULL,             -- % increase at each escalation
  escalation_interval_months SMALLINT UNSIGNED NULL,
  grace_days     SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  penalty_pct    DECIMAL(5,2)  NOT NULL DEFAULT 5.00,
  deposit_xaf    BIGINT UNSIGNED NOT NULL DEFAULT 0,
  deposit_paid   TINYINT(1) NOT NULL DEFAULT 0,
  -- Common Law factoring (Bamenda, North-West Region)
  legal_system   ENUM('common_law','civil_law') NOT NULL DEFAULT 'common_law',
  jurisdiction   VARCHAR(160) NOT NULL DEFAULT 'Bamenda, North-West Region, Cameroon',
  notice_to_quit_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  status         ENUM('draft','active','expired','terminated','in_recovery') NOT NULL DEFAULT 'draft',
  signed_at      DATETIME NULL,
  witness_1      VARCHAR(120) NULL,
  witness_2      VARCHAR(120) NULL,
  stamp_duty_paid TINYINT(1) NOT NULL DEFAULT 0,
  notes          TEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_leases_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_leases_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_leases_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lease_events (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lease_id   INT UNSIGNED NOT NULL,
  event_type VARCHAR(40) NOT NULL,   -- created/activated/renewal_notice/escalation_applied/notice_to_quit/court_filing/terminated/deposit_refunded...
  detail     TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_lease FOREIGN KEY (lease_id) REFERENCES leases(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_events_lease (lease_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lease_documents (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lease_id  INT UNSIGNED NOT NULL,
  doc_kind  ENUM('agreement','notice_to_quit','move_in_checklist','move_out_report','court_document','other') NOT NULL,
  title     VARCHAR(160) NOT NULL,
  file_path VARCHAR(255) NULL,      -- future: PDF in storage; generated docs keep inline content
  content   LONGTEXT NULL,          -- generated HTML (agreement / notice)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_docs_lease FOREIGN KEY (lease_id) REFERENCES leases(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recovery of premises under Common Law: notice -> expiry -> court -> enforcement.
-- Self-help eviction is never an option; every stage is tracked here.
CREATE TABLE IF NOT EXISTS recovery_cases (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lease_id   INT UNSIGNED NOT NULL,
  stage      ENUM('demand_letter','notice_to_quit_issued','notice_expired','court_filing','judgment','enforcement','closed') NOT NULL DEFAULT 'demand_letter',
  opened_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes      TEXT NULL,
  CONSTRAINT fk_recovery_lease FOREIGN KEY (lease_id) REFERENCES leases(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_recovery_stage (stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Module 4 · Accounting & Financial Management
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gl_accounts (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code      VARCHAR(12) NOT NULL UNIQUE,        -- SYSCOHADA-shaped
  name      VARCHAR(120) NOT NULL,
  acct_type ENUM('asset','liability','equity','income','expense') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  number       VARCHAR(24) NOT NULL UNIQUE,     -- INV-2026-0001
  lease_id     INT UNSIGNED NULL,
  tenant_id    INT UNSIGNED NOT NULL,
  kind         ENUM('rent','utility','late_fee','amenity','other') NOT NULL DEFAULT 'rent',
  issue_date   DATE NOT NULL,
  due_date     DATE NOT NULL,
  period_start DATE NULL,
  period_end   DATE NULL,
  amount_xaf   BIGINT UNSIGNED NOT NULL,
  paid_xaf     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status       ENUM('draft','unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  notes        VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_lease FOREIGN KEY (lease_id) REFERENCES leases(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_invoices_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_invoices_status (status),
  INDEX idx_invoices_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_lines (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  gl_code    VARCHAR(12) NOT NULL,
  description VARCHAR(200) NOT NULL,
  amount_xaf BIGINT UNSIGNED NOT NULL,
  CONSTRAINT fk_lines_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id  INT UNSIGNED NULL,
  lease_id    INT UNSIGNED NULL,
  amount_xaf  BIGINT UNSIGNED NOT NULL,
  channel     ENUM('mtn_momo','orange_money','bank_transfer','card','cash','cheque') NOT NULL,
  reference   VARCHAR(80) NULL,                 -- MoMo/OM transaction ID
  paid_at     DATETIME NOT NULL,
  received_by INT UNSIGNED NULL,
  status      ENUM('pending','confirmed','failed') NOT NULL DEFAULT 'confirmed',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_payments_lease FOREIGN KEY (lease_id) REFERENCES leases(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_payments_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Double-entry ledger: every invoice/payment posts balanced journal lines.
CREATE TABLE IF NOT EXISTS journal_entries (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL,
  memo       VARCHAR(200) NOT NULL,
  ref_type   VARCHAR(30) NULL,                  -- invoice|payment|late_fee|payout
  ref_id     INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_lines (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id   INT UNSIGNED NOT NULL,
  gl_code    VARCHAR(12) NOT NULL,
  debit_xaf  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  credit_xaf BIGINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_jlines_entry FOREIGN KEY (entry_id) REFERENCES journal_entries(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS late_fee_rules (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  grace_days  SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  penalty_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  flat_xaf    BIGINT UNSIGNED NOT NULL DEFAULT 0,
  active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owner_payouts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id     INT UNSIGNED NOT NULL,
  period_start DATE NOT NULL,
  period_end   DATE NOT NULL,
  gross_xaf    BIGINT UNSIGNED NOT NULL DEFAULT 0,
  fee_xaf      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  net_xaf      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status       ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at      DATETIME NULL,
  CONSTRAINT fk_payouts_owner FOREIGN KEY (owner_id) REFERENCES owners(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Module 5 · Maintenance, FM & Work Orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vendors (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(160) NOT NULL,
  service_type VARCHAR(120) NOT NULL,           -- plumbing, HVAC, generator...
  phone        VARCHAR(30) NULL,
  email        VARCHAR(160) NULL,
  rating       DECIMAL(2,1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_tickets (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id     INT UNSIGNED NULL,
  building_id INT UNSIGNED NULL,
  tenant_id   INT UNSIGNED NULL,
  title       VARCHAR(160) NOT NULL,
  description TEXT NULL,
  severity    ENUM('urgent','high','low') NOT NULL DEFAULT 'high',
  status      ENUM('open','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  photos      TEXT NULL,                        -- JSON list of attachment paths
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tickets_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tickets_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tickets_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_tickets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_orders (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id     INT UNSIGNED NOT NULL,
  assignee_kind ENUM('internal','vendor') NOT NULL,
  user_id       INT UNSIGNED NULL,              -- internal technician
  vendor_id     INT UNSIGNED NULL,
  sla_hours     SMALLINT UNSIGNED NOT NULL DEFAULT 48,
  status        ENUM('assigned','in_progress','done','verified') NOT NULL DEFAULT 'assigned',
  labor_xaf     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  parts_xaf     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  opened_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at     DATETIME NULL,
  CONSTRAINT fk_wo_ticket FOREIGN KEY (ticket_id) REFERENCES maintenance_tickets(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_wo_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_wo_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pm_schedules (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id INT UNSIGNED NULL,
  unit_id     INT UNSIGNED NULL,
  title       VARCHAR(160) NOT NULL,            -- 'Generator servicing', 'Fire extinguisher audit'
  frequency   ENUM('weekly','monthly','quarterly','semiannual','annual') NOT NULL DEFAULT 'monthly',
  last_done   DATE NULL,
  next_due    DATE NOT NULL,
  vendor_id   INT UNSIGNED NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_pm_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pm_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Module 6 · Utilities & Smart Building
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meters (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id INT UNSIGNED NULL,
  unit_id     INT UNSIGNED NULL,
  utility     ENUM('water','electricity','gas','generator_fuel') NOT NULL,
  serial      VARCHAR(60) NOT NULL,
  is_iot      TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_meters_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_meters_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meter_readings (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meter_id  INT UNSIGNED NOT NULL,
  read_date DATE NOT NULL,
  value     BIGINT UNSIGNED NOT NULL,
  source    ENUM('manual','iot') NOT NULL DEFAULT 'manual',
  CONSTRAINT fk_readings_meter FOREIGN KEY (meter_id) REFERENCES meters(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_readings_meter_date (meter_id, read_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utility_bills (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id  INT UNSIGNED NOT NULL,
  utility      ENUM('water','electricity','gas','generator_fuel') NOT NULL,
  period_start DATE NOT NULL,
  period_end   DATE NOT NULL,
  total_xaf    BIGINT UNSIGNED NOT NULL,
  split_method ENUM('occupancy','sqm') NOT NULL DEFAULT 'occupancy',
  status       ENUM('draft','allocated','invoiced') NOT NULL DEFAULT 'draft',
  CONSTRAINT fk_ubills_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utility_allocations (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_id   INT UNSIGNED NOT NULL,
  unit_id   INT UNSIGNED NOT NULL,
  share_xaf BIGINT UNSIGNED NOT NULL,
  detail    VARCHAR(160) NULL,                  -- e.g. '3 occupants / 10 total'
  CONSTRAINT fk_ualloc_bill FOREIGN KEY (bill_id) REFERENCES utility_bills(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ualloc_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Module 7 · Security, Access & Visitors
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_passes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id      INT UNSIGNED NULL,
  visitor_name VARCHAR(120) NOT NULL,
  visitor_phone VARCHAR(30) NULL,
  qr_token     VARCHAR(32) NOT NULL UNIQUE,
  valid_on     DATE NOT NULL,
  status       ENUM('pending','approved','used','expired') NOT NULL DEFAULT 'approved',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitors_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenities (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building_id    INT UNSIGNED NOT NULL,
  name           VARCHAR(120) NOT NULL,
  kind           ENUM('meeting_room','gym','clubhouse','parking','other') NOT NULL,
  hourly_rate_xaf BIGINT UNSIGNED NOT NULL DEFAULT 0,
  active         TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_amenities_building FOREIGN KEY (building_id) REFERENCES buildings(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenity_bookings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  amenity_id INT UNSIGNED NOT NULL,
  tenant_id  INT UNSIGNED NOT NULL,
  start_at   DATETIME NOT NULL,
  end_at     DATETIME NOT NULL,
  total_xaf  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status     ENUM('reserved','paid','cancelled') NOT NULL DEFAULT 'reserved',
  CONSTRAINT fk_bookings_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
