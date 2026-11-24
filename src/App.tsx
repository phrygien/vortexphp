import { useMemo, useState } from 'react';
import { Download, FileCode2, FolderTree, Search } from 'lucide-react';
import { CodeViewer } from './components/CodeViewer';
import { CrudSimulator } from './components/CrudSimulator';
import { ArtisanSimulator } from './components/ArtisanSimulator';
import { ARCHITECTURE_STEPS } from './data/architectureSteps';
import { VORTEX_FILES, VORTEX_FILE_MAP, VortexFile } from './data/vortexFiles';
import { buildZipBlob } from './utils/zipBuilder';

type WorkspaceTab = 'explorer' | 'crud' | 'artisan';
type PackageFilter = 'all' | VortexFile['packageName'];

export default function App() {
  const [activeTab, setActiveTab] = useState<WorkspaceTab>('explorer');
  const [selectedPath, setSelectedPath] = useState<string>('packages/framework/composer.json');
  const [selectedStepId, setSelectedStepId] = useState<number | null>(null);
  const [packageFilter, setPackageFilter] = useState<PackageFilter>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const filteredFiles = useMemo(() => {
    let list = VORTEX_FILES;

    if (packageFilter !== 'all') {
      list = list.filter((f) => f.packageName === packageFilter);
    }

    if (selectedStepId !== null) {
      const step = ARCHITECTURE_STEPS.find((s) => s.id === selectedStepId);
      if (step) {
        list = list.filter((f) => step.files.includes(f.path));
      }
    }

    const q = searchQuery.trim().toLowerCase();
    if (q !== '') {
      list = list.filter(
        (f) => f.path.toLowerCase().includes(q) || f.content.toLowerCase().includes(q)
      );
    }

    return list;
  }, [packageFilter, selectedStepId, searchQuery]);

  const activeFile = VORTEX_FILE_MAP[selectedPath] ?? filteredFiles[0] ?? null;
  const activeStep = ARCHITECTURE_STEPS.find((s) => s.id === selectedStepId) ?? null;

  const totalLines = useMemo(
    () => VORTEX_FILES.reduce((sum, file) => sum + file.lines, 0),
    []
  );

  const handleDownloadZip = () => {
    const blob = buildZipBlob(
      VORTEX_FILES.map((f) => ({
        path: `mecene-monorepo/${f.path}`,
        content: f.content,
      }))
    );
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'mecene-php83-monorepo.zip';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-[1440px] items-center justify-between gap-8 px-6 py-3.5">
          <span className="font-display text-lg font-bold tracking-tight text-slate-900 whitespace-nowrap shrink-0">
            Mecene PHP 8.3+
          </span>

          <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <button
              type="button"
              onClick={() => setActiveTab('explorer')}
              className={`hover:text-slate-900 transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'explorer' ? 'text-slate-900 underline underline-offset-8' : ''
              }`}
            >
              Monorepo
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('crud')}
              className={`hover:text-slate-900 transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'crud' ? 'text-slate-900 underline underline-offset-8' : ''
              }`}
            >
              Démo CRUD
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('artisan')}
              className={`hover:text-slate-900 transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'artisan' ? 'text-slate-900 underline underline-offset-8' : ''
              }`}
            >
              Artisan &amp; Installer
            </button>
            <button
              type="button"
              onClick={() => {
                setActiveTab('explorer');
                setPackageFilter('all');
                setSelectedStepId(null);
                setSelectedPath('GUIDE_PUBLICATION.md');
              }}
              className="hover:text-slate-900 transition-colors whitespace-nowrap shrink-0"
            >
              Guide Packagist
            </button>
          </nav>

          <div className="flex items-center shrink-0">
            <button
              type="button"
              onClick={handleDownloadZip}
              className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-slate-800 whitespace-nowrap shrink-0"
            >
              <Download className="h-3.5 w-3.5" />
              <span>Télécharger monorepo.zip</span>
            </button>
          </div>
        </div>
      </header>

      <div className="mx-auto max-w-[1440px] px-6 py-6">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
          <div>
            <h1 className="text-xl font-bold tracking-tight text-slate-900">
              Mecene — Distribution Packagist en 3 packages
            </h1>
            <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 tabular-nums">
              <span>mecene/framework</span>
              <span aria-hidden="true">·</span>
              <span>mecene/app</span>
              <span aria-hidden="true">·</span>
              <span>mecene/installer</span>
              <span aria-hidden="true">·</span>
              <span>Templates .mecene.php</span>
              <span aria-hidden="true">·</span>
              <span>{VORTEX_FILES.length} fichiers</span>
              <span aria-hidden="true">·</span>
              <span>{totalLines} lignes</span>
            </div>
          </div>

          <div className="flex items-center gap-1 rounded-lg bg-slate-200/70 p-1">
            <button
              type="button"
              onClick={() => setActiveTab('explorer')}
              className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'explorer'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Explorateur de code
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('crud')}
              className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'crud'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Démo CRUD &amp; ORM
            </button>
            <button
              type="button"
              onClick={() => setActiveTab('artisan')}
              className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors whitespace-nowrap shrink-0 ${
                activeTab === 'artisan'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Artisan CLI &amp; Debug
            </button>
          </div>
        </div>

        {activeTab === 'explorer' && (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <aside className="lg:col-span-4 xl:col-span-3 space-y-4">
              <div className="rounded-lg border border-slate-200 bg-white p-3">
                <div className="mb-2 text-xs font-semibold text-slate-900">Package</div>
                <div className="grid grid-cols-2 gap-1.5">
                  {(
                    [
                      ['all', 'Tous'],
                      ['framework', 'framework'],
                      ['app', 'app'],
                      ['installer', 'installer'],
                    ] as const
                  ).map(([key, label]) => (
                    <button
                      key={key}
                      type="button"
                      onClick={() => {
                        setPackageFilter(key);
                        setSelectedStepId(null);
                      }}
                      className={`rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors whitespace-nowrap ${
                        packageFilter === key
                          ? 'bg-slate-900 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      }`}
                    >
                      {label}
                    </button>
                  ))}
                </div>
              </div>

              <div className="rounded-lg border border-slate-200 bg-white p-4">
                <div className="flex items-center justify-between gap-2 mb-3">
                  <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-900">
                    <FolderTree className="h-3.5 w-3.5 text-slate-500" />
                    <span>Composants</span>
                  </span>
                  {selectedStepId !== null && (
                    <button
                      type="button"
                      onClick={() => setSelectedStepId(null)}
                      className="text-xs font-medium text-slate-600 hover:text-slate-900"
                    >
                      Tout voir
                    </button>
                  )}
                </div>

                <div className="max-h-52 overflow-y-auto space-y-1 pr-1">
                  {ARCHITECTURE_STEPS.map((step) => {
                    const isSelected = selectedStepId === step.id;
                    return (
                      <button
                        key={step.id}
                        type="button"
                        onClick={() => {
                          setSelectedStepId(isSelected ? null : step.id);
                          setPackageFilter('all');
                          if (!isSelected && step.files[0]) {
                            setSelectedPath(step.files[0]);
                          }
                        }}
                        className={`w-full rounded-md px-2.5 py-1.5 text-left text-xs transition-colors ${
                          isSelected
                            ? 'bg-slate-900 font-medium text-white'
                            : 'text-slate-700 hover:bg-slate-100'
                        }`}
                      >
                        <div className="truncate">{step.title}</div>
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="rounded-lg border border-slate-200 bg-white p-4">
                <div className="relative mb-3">
                  <Search className="pointer-events-none absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Filtrer les fichiers..."
                    className="w-full rounded-md border border-slate-300 py-1.5 pl-8 pr-3 text-xs text-slate-900 focus:border-slate-900 focus:outline-none"
                  />
                </div>

                <div className="max-h-[380px] overflow-y-auto space-y-0.5 pr-1">
                  {filteredFiles.map((file) => {
                    const isActive = activeFile?.path === file.path;
                    return (
                      <button
                        key={file.path}
                        type="button"
                        onClick={() => setSelectedPath(file.path)}
                        className={`flex w-full items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-left font-mono text-[11.5px] transition-colors ${
                          isActive
                            ? 'bg-slate-900 font-medium text-white'
                            : 'text-slate-700 hover:bg-slate-100'
                        }`}
                      >
                        <span className="inline-flex min-w-0 items-center gap-1.5">
                          <FileCode2 className="h-3.5 w-3.5 shrink-0 opacity-60" />
                          <span className="truncate">{file.path}</span>
                        </span>
                        <span
                          className={`shrink-0 text-[10px] tabular-nums ${
                            isActive ? 'text-slate-300' : 'text-slate-400'
                          }`}
                        >
                          {file.lines}L
                        </span>
                      </button>
                    );
                  })}
                </div>
              </div>
            </aside>

            <main className="lg:col-span-8 xl:col-span-9 space-y-4">
              {activeStep && (
                <div className="rounded-lg border border-slate-200 bg-white px-5 py-4">
                  <h2 className="text-sm font-semibold text-slate-900">{activeStep.title}</h2>
                  <p className="mt-1 text-sm text-slate-600">{activeStep.summary}</p>
                </div>
              )}

              <CodeViewer file={activeFile} />
            </main>
          </div>
        )}

        {activeTab === 'crud' && <CrudSimulator />}

        {activeTab === 'artisan' && <ArtisanSimulator />}
      </div>
    </div>
  );
}
