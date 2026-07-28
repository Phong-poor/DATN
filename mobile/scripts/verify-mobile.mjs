import { rmSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';

const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';
const outputDirectory = resolve('.expo', 'verify-export');

function run(args) {
  const result = spawnSync(npx, args, {
    cwd: process.cwd(),
    stdio: 'inherit',
    shell: process.platform === 'win32',
  });

  if (result.error) throw result.error;
  if (result.status !== 0) process.exit(result.status ?? 1);
}

try {
  rmSync(outputDirectory, { recursive: true, force: true });
  run(['--yes', 'expo-doctor']);
  run(['expo', 'export', '--platform', 'all', '--output-dir', outputDirectory]);
} finally {
  rmSync(outputDirectory, { recursive: true, force: true });
}
