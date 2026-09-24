import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: false,
  use: {
    baseURL: 'http://127.0.0.1:9081',
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0.0.0 Safari/537.36',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'php -S 127.0.0.1:9081 -t public',
    url: 'http://127.0.0.1:9081/viewing',
    reuseExistingServer: true,
    timeout: 30000,
    env: {
      APP_ENV: 'test',
      APP_DEBUG: '0',
    },
  },
});
