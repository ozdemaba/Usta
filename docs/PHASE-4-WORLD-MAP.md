# Phase 4 — World Map & Geographic Intelligence Foundation

## Scope
Establish the production-facing map contract without pretending the data layer is complete.

### Implemented
- MapLibre adapter boundary.
- Public map configuration endpoint.
- Bounded viewport endpoint with strict latitude/longitude/limit validation.
- Opportunity marker payload with deadline classification.
- GeoJSON country-feature contract for future boundary providers.
- Scoped map CSS/JS assets.
- Contract test.

### Production constraints
- No uncontrolled dependency on public OSM tiles or Nominatim.
- Browser receives bounded viewport results, not the entire opportunity table.
- Production scale must replace row-level viewport fallback with server-side aggregation/vector tiles backed by PostGIS or equivalent.
- Country geometry must come from a licensed/approved boundary dataset and be cached/versioned.
- All timestamps are intended to be UTC.

### Next map work
1. Add latitude/longitude columns to the opportunity schema through a versioned migration.
2. Add country/region geometry tables and approved boundary dataset ingestion.
3. Add server-side aggregation and clustering.
4. Add MapLibre style/source/layer wiring and drill-down interactions.
5. Add map filters and country/region intelligence overlays.
6. Add PostGIS/vector-tile adapter for production scale.
