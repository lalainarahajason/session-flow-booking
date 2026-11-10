import { test } from 'node:test';
import assert from 'node:assert/strict';
import { assessResult } from '../plugin-check-result.mjs';

test('accepts the pinned checker success message only after a successful process', () => {
  assert.equal(assessResult('Success: Checks complete. No errors found.\n', '', 0).ok, true);
  assert.equal(assessResult('Success: Checks complete. No errors found.', '', 1).ok, false);
});
test('blocks errors and warnings even when WP-CLI exits with zero', () => {
  for (const type of ['ERROR', 'WARNING']) {
    assert.equal(assessResult(JSON.stringify([{ type, code: 'example', message: 'finding' }]), '', 0).ok, false);
  }
});
test('blocks missing, truncated, unexpected and stderr results', () => {
  for (const output of ['', '[]', '{}', '[{"type":', 'Success: some other command']) {
    assert.equal(assessResult(output, '', 0).ok, false);
  }
  assert.equal(assessResult('Success: Checks complete. No errors found.', 'PHP warning', 0).ok, false);
  assert.equal(assessResult('', '', null).ok, false);
});
