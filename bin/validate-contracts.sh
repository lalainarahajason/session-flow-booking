#!/usr/bin/env bash
set -euo pipefail
npx --no-install redocly lint specs/contracts/openapi.json --extends spec
python3 specs/contracts/validate_contracts.py
