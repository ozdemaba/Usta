# GOI Core

## Phase 2 database foundation

The core owns the first durable application schema: organisations, countries, regions, data_sources, opportunities, and opportunity_sources.

Schema creation uses WordPress dbDelta() and is idempotent. The schema version is persisted in goi_core_schema_version.

The control plane must execute the core migration during signed release installation; the plugin activation hook also provisions the schema for a fresh install.

No external data is accepted directly into these tables without a normalisation and provenance layer. Foreign-key-like identifiers are intentionally application-managed for WordPress database compatibility.
