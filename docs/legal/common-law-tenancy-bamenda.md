# Common Law Factoring — Tenancies in Bamenda, North-West Region

> **This document is engineering guidance, not legal advice.** It records why
> the system is built the way it is, so counsel can review the assumptions.
> Every number below that touches a legal deadline is **configurable** and must
> be settled with a Cameroonian lawyer before production use.

## 1 · Why Common Law matters here

Cameroon is a **bijural** state: the eight francophone regions operate under
Civil Law (French heritage), while the North-West and South-West regions —
including **Bamenda** — operate under **Common Law** (Southern Cameroons
heritage). A property management system written for Bamenda must therefore
treat leases, notices and eviction as Common Law instruments first.

Nationwide statutes still apply across both systems (notably the 1974
ordinance fixing terms and conditions of leases); regional practice shapes
procedure and documentation style.

## 2 · What the system encodes

| Principle | Where it lives |
|---|---|
| Lease records its governing system & jurisdiction | `leases.legal_system`, `leases.jurisdiction` (default Common Law, Bamenda NW) |
| Tenancy agreement generated in Common Law style — parties, term, quiet enjoyment, deposit, forfeiture, witnessed execution | `modules/Leases/Views/documents/agreement.php` |
| Termination requires a properly served **notice to quit** with contractual notice period | `leases.notice_to_quit_days` (default 30; commercial lets often 90) + generator `notice_to_quit.php` |
| Possession is recovered **only through the court**, never self-help | `recovery_cases` pipeline: demand letter → notice → expiry → court filing → judgment → enforcement |
| Full audit trail of every lifecycle action | `lease_events` (who, what, when) |
| Documents kept as first-class records | `lease_documents` (agreement, notices, court documents) |
| Fiscal formalities (stamping/registration) tracked, not assumed | `leases.stamp_duty_paid`, witness fields |

## 3 · Design decisions worth reviewing with counsel

1. **Notice periods.** Defaults (30 days residential / 90 days commercial per
   lease term) follow common Bamenda practice, but the controlling instrument
   is the lease + applicable ordinance. The field is per-lease for a reason.
2. **Service of notices.** The generated notice states personal service,
   registered post or bailiff service as options. Which modes are provable in
   the Magistrate's Court should be confirmed; the system logs service events
   but does not yet capture proof-of-service documents.
3. **Distress for rent.** A Common Law remedy in the Anglophone regions; not
   yet modelled. Candidate: a `distress` stage or separate case type.
4. **Deposit handling.** Modelled as refundable against dilapidations beyond
   fair wear and tear (30-day refund window in the generated agreement).
   Statutory caps or interest treatment, if any, need confirmation.
5. **Stamp duty / registration.** The agreement carries a note that it must be
   stamped/registered per fiscal legislation; amounts and deadlines are not
   encoded.
6. **Court names & jurisdiction thresholds.** The notice refers generically to
   "the competent court in Bamenda" (Magistrate's Court / High Court depending
   on the matter). The exact forum and current procedure should be verified.

## 4 · Civil Law co-existence

For portfolios in francophone regions, `legal_system` switches to `civil_law`
and the jurisdiction field changes; the recovery pipeline remains court-based
(which is also correct under Civil Law procedure) while document wording and
notice periods follow the lease. Harmonisation of the two document templates
is a roadmap item.
