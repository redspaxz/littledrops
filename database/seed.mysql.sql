-- ============================================================
--  PBMS seed data — Bamenda, North-West Region, Cameroon
--  Demo staff accounts all use password: Demo@2026
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Owners / landlords ------------------------------------------------
INSERT INTO owners (id, name, phone, email, mgmt_fee_pct, payout_account) VALUES
 (1, 'Manka''o Family Estate', '+237 677 401 218', 'mankao.estate@example.cm', 10.00, 'ACC 0124 7896 3210 – AFC Bank Bamenda'),
 (2, 'Ndzah Holdings Ltd',     '+237 699 210 447', 'accounts@ndzahholdings.cm', 12.00, 'ACC 0341 5567 8890 – NFC Bamenda');

-- Staff users (RBAC per PBMS spec) ----------------------------------
-- password for all demo accounts: Demo@2026
INSERT INTO users (id, full_name, email, phone, password_hash, role, locale, active) VALUES
 (1, 'Ngwa Roland',      'admin@littledrops.cm',    '+237 671 000 001', '$2y$10$Z9aObnI.mg7f3Oeol3ttIuevG1GKLfARkhucTjGLOEmta7TJ2Guim', 'admin',            'en', 1),
 (2, 'Fru Brenda',       'manager@littledrops.cm',  '+237 671 000 002', '$2y$10$Z9aObnI.mg7f3Oeol3ttIuevG1GKLfARkhucTjGLOEmta7TJ2Guim', 'property_manager', 'en', 1),
 (3, 'Mbah Cyril',       'accounts@littledrops.cm', '+237 671 000 003', '$2y$10$Z9aObnI.mg7f3Oeol3ttIuevG1GKLfARkhucTjGLOEmta7TJ2Guim', 'accountant',       'en', 1),
 (4, 'Ateh Blaise',      'tech@littledrops.cm',     '+237 671 000 004', '$2y$10$Z9aObnI.mg7f3Oeol3ttIuevG1GKLfARkhucTjGLOEmta7TJ2Guim', 'technician',       'en', 1);

-- Portfolios & buildings (Bamenda quarters) -------------------------
INSERT INTO portfolios (id, name, owner_id, notes) VALUES
 (1, 'Bamenda Residential',  1, 'Residential holdings across Bamenda'),
 (2, 'Mankon Commercial',    2, 'Retail and office stock');

INSERT INTO buildings (id, portfolio_id, name, quarter, city, region, address) VALUES
 (1, 1, 'Nkwen Duplex Court',    'Nkwen',      'Bamenda', 'North-West', 'Mile 4 Nkwen, behind Nkwen Baptist Church'),
 (2, 1, 'Azire Garden Terraces','Azire',      'Bamenda', 'North-West', 'Azire New Layout, off Bamenda-Bambui road'),
 (3, 2, 'Mankon Commercial Plaza','Mankon',   'Bamenda', 'North-West', 'Commercial Avenue, Mankon'),
 (4, 1, 'Old Town Residences',  'Old Town',   'Bamenda', 'North-West', 'Old Town, near City Chemist Roundabout'),
 (5, 1, 'Up Station Flats',     'Up Station', 'Bamenda', 'North-West', 'Up Station, Ministerial Quarter');

INSERT INTO floors (id, building_id, label, kind) VALUES
 (1, 3, 'Ground Floor', 'floor'),
 (2, 3, 'First Floor',  'floor');

