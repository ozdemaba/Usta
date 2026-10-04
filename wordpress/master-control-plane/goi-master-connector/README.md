# GOI Master Connector 1.1.0

Secure WordPress control-plane foundation for Global Opportunity Intelligence.

## Security posture
- HMAC request authentication with timestamp replay window.
- WordPress capability + REST nonce for browser requests.
- Strict workspace path allowlist and traversal rejection.
- SHA-256 manifest and post-write verification.
- Deployment lock and release idempotency.
- Pre-write snapshots.
- Automatic rollback on deployment failure.
- No arbitrary shell or code execution API.
- Audit/state transitions persisted in WordPress.

## Current limitation
The deployment endpoint is intentionally a controlled file-staging primitive. It does not yet fetch releases from GitHub, execute migrations, activate plugin replacements, or perform full application regression tests. Those are subsequent phases and must be implemented before production autonomous deployment is declared ready.
