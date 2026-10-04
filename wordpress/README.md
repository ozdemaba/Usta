# WordPress Application

The WordPress application is split into two deliberately distinct components:

- **master-control-plane**: trusted deployment/control infrastructure.
- **goi-core**: the actual Global Opportunity Intelligence product.

The control plane must not become the business application, and GOI Core must not acquire unrestricted deployment privileges.
