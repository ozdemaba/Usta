# Phase 6 — Procurement Foundation

## Objective
Establish the canonical procurement data model and source-aware observation history needed for global tender ingestion, lifecycle tracking, search, alerts and AI analysis.

## Implemented
- OCDS-aligned procurement record layer linked to canonical opportunities.
- Planning/tender/award/contract lifecycle fields.
- Award and contract supplier references.
- Procurement document registry.
- Source observations preserving change history and raw-payload hashes.
- Source-scoped canonical opportunity identity using SHA-256 of source ID + source record ID.
- REST read endpoints for procurement lists and individual records.
- Protected ingestion endpoint for source adapters.
- UTC normalisation for incoming timestamps.
- ISO-2 country and ISO-4217-style currency validation.
- Idempotent source-aware upsert behaviour.
- Core schema migration 1.3.0.

## Provenance
Every ingested procurement record must retain its source ID, source record ID, observation timestamp and raw payload hash. Source URLs are retained where supplied. This is a foundation for later evidence lineage and AI citations.

## Security
Read operations require WordPress read capability. Ingestion requires WordPress manage_options until the organisation/tenant permission model is implemented. Incoming URLs are escaped, identifiers are sanitised, and dates/codes are validated.

## Deliberate constraints
This phase does not claim global live procurement coverage. Country adapters, licensed source connections, OCDS feeds, SAM.gov/TED adapters, ingestion queues, change detection workers and production-scale external data services remain later phases.

## Acceptance
- Schema contract passes.
- PHP lint passes.
- Control-plane validation passes.
- Procurement routes are registered.
- Migration 1.3.0 is idempotent after the schema version reaches 1.3.0.
- No fabricated procurement records are introduced.
