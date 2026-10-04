# Phase 5 — Geographic Data Layer

Adds country/region service contracts and server-side country aggregation. No boundary geometry is fabricated and no uncontrolled public map dependency is introduced.

Contracts:
- /wp-json/goi/v1/geo/countries
- /wp-json/goi/v1/geo/regions?country=AU
- GOI_Core_Map_Aggregation::by_country()

Production geometry must come from an approved/licensed, versioned and checksum-verified boundary dataset. Next: canonical country seed, geometry ingestion/versioning, hierarchy validation, clustering/vector tiles, and map rendering.
