#!/usr/bin/env bash
set -euo pipefail
npm ci --ignore-scripts
composer install --no-interaction
npm run env:start
docker compose up -d --wait --wait-timeout 120 mysql
# Connect only this project's tests-cli container after verifying its plugin bind mount.
test_cli=$(docker ps --filter label=com.docker.compose.service=tests-cli --format '{{.ID}}')
matched=""
for candidate in $test_cli; do
 if docker inspect "$candidate" --format '{{range .Mounts}}{{println .Source}}{{end}}' | rg -Fx "$PWD" >/dev/null; then matched="$candidate"; break; fi
done
if [ -z "$matched" ]; then echo "Project tests-cli container not found" >&2; exit 1; fi
if ! docker inspect "$matched" --format '{{json .NetworkSettings.Networks}}' | rg -q 'sfbk-sprint1_default'; then docker network connect sfbk-sprint1_default "$matched"; fi
mkdir -p artifacts/qa
docker exec "$matched" php /var/www/html/wp-content/sfbk-dev-source/vendor/bin/phpunit --no-configuration --bootstrap /var/www/html/wp-content/sfbk-dev-source/tests/bootstrap-integration.php /var/www/html/wp-content/sfbk-dev-source/tests/Integration | tee artifacts/qa/integration-ci.txt
# WordPress wp_die can exit zero before PHPUnit starts: require an actual completed suite.
rg -q '^OK \([0-9]+ tests?, [0-9]+ assertions?\)' artifacts/qa/integration-ci.txt
