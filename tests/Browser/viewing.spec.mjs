import { expect, test } from '@playwright/test';

test('standalone Viewing home renders through the central view boundary', async ({ page }) => {
  const response = await page.goto('/viewing');

  expect(response).not.toBeNull();
  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('text/html');
  expect(response.headers()['x-viewing-guard']).toBeUndefined();
  await expect(page.locator('body')).toContainText('Viewing');
});