-- Units --------------------------------------------------------------
INSERT INTO units (id, building_id, floor_id, code, unit_type, sqm, bedrooms, bathrooms, market_rent_xaf, status, amenities) VALUES
 (1,  1, NULL, '101', 'duplex',         150.00, 3, 3, 145000, 'leased',            '["borehole water","fenced compound","generator backup"]'),
 (2,  1, NULL, '102', 'duplex',         150.00, 3, 3, 145000, 'vacant',            '["borehole water","fenced compound","generator backup"]'),
 (3,  1, NULL, '103', 'self_contain',    28.00, 1, 1,  30000, 'leased',            '["water","electricity meter"]'),
 (4,  2, NULL, 'A1',  'apartment',       72.00, 2, 1,  75000, 'leased',            '["balcony","water tank"]'),
 (5,  2, NULL, 'A2',  'apartment',       72.00, 2, 1,  75000, 'vacant',            '["balcony","water tank"]'),
 (6,  2, NULL, 'A3',  'studio',          35.00, 1, 1,  35000, 'under_maintenance', '["water"]'),
 (7,  3, 1,    'S1',  'shop',            24.00, NULL, 1, 60000, 'leased',          '["roller shutter","generator backup"]'),
 (8,  3, 1,    'S2',  'shop',            24.00, NULL, 1, 60000, 'vacant',          '["roller shutter","generator backup"]'),
 (9,  3, 2,    'O1',  'office',          55.00, NULL, 2, 120000, 'leased',         '["AC","fibre ready","generator backup"]'),
 (10, 3, 2,    'O2',  'office',          55.00, NULL, 2, 120000, 'vacant',         '["AC","fibre ready","generator backup"]'),
 (11, 4, NULL, '201', 'apartment',       65.00, 2, 1,  70000, 'leased',            '["balcony"]'),
 (12, 4, NULL, '202', 'apartment',       65.00, 2, 1,  70000, 'reserved',          '["balcony"]'),
 (13, 5, NULL, 'F1',  'apartment',       48.00, 1, 1,  45000, 'vacant',            '[]'),
 (14, 5, NULL, 'F2',  'apartment',       48.00, 1, 1,  45000, 'out_of_service',    '[]'),
 (15, 2, NULL, 'A4',  'self_contain',    26.00, 1, 1,  28000, 'vacant',            '["water"]');

-- Tenants ------------------------------------------------------------
INSERT INTO tenants (id, user_id, kind, name, email, phone, alt_phone, emergency_name, emergency_phone, id_type, id_number, notes) VALUES
 (1, NULL, 'individual', 'Ngwa Emmanuel',        'e.ngwa@example.cm', '+237 675 112 233', NULL, 'Ngwa Bernard', '+237 677 808 101', 'national_id', '1198456321', NULL),
 (2, NULL, 'individual', 'Fru Delphine',         'd.fru@example.cm',  '+237 653 442 090', NULL, 'Fru Achuo',    '+237 699 341 220', 'national_id', '1187233098', NULL),
 (3, NULL, 'individual', 'Mbah Comfort',         'c.mbah@example.cm', '+237 671 903 512', NULL, 'Mbah Terence', '+237 678 220 415', 'passport',    'P0114329', NULL),
 (4, NULL, 'individual', 'Che Christian',        'c.che@example.cm',  '+237 690 114 726', NULL, 'Che Beltha',   '+237 655 801 337', 'national_id', '1204417896', NULL),
 (5, NULL, 'corporate',  'Bamenda Tech Hub Ltd', 'info@bthub.cm',     '+237 654 220 118', NULL, 'Ndi Weber (MD)','+237 677 445 990', 'certificate_incorporation', 'NW/ABA/2019/1147', 'Occupies office O1 at Mankon Commercial Plaza'),
 (6, NULL, 'individual', 'Tanwi Eric',           'e.tanwi@example.cm','+237 674 331 802', NULL, 'Tanwi Vera',   '+237 691 002 144', 'national_id', '1179002345', NULL),
 (7, NULL, 'individual', 'Ayafor Larissa',       'l.ayafor@example.cm','+237 658 771 349', NULL, 'Ayafor Primus','+237 672 118 654', 'national_id', '1166882201', 'Previous tenant of Old Town 201 — moved out Dec 2025');

