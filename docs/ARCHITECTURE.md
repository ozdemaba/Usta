# GOI Architecture

## System boundary

GOI is a WordPress-based application with a dedicated WordPress control plane. WordPress remains the product/admin shell and authenticated API boundary. Data-heavy intelligence services may run outside WordPress when scale, geospatial processing, vector search, OCR, or long-running agent execution require them.

## Runtime layers

1. **Control plane** — deployment orchestration, release verification, backups, rollback, health, audit and environment controls.
2. **GOI Core** — WordPress plugin containing product modules, REST API, permissions, migrations, admin UI and integration contracts.
3. **Intelligence services** — ingestion, normalization, search, knowledge graph, document processing and agent orchestration.
4. **Data plane** — WordPress relational data for application state plus PostgreSQL/PostGIS, object storage, Redis and vector search where justified by scale.
5. **External sources** — procurement, trade, country, project, company and market sources with licence and provenance metadata.

## Non-negotiable engineering rules

- No patch-on-patch plugin architecture.
- One versioned GOI Core product with upgrade-safe migrations.
- Every externally sourced fact retains provenance and retrieval metadata.
- AI output must distinguish evidence, inference, prediction and recommendation.
- Destructive actions require explicit authorization.
- Deployment is fail-closed: validate before activation and rollback on failed health checks.
- No production deployment is claimed until the target environment is independently verified.

## Phase gate

Every phase must produce code, automated validation, tests, documentation, a versioned release artifact and a recorded verification result before the next phase begins.
