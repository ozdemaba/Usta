# Control Plane Protocol

## Phase 1 contract

The WordPress connector is a security boundary, not the application itself.

### Required release fields
- release_id
- version
- build_id
- files[]
- files[].path
- files[].sha256
- files[].size
- files[].content for the current controlled staging primitive

### State machine
created -> backed_up -> staged -> installed -> migrated -> health_checked -> regression_checked -> completed

Failure path:
failed -> rollback_pending -> rolled_back

A deployment lock prevents concurrent releases. A release_id already recorded cannot be replayed.

### Integrity
Every file is validated against its declared byte length and SHA-256 before it is accepted. Files are written to a temporary sibling and renamed into place only after a second SHA-256 check.

### Important production gate
Phase 1 is not the final GitHub-to-WordPress installer. Before production autonomous deployment, the connector must gain:
1. authenticated release retrieval,
2. detached signature verification,
3. immutable release staging,
4. GOI Core directory swap,
5. activation-state preservation,
6. database migration runner with checkpoints,
7. application health checks,
8. regression suite invocation through a safe application test endpoint,
9. full rollback including database/activation state,
10. release retention and garbage collection.

No phase is marked production-complete until those controls are implemented and tested.
