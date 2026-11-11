#!/usr/bin/env bash
set -euo pipefail
# Explicit distributable allowlist. Development tools and fixtures never enter the package.
if [ -e dist/session-flow-bookings ]; then
 echo "Existing package directory: preserve it and choose a clean build workspace." >&2
 exit 1
fi
mkdir -p dist/session-flow-bookings
for item in session-flow-bookings.php uninstall.php readme.txt LICENSE README.md CHANGELOG.md composer.json composer.lock src templates assets; do
 cp -R "$item" dist/session-flow-bookings/
done
composer install --working-dir=dist/session-flow-bookings --no-dev --no-interaction --no-scripts --no-plugins
(cd dist && zip -qr session-flow-bookings-0.1.0.zip session-flow-bookings)
shasum -a 256 dist/session-flow-bookings-0.1.0.zip
