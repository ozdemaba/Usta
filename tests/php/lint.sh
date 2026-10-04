#!/usr/bin/env bash
set -euo pipefail
files=$(find wordpress services -type f -name '*.php' 2>/dev/null || true)
if [ -z "$files" ]; then exit 0; fi
while IFS= read -r file; do php -l "$file"; done <<< "$files"
