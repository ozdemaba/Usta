# Deployment State Machine

A release progresses through explicit states:

created -> validated -> approved -> backed_up -> staged -> installed -> migrated -> health_checked -> regression_checked -> completed

Failure transitions to failed, followed by rollback_pending -> rolled_back -> verified.

No state may be skipped. Every transition records release ID, build ID, actor, timestamp, request ID, previous state, new state and outcome.

A release is successful only after health and regression checks pass against the target environment.
