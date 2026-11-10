/** Local Docker tooling only; never distributed as plugin runtime code. */
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';
import { assessResult } from './plugin-check-result.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const cli = resolve(root, 'node_modules/@wordpress/env/bin/wp-env');
const pluginCheckVersion = '2.2.0';
const container = 'tests-cli';
const mode = process.argv[2];
if (!['install', 'check'].includes(mode) || process.argv.length !== 3) {
  console.error('Usage: node tools/plugin-check.mjs install|check');
  process.exit(2);
}
if (!existsSync(cli)) {
  console.error('wp-env absent. Exécuter npm ci.');
  process.exit(2);
}
const baseConfig = JSON.parse(readFileSync(resolve(root, '.wp-env.json'), 'utf8'));
const overrideFile = resolve(root, '.wp-env.override.json');
const override = existsSync(overrideFile) ? JSON.parse(readFileSync(overrideFile, 'utf8')) : {};
const mappingKey = 'wp-content/plugins/session-flow-bookings';
const source = override.env?.tests?.mappings?.[mappingKey] ?? override.mappings?.[mappingKey]
  ?? baseConfig.env?.tests?.mappings?.[mappingKey] ?? baseConfig.mappings[mappingKey];
const target = resolve(root, source);
if (mode === 'check' && !existsSync(resolve(target, 'session-flow-bookings.php'))) {
  console.error('BLOQUÉ : session-flow-bookings.php absent ; aucun contrôle produit exécuté.');
  process.exit(2);
}

const report = resolve(root, 'artifacts/plugin-check', new Date().toISOString().replaceAll(':', '-'));
mkdirSync(report, { recursive: true });
let sequence = 0;
function wp(args, { capture = false, allowFailure = false } = {}) {
  // Pass flags to WP-CLI, not wp-env's own --version/--help parser.
  const argv = [cli, 'run', container, 'wp', '--', ...args];
  const run = spawnSync(process.execPath, argv, {
    cwd: root,
    env: { ...process.env, NO_COLOR: '1' },
    encoding: 'utf8',
    maxBuffer: 32 * 1024 * 1024,
    timeout: 180000,
  });
  const name = String(++sequence).padStart(2, '0');
  writeFileSync(resolve(report, `${name}.json`), JSON.stringify({
    command: ['node', ...argv], exitCode: run.status, error: run.error?.message,
    stdout: run.stdout, stderr: run.stderr,
  }, null, 2));
  if (!capture) {
    process.stdout.write(run.stdout || '');
    process.stderr.write(run.stderr || '');
  }
  if ((run.error || run.status !== 0) && !allowFailure) {
    console.error(`Commande échouée ; rapport : ${report}`);
    process.exit(run.status || 2);
  }
  return run;
}

function verifyCheckerVersion() {
  const version = wp(['plugin', 'get', 'plugin-check', '--field=version'], { capture: true });
  if (!version.stdout.split(/\r?\n/).some((line) => line.trim() === pluginCheckVersion)) {
    console.error(`Plugin Check ${pluginCheckVersion} requis ; rapport : ${report}`);
    process.exit(2);
  }
}

if (mode === 'install') {
  // A fixed official release; no destructive replacement of an existing checker.
  const installed = wp(['plugin', 'is-installed', 'plugin-check'], { capture: true, allowFailure: true });
  if (installed.status !== 0) {
    wp(['plugin', 'install', 'plugin-check', `--version=${pluginCheckVersion}`, '--activate']);
  } else {
    wp(['plugin', 'activate', 'plugin-check']);
  }
  wp(['plugin', 'verify-checksums', 'plugin-check']);
  verifyCheckerVersion();
  console.log(`Plugin Check ${pluginCheckVersion} installé dans ${container}. Rapport : ${report}`);
  process.exit(0);
}

wp(['core', 'version']);
wp(['cli', 'info']);
wp(['db', 'query', 'SELECT VERSION();']);
verifyCheckerVersion();

// Shell redirection here is a constant, internal command, with no user interpolation.
// Capturing inside the container avoids mixing wp-env status banners with the result.
const containerReport = `/var/www/html/wp-content/sfbk-check-reports/${report.split('/').at(-1)}`;
const output = `${containerReport}/stdout.log`;
const errors = `${containerReport}/stderr.log`;
const command = `wp plugin check session-flow-bookings --require=/var/www/html/wp-content/plugins/plugin-check/cli.php --format=strict-json > ${output} 2> ${errors}`;
const result = spawnSync(process.execPath, [cli, 'run', container, 'sh', '--', '-c', command], {
  cwd: root, encoding: 'utf8', timeout: 180000,
});
writeFileSync(resolve(report, 'runner.json'), JSON.stringify({ command, exitCode: result.status, stdout: result.stdout, stderr: result.stderr, error: result.error?.message }, null, 2));

try {
  const stdout = readFileSync(resolve(report, 'stdout.log'), 'utf8');
  const stderr = readFileSync(resolve(report, 'stderr.log'), 'utf8');
  const verdict = assessResult(stdout, stderr, result.status);
  writeFileSync(resolve(report, 'result.json'), JSON.stringify(verdict, null, 2));
  console.log(stdout);
  console.error(stderr);
  console.log(`${verdict.ok ? 'OK' : 'BLOQUÉ'} : ${verdict.reason}. Rapport : ${report}`);
  process.exit(verdict.ok ? 0 : 1);
} catch (error) {
  console.error(`BLOQUÉ : ${error.message}. Rapport : ${report}`);
  process.exit(2);
}