-- Leases (Common Law defaults for Bamenda) ---------------------------
INSERT INTO leases (id, code, unit_id, tenant_id, start_date, end_date, rent_xaf, billing_cycle, rent_type, escalation_pct, escalation_interval_months, grace_days, penalty_pct, deposit_xaf, deposit_paid, legal_system, jurisdiction, notice_to_quit_days, status, signed_at, witness_1, witness_2, stamp_duty_paid, notes) VALUES
 (1, 'LSE-2026-001', 1, 1, '2026-01-01', '2026-12-31', 145000, 'monthly', 'fixed', NULL, NULL, 7, 5.00, 290000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'active',     '2025-12-20 10:00:00', 'Barrister N. Agho', 'Ngwa Bernard', 1, NULL),
 (2, 'LSE-2026-002', 3, 2, '2026-03-01', '2027-02-28',  30000, 'monthly', 'fixed', NULL, NULL, 7, 5.00,  60000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'active',     '2026-02-18 09:30:00', 'Fru Achuo', 'Barrister N. Agho', 1, NULL),
 (3, 'LSE-2026-003', 4, 3, '2026-02-01', '2027-01-31',  75000, 'monthly', 'dynamic', 10.00, 12, 7, 5.00, 150000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'active',     '2026-01-22 14:15:00', 'Mbah Terence', 'Fru Achuo', 1, 'Escalation clause: +10% at renewal each 12 months'),
 (4, 'LSE-2026-004', 7, 5, '2025-09-01', '2026-08-31',  60000, 'monthly', 'fixed', NULL, NULL, 7, 5.00, 120000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 90, 'active',     '2025-08-25 11:00:00', 'Ndi Weber', 'Barrister N. Agho', 1, 'Commercial let — 90-day notice clause; renewal due'),
 (5, 'LSE-2026-005', 9, 4, '2025-10-01', '2026-09-30', 120000, 'monthly', 'fixed', NULL, NULL, 7, 5.00, 240000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'in_recovery','2025-09-15 15:45:00', 'Che Beltha', 'Barrister N. Agho', 1, 'Two months in arrears — notice to quit served 01 Aug 2026'),
 (6, 'LSE-2026-006', 11, 6, '2026-05-01', '2027-04-30', 70000, 'monthly', 'fixed', NULL, NULL, 7, 5.00, 140000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'active',     '2026-04-18 16:20:00', 'Tanwi Vera', 'Ngwa Bernard', 1, NULL),
 (7, 'LSE-2025-014', 11, 7, '2025-01-01', '2025-12-31', 65000, 'monthly', 'fixed', NULL, NULL, 7, 5.00, 130000, 1, 'common_law', 'Bamenda, North-West Region, Cameroon', 30, 'expired',    '2024-12-15 10:30:00', 'Ayafor Primus', 'Ngwa Bernard', 1, 'Ended amicably; deposit refunded in full');

INSERT INTO lease_events (id, lease_id, event_type, detail, created_by, created_at) VALUES
 (1, 1, 'created',   'Lease drafted from wizard', 2, '2025-12-18 09:00:00'),
 (2, 1, 'activated', 'Agreement signed and stamped; deposit 290,000 FCFA received', 2, '2025-12-20 10:05:00'),
 (3, 2, 'activated', 'Agreement signed; deposit received', 2, '2026-02-18 09:35:00'),
 (4, 3, 'activated', 'Agreement signed; deposit received', 2, '2026-01-22 14:20:00'),
 (5, 4, 'renewal_notice', 'Expiring 31 Aug 2026 — renewal offer sent to tenant (90-day notice clause)', 2, '2026-08-01 08:30:00'),
 (6, 5, 'activated', 'Agreement signed; deposit 240,000 FCFA received', 2, '2025-09-15 15:50:00'),
 (7, 5, 'demand_letter', 'Formal demand letter for 2 months arrears (240,000 FCFA) delivered by bailiff', 3, '2026-07-20 11:00:00'),
 (8, 5, 'notice_to_quit', 'Notice to quit served — expires 31 Aug 2026; possession proceedings if unpaid', 3, '2026-08-01 09:15:00'),
 (9, 6, 'activated', 'Agreement signed; deposit received', 2, '2026-04-18 16:25:00'),
 (10, 7, 'terminated', 'Tenant moved out; move-out inspection clean, deposit refunded 05 Jan 2026', 2, '2025-12-30 17:00:00');

