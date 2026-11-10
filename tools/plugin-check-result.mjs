/** Fail closed: Plugin Check 2.2.0 can return exit code 0 with findings. */
export function assessResult(stdout, stderr, exitCode) {
  if (exitCode !== 0) return { ok: false, reason: `WP-CLI exit ${exitCode}` };
  if (stderr.trim()) return { ok: false, reason: 'Unexpected stderr; inspect full report' };
  const output = stdout.trim();
  if (/^Success: Checks complete\. No errors found\.$/.test(output)) {
    return { ok: true, reason: 'Plugin Check reports no findings' };
  }
  try {
    const findings = JSON.parse(output);
    if (Array.isArray(findings) && findings.length > 0) {
      return { ok: false, reason: `${findings.length} finding(s), including warnings` };
    }
  } catch { /* Unknown or partial output must never pass. */ }
  return { ok: false, reason: 'Missing or unrecognized Plugin Check result' };
}
