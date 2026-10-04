# Deployment Control Plane

The deployment path is designed for unattended, auditable releases:

GitHub source -> CI validation -> immutable release manifest -> authenticated WordPress control plane -> pre-deploy snapshot -> staged write -> validation -> activation/migration -> health check -> regression check.

## Security requirements

- Machine requests use timestamped HMAC authentication.
- Release manifests contain SHA-256 hashes for every payload file.
- The WordPress connector must restrict deployment paths to its configured workspace/application roots.
- Production operations must require privileged machine authorization and/or explicit administrator approval according to environment policy.
- Rollback is a first-class operation, not an emergency filesystem workaround.
- Credentials are stored as GitHub/WordPress secrets and are never committed to the repository.

## Current limitation

The repository currently contains the protocol and application foundation. The installed WordPress connector still needs the release-fetch/install adapter and its live authenticated transport to be wired and verified. Until that verification succeeds, releases must not be represented as automatically deployed to production.
