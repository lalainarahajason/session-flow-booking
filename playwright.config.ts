import { defineConfig } from '@playwright/test';
export default defineConfig({ testDir: './tests/e2e', workers: 1, reporter: [['list'], ['json', {outputFile: 'artifacts/playwright/results.json'}]], use: { baseURL: 'http://localhost:8897', headless: true }, outputDir: 'artifacts/playwright/output' });
