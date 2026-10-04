# Phase 1 release installer contract

The release path is now designed around a signed immutable artifact rather than arbitrary file writes.

## Flow

1. GitHub Actions builds the GOI Core ZIP.
2. The builder computes SHA-256 for every archive member and the complete archive.
3. The release manifest records version, build, schema target, archive hash, plugin hash, file hashes and sizes.
4. The manifest is signed with Ed25519.
5. GitHub publishes the ZIP and manifest.
6. The WordPress Master Connector fetches the manifest and ZIP over HTTPS.
7. The connector verifies the detached signature using the administrator-configured trusted public key.
8. Every ZIP member is checked against the signed manifest before extraction.
9. The active GOI Core directory is moved to a versioned rollback location.
10. The verified release is installed, activated if previously active, migrated, health checked and regression checked.
11. Any post-install failure attempts automatic file rollback and refuses rollback when the database schema has advanced beyond the recorded previous schema.

## Required configuration

The connector's Release signing public key must be configured in its WordPress admin screen. The private key must never be stored in WordPress or committed to GitHub; it belongs in the GitHub Actions secret GOI_RELEASE_PRIVATE_KEY.

The installer deliberately does not accept a public key supplied by the deployment caller.

## Important boundary

This is a real release-installation foundation, but it is not yet the final production deployment system. Remaining gates include reversible database migration metadata, full database snapshots/rollback, release retention, nonce/idempotency storage for machine requests, granular deployment scopes, and an end-to-end deployment test against the user's WordPress site.
