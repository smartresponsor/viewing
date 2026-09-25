import { defineConfig } from '@playwright/test';

const port = process.env.VIEWING_PLAYWRIGHT_PORT ?? '19081';
const baseURL = `http://127.0.0.1:${port}`;

export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: false,
  use: {
    baseURL,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0.0.0 Safari/537.36',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: `php -S 127.0.0.1:${port} -t public`,
    url: `${baseURL}/viewing`,
    reuseExistingServer: true,
    timeout: 30000,
    env: {
      APP_ENV: 'test',
      APP_DEBUG: '0',
    },
  },
});