-- Recovery of premises (Common Law pipeline) --------------------------
INSERT INTO recovery_cases (id, lease_id, stage, opened_at, updated_at, notes) VALUES
 (1, 5, 'notice_to_quit_issued', '2026-07-20 11:05:00', '2026-08-01 09:20:00', 'Demand letter 20 Jul 2026; notice to quit served 01 Aug 2026, expires 31 Aug 2026. If unpaid, file for recovery of premises at the Bamenda Magistrate''s Court — self-help eviction is not an option under Common Law.');

-- Chart of accounts (SYSCOHADA-shaped) --------------------------------
INSERT INTO gl_accounts (id, code, name, acct_type) VALUES
 (1,  '411',  'Tenants — receivables',        'asset'),
 (2,  '521',  'Banks',                        'asset'),
 (3,  '571',  'Cash on hand',                 'asset'),
 (4,  '706',  'Rental income',                'income'),
 (5,  '7068', 'Utility recharges',            'income'),
 (6,  '7588', 'Penalties and late fees',      'income'),
 (7,  '6051', 'Electricity expense',          'expense'),
 (8,  '6052', 'Water expense',                'expense'),
 (9,  '6226', 'Repairs and maintenance',      'expense'),
 (10, '6281', 'Generator fuel expense',       'expense');

INSERT INTO late_fee_rules (id, name, grace_days, penalty_pct, flat_xaf, active) VALUES
 (1, 'Standard — 5% after 7 days', 7, 5.00, 0, 1);

