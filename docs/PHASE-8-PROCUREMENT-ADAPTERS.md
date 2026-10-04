# Phase 8 — Procurement Source Adapter Foundation

## Objective
Establish a controlled adapter boundary for authoritative procurement sources before enabling live ingestion.

## Sources verified for integration design
- TED EU Search API: published procurement notices, anonymous search/retrieval, bulk XML and open-data reuse paths.
- SAM.gov: public contract opportunities and contract-award data services; API access must follow SAM.gov Terms of Use and key/security requirements.
- AusTender: Australian Government procurement datasets and notices.
- UK Find a Tender: search APIs for notices/opportunities/contracts.

## Implemented
- Common adapter interface.
- Adapter registry.
- HTTPS-only outbound transport boundary using WordPress safe HTTP.
- No direct adapter database writes.
- Canonical normalization boundary.
- Source manifests capturing owner, jurisdiction, auth, endpoint, coverage and reuse policy.
- Initial adapters are deliberately non-ingesting until endpoint/query/credential/licence configuration is explicitly validated.

## Guardrail
No live records are fabricated. A source being registered does not mean production ingestion is enabled.

## Next
Implement the TED adapter first, followed by SAM.gov, AusTender and UK Find a Tender ingestion workers, with source-specific pagination/checkpoints, rate limits, retries, dead-letter handling, document retrieval and provenance-preserving normalization.