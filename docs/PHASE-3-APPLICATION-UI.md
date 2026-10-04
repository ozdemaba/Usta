# Phase 3 — Application UI

## Scope
Establish the first real GOI application surface in WordPress:
- premium dark application shell
- responsive admin command centre
- module navigation
- public-facing GOI Intelligence page via shortcode
- reusable map, KPI, AI operations and module workspace surfaces
- UI asset loading only where GOI is rendered
- no fabricated live metrics

## Access
After the GOI Core release containing this phase is installed and activated:
1. WordPress Admin → GOI Intelligence → Command Centre.
2. A page named “GOI Intelligence” is provisioned automatically on activation.
3. The public page contains the [goi_intelligence] application shell.

## Gate
The phase is not complete until PHP lint, security scans, schema contract, UI contract and CI all pass. Live deployment remains a separate authenticated deployment gate.