-- Invoices (Aug–Sep 2026) ---------------------------------------------
INSERT INTO invoices (id, number, lease_id, tenant_id, kind, issue_date, due_date, period_start, period_end, amount_xaf, paid_xaf, status, notes) VALUES
 (1,  'INV-2026-0101', 1, 1, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31', 145000, 145000, 'paid',    NULL),
 (2,  'INV-2026-0102', 2, 2, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31',  30000,  30000, 'paid',    NULL),
 (3,  'INV-2026-0103', 3, 3, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31',  75000,  75000, 'paid',    NULL),
 (4,  'INV-2026-0104', 4, 5, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31',  60000,  60000, 'paid',    NULL),
 (5,  'INV-2026-0105', 5, 4, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31', 120000,      0, 'unpaid',  'Overdue — recovery case open'),
 (6,  'INV-2026-0106', 6, 6, 'rent',     '2026-08-01', '2026-08-05', '2026-08-01', '2026-08-31',  70000,  35000, 'partial', 'Half paid; balance promised 10 Sep'),
 (7,  'INV-2026-0121', 1, 1, 'rent',     '2026-09-01', '2026-09-05', '2026-09-01', '2026-09-30', 145000, 145000, 'paid',    NULL),
 (8,  'INV-2026-0122', 2, 2, 'rent',     '2026-09-01', '2026-09-05', '2026-09-01', '2026-09-30',  30000,      0, 'unpaid',  NULL),
 (9,  'INV-2026-0123', 3, 3, 'rent',     '2026-09-01', '2026-09-05', '2026-09-01', '2026-09-30',  75000,  75000, 'paid',    NULL),
 (10, 'INV-2026-0124', 5, 4, 'rent',     '2026-09-01', '2026-09-05', '2026-09-01', '2026-09-30', 120000,      0, 'unpaid',  'Recovery case open'),
 (11, 'INV-2026-0125', 4, 5, 'rent',     '2026-09-01', '2026-09-05', '2026-09-01', '2026-09-30',  60000,      0, 'unpaid',  NULL),
 (12, 'INV-2026-0130', 5, 4, 'late_fee', '2026-08-08', '2026-08-15', '2026-08-01', '2026-08-31',   6000,      0, 'unpaid',  '5% late fee on Aug arrears');

INSERT INTO invoice_lines (invoice_id, gl_code, description, amount_xaf) VALUES
 (1,  '706', 'Rent August 2026 — Unit 101 Nkwen Duplex Court', 145000),
 (2,  '706', 'Rent August 2026 — Unit 103', 30000),
 (3,  '706', 'Rent August 2026 — Unit A1', 75000),
 (4,  '706', 'Rent August 2026 — Shop S1', 60000),
 (5,  '706', 'Rent August 2026 — Office O1', 120000),
 (6,  '706', 'Rent August 2026 — Unit 201', 70000),
 (7,  '706', 'Rent September 2026 — Unit 101', 145000),
 (8,  '706', 'Rent September 2026 — Unit 103', 30000),
 (9,  '706', 'Rent September 2026 — Unit A1', 75000),
 (10, '706', 'Rent September 2026 — Office O1', 120000),
 (11, '706', 'Rent September 2026 — Shop S1', 60000),
 (12, '7588','Late fee — 5% of August rent', 6000);

-- Payments (Mobile Money first) ----------------------------------------
INSERT INTO payments (id, invoice_id, lease_id, amount_xaf, channel, reference, paid_at, received_by, status) VALUES
 (1, 1, 1, 145000, 'mtn_momo',      'MP260804.1234.A5678', '2026-08-04 18:42:00', 3, 'confirmed'),
 (2, 2, 2,  30000, 'orange_money', 'OM260803.8871.C2210', '2026-08-03 12:10:00', 3, 'confirmed'),
 (3, 3, 3,  75000, 'bank_transfer','AFC-884201-0308',     '2026-08-05 09:05:00', 3, 'confirmed'),
 (4, 4, 4,  60000, 'cash',          NULL,                  '2026-08-05 10:20:00', 3, 'confirmed'),
 (5, 6, 6,  35000, 'mtn_momo',      'MP260806.5512.B9034', '2026-08-06 19:55:00', 3, 'confirmed'),
 (6, 7, 1, 145000, 'mtn_momo',      'MP260903.7741.D1180', '2026-09-03 20:12:00', 3, 'confirmed'),
 (7, 9, 3,  75000, 'orange_money', 'OM260905.2210.F7745', '2026-09-05 08:47:00', 3, 'confirmed');

-- Double-entry examples -------------------------------------------------
INSERT INTO journal_entries (id, entry_date, memo, ref_type, ref_id) VALUES
 (1, '2026-08-04', 'INV-2026-0101 rent billed to Ngwa Emmanuel', 'invoice', 1),
 (2, '2026-08-04', 'MTN MoMo receipt MP260804.1234.A5678',       'payment', 1),
 (3, '2026-09-03', 'MTN MoMo receipt MP260903.7741.D1180',       'payment', 6);

INSERT INTO journal_lines (entry_id, gl_code, debit_xaf, credit_xaf) VALUES
 (1, '411', 145000, 0),
 (1, '706', 0, 145000),
 (2, '521', 145000, 0),
 (2, '411', 0, 145000),
 (3, '521', 145000, 0),
 (3, '411', 0, 145000);

-- Owner payout -----------------------------------------------------------
INSERT INTO owner_payouts (id, owner_id, period_start, period_end, gross_xaf, fee_xaf, net_xaf, status, paid_at) VALUES
 (1, 1, '2026-08-01', '2026-08-31', 320000, 32000, 288000, 'pending', NULL);

-- Maintenance ------------------------------------------------------------
INSERT INTO vendors (id, name, service_type, phone, email, rating) VALUES
 (1, 'Momo Plumbing & Electrical Works', 'Plumbing / Electrical', '+237 677 512 340', 'momoplumbs@example.cm', 4.5),
 (2, 'Savannah Power Systems',           'Generators / HVAC',     '+237 694 118 275', 'service@savannahpower.cm', 4.2);

INSERT INTO maintenance_tickets (id, unit_id, building_id, tenant_id, title, description, severity, status, photos) VALUES
 (1, NULL, 3, 5,    'Generator failed at plaza',      'Perkins genset tripped during load transfer; shops without backup power since morning.', 'urgent', 'open',      NULL),
 (2, 4,    NULL, 3, 'Water leak under kitchen sink',  'Steady drip soaking the cabinet floor.', 'high',   'assigned',  NULL),
 (3, NULL, 1, NULL, 'Repaint stairwell railing',      'Railing paint peeling in Block 101/102 stairwell.', 'low', 'open',    NULL),
 (4, 11,   NULL, 6, 'Faulty entrance door lock',      'Cylinder jams; needs replacement.', 'high', 'resolved',  NULL);

INSERT INTO work_orders (id, ticket_id, assignee_kind, user_id, vendor_id, sla_hours, status, labor_xaf, parts_xaf, opened_at, closed_at) VALUES
 (1, 2, 'internal', 4, NULL, 24, 'in_progress', 15000, 8000, '2026-09-07 08:30:00', NULL),
 (2, 4, 'internal', 4, NULL, 48, 'verified',    10000, 22000, '2026-08-28 09:00:00', '2026-08-29 15:30:00');

INSERT INTO pm_schedules (id, building_id, unit_id, title, frequency, last_done, next_due, vendor_id, active) VALUES
 (1, 3, NULL, 'Generator 250-hour service',  'monthly',   '2026-08-15', '2026-09-15', 2, 1),
 (2, 1, NULL, 'Fire extinguisher audit',     'quarterly', '2026-07-01', '2026-10-01', NULL, 1),
 (3, 3, NULL, 'HVAC filter cleaning (offices)','quarterly','2026-06-20', '2026-09-20', 2, 1);

-- Utilities ---------------------------------------------------------------
INSERT INTO meters (id, building_id, unit_id, utility, serial, is_iot) VALUES
 (1, NULL, 1, 'water',          'W-NDC-101', 0),
 (2, NULL, 1, 'electricity',    'E-NDC-101', 0),
 (3, 3,    NULL, 'generator_fuel', 'GPS-PLAZA-01', 1),
 (4, 4,    NULL, 'water',       'W-OT-BULK', 0);

INSERT INTO meter_readings (meter_id, read_date, value, source) VALUES
 (1, '2026-07-31', 820,  'manual'),
 (1, '2026-08-31', 842,  'manual'),
 (2, '2026-07-31', 4120, 'manual'),
 (2, '2026-08-31', 4367, 'manual'),
 (3, '2026-07-31', 520,  'iot'),
 (3, '2026-08-31', 340,  'iot'),
 (4, '2026-07-31', 1180, 'manual'),
 (4, '2026-08-31', 1265, 'manual');

INSERT INTO utility_bills (id, building_id, utility, period_start, period_end, total_xaf, split_method, status) VALUES
 (1, 3, 'generator_fuel', '2026-08-01', '2026-08-31', 180000, 'occupancy', 'allocated');

INSERT INTO utility_allocations (bill_id, unit_id, share_xaf, detail) VALUES
 (1, 7, 60000, 'Shop S1 — 1 of 3 occupied units'),
 (1, 8, 60000, 'Shop S2 — vacant, absorbed by landlord'),
 (1, 9, 60000, 'Office O1 — 1 of 3 occupied units');

-- Security, access & amenities ----------------------------------------------
INSERT INTO visitor_passes (id, unit_id, visitor_name, visitor_phone, qr_token, valid_on, status) VALUES
 (1, 1,  'Berinyuy Solange', '+237 678 004 331', 'a91f2c7d4e5b6a8012345678', '2026-09-08', 'approved'),
 (2, 7,  'Ndifor Peter',     '+237 655 218 909', 'b72e3d8c9a1f5b6023456789', '2026-09-07', 'used');

INSERT INTO amenities (id, building_id, name, kind, hourly_rate_xaf, active) VALUES
 (1, 3, 'Conference Room — 2nd Floor', 'meeting_room', 15000, 1),
 (2, 3, 'Covered Parking Bay',         'parking',       2000, 1);

INSERT INTO amenity_bookings (id, amenity_id, tenant_id, start_at, end_at, total_xaf, status) VALUES
 (1, 1, 5, '2026-09-10 09:00:00', '2026-09-10 12:00:00', 45000, 'reserved');

SET FOREIGN_KEY_CHECKS = 1;
