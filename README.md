# Usta — Global Opportunity Intelligence

Canonical source repository for the Global Opportunity Intelligence (GOI) platform.

This repository is the source of truth for the WordPress control plane, GOI Core application, services, data contracts, agents, tests, deployment manifests, and documentation.

## Engineering rule

Every phase follows: build → validate → test → review → release → deploy → health-check → regression-check.

No production deployment is considered complete until the target WordPress environment reports a successful health check and the release is verified.
