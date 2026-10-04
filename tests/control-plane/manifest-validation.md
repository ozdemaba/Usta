# Control Plane Test Matrix

- Reject missing release_id.
- Reject invalid release_id characters.
- Reject missing file hash.
- Reject non-SHA-256 hash.
- Reject unsafe relative paths.
- Reject traversal attempts.
- Reject files above configured count.
- Reject byte-size mismatch.
- Reject content hash mismatch.
- Reject post-write hash mismatch.
- Reject duplicate release_id.
- Reject concurrent deployment lock.
- Verify failure automatically restores pre-existing files.
- Verify audit records include request and release identifiers.
- Verify connector never exposes arbitrary shell/code execution.
