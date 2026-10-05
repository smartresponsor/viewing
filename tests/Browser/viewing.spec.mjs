import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const visualComponent = 'Viewing';

test('standalone Viewing home renders through the central view boundary', async ({ page }) => {
  const response = await page.goto('/viewing');

  expect(response).not.toBeNull();
  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('text/html');
  expect(response.headers()['x-viewing-guard']).toBeUndefined();
  await expect(page.locator('body')).toContainText('Viewing');

  const date = new Date().toISOString().slice(0, 10);
  const runId = (process.env.CMCP_VISUAL_RUN_ID ?? `playwright-${Date.now()}`)
    .replace(/[^A-Za-z0-9._-]+/g, '-');
  const artifactDirectory = resolve('..', 'var', visualComponent, date, runId);

  await mkdir(artifactDirectory, { recursive: true });
  await page.screenshot({ path: resolve(artifactDirectory, 'viewing-home.png'), fullPage: true });
});
