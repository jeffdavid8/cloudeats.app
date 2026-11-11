import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import path from 'path';

test('chooses a server backup or upload and sends only selected tables after confirmation', async ({ page }) => {
  const html = execFileSync('php', [
    '-d', 'short_open_tag=1',
    path.resolve('tests/fixtures/admin-restore-render-worker.php'),
  ], { encoding: 'utf8' });
  await page.setContent(html);
  const breadcrumb = page.getByRole('navigation', { name: 'Breadcrumb' });
  await expect(breadcrumb.getByRole('link', { name: 'Admin Dashboard' })).toHaveAttribute('href', '?app=admin&p=dashboard');
  await expect(breadcrumb.getByRole('link', { name: 'Restore DB' })).toHaveAttribute('aria-current', 'page');
  await page.evaluate(() => {
    Object.assign(window, {
      submitted: [] as { backup: string | null; upload: string | null; tables: string[] }[],
      mb: {
        ajax: (options: { data: FormData; success: (value: object) => void; complete: () => void }) => {
          const file = options.data.get('db_file');
          (window as unknown as { submitted: object[] }).submitted.push({
            backup: options.data.get('backup_file'),
            upload: file instanceof File ? file.name : null,
            tables: options.data.getAll('target_tables[]'),
          });
          options.success({ success: true });
          options.complete();
        },
      },
    });
  });
  await expect(page.locator('#backupFile option')).toHaveCount(3);
  await page.locator('#backupFile').selectOption('backup.json');
  await page.locator('#submitBtn').click();
  await expect(page.locator('#uploadStatus')).toHaveValue(/check at least one/);
  await page.locator('input[value="selected_table"]').check();
  page.once('dialog', dialog => dialog.dismiss());
  await page.locator('#submitBtn').click();
  expect(await page.evaluate(() => (window as unknown as { submitted: object[] }).submitted)).toHaveLength(0);
  page.once('dialog', dialog => dialog.accept());
  await page.locator('#submitBtn').click();
  expect(await page.evaluate(() => (window as unknown as { submitted: object[] }).submitted)).toEqual([
    { backup: 'backup.json', upload: null, tables: ['selected_table'] },
  ]);
  await page.locator('#fileInput').setInputFiles({
    name: 'local.json', mimeType: 'application/json', buffer: Buffer.from('{"tables":{}}'),
  });
  await expect(page.locator('#backupFile')).toHaveValue('');
  page.once('dialog', dialog => dialog.accept());
  await page.locator('#submitBtn').click();
  expect(await page.evaluate(() => (window as unknown as { submitted: object[] }).submitted)).toEqual([
    { backup: 'backup.json', upload: null, tables: ['selected_table'] },
    { backup: null, upload: 'local.json', tables: ['selected_table'] },
  ]);
});
