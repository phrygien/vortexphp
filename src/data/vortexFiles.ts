export interface VortexFile {
  path: string;
  name: string;
  directory: string;
  packageName: 'root' | 'framework' | 'app' | 'installer';
  content: string;
  lines: number;
  bytes: number;
}

const rawModules = import.meta.glob(
  [
    '/mecene/**/*',
    '/mecene/packages/app/.env.example',
    '/mecene/packages/app/public/.htaccess',
    '/mecene/packages/app/storage/**/.gitkeep',
  ],
  {
    query: '?raw',
    import: 'default',
    eager: true,
  }
) as Record<string, string>;

function detectPackage(path: string): VortexFile['packageName'] {
  if (path.startsWith('packages/framework/')) return 'framework';
  if (path.startsWith('packages/app/')) return 'app';
  if (path.startsWith('packages/installer/')) return 'installer';
  return 'root';
}

export const VORTEX_FILES: VortexFile[] = Object.entries(rawModules)
  .map(([fullPath, content]) => {
    const path = fullPath.replace(/^\/mecene\//, '');
    const parts = path.split('/');
    const name = parts[parts.length - 1] ?? path;
    const directory = parts.length > 1 ? parts.slice(0, -1).join('/') : '/';
    const text = typeof content === 'string' ? content : '';

    return {
      path,
      name,
      directory,
      packageName: detectPackage(path),
      content: text,
      lines: text === '' ? 0 : text.split('\n').length,
      bytes: new TextEncoder().encode(text).length,
    };
  })
  .sort((a, b) => a.path.localeCompare(b.path));

export const VORTEX_FILE_MAP: Record<string, VortexFile> = Object.fromEntries(
  VORTEX_FILES.map((file) => [file.path, file])
);
