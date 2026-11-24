import { useState } from 'react';
import { Play, RotateCcw } from 'lucide-react';

interface TerminalEntry {
  command: string;
  output: string[];
}

const PRESET_COMMANDS = [
  { label: 'mecene new mon-projet', cmd: 'mecene new mon-projet' },
  { label: 'php artisan key:generate', cmd: 'php artisan key:generate' },
  { label: 'php artisan migrate', cmd: 'php artisan migrate' },
  { label: 'php artisan make:controller CommentController', cmd: 'php artisan make:controller CommentController' },
  { label: 'php artisan make:model Comment', cmd: 'php artisan make:model Comment' },
  { label: 'php artisan migrate --rollback', cmd: 'php artisan migrate --rollback' },
  { label: 'php artisan serve', cmd: 'php artisan serve' },
];

export function ArtisanSimulator() {
  const [commandInput, setCommandInput] = useState('mecene new mon-projet');
  const [migrated, setMigrated] = useState(false);
  const [history, setHistory] = useState<TerminalEntry[]>([
    {
      command: 'php artisan --version',
      output: ['Mecene Artisan CLI 1.0.0 (mecene/framework)'],
    },
  ]);

  const executeCommand = (rawCmd: string) => {
    const cmd = rawCmd.trim();
    if (!cmd) return;

    let output: string[] = [];

    if (cmd.startsWith('mecene new ')) {
      const name = cmd.replace('mecene new ', '').trim() || 'mon-projet';
      output = [
        `Création du projet Mecene dans [${name}]...`,
        `Creating a "mecene/app" project at "./${name}"`,
        'Installing mecene/framework (v1.0.0)',
        'Clé applicative générée avec succès : base64:kP9xL2mR8vN4qT7wZ1yC5bH0jF3sD6aE...',
        'Initialisation du dépôt Git...',
        `Application prête ! cd ${name} && php artisan migrate && php artisan serve`,
      ];
    } else if (cmd === 'php artisan key:generate') {
      output = [
        'Clé applicative générée avec succès : base64:qW8eR4tY2uI9oP1aS5dF7gH3jK6lZ0xC...',
      ];
    } else if (cmd === 'php artisan migrate') {
      if (!migrated) {
        setMigrated(true);
        output = [
          'Migration exécutée : 2026_01_01_000001_create_users_table',
          'Migration exécutée : 2026_01_01_000002_create_posts_table',
        ];
      } else {
        output = ['Base de données déjà à jour. Rien à migrer.'];
      }
    } else if (cmd === 'php artisan migrate --rollback') {
      if (migrated) {
        setMigrated(false);
        output = [
          'Rollback effectué : 2026_01_01_000002_create_posts_table',
          'Rollback effectué : 2026_01_01_000001_create_users_table',
        ];
      } else {
        output = ['Aucune migration à annuler.'];
      }
    } else if (cmd.startsWith('php artisan make:controller ')) {
      const name = cmd.replace('php artisan make:controller ', '').trim() || 'DemoController';
      const normalized = name.endsWith('Controller') ? name : `${name}Controller`;
      output = [`Contrôleur créé (via stubs/controller.stub) : app/Controllers/${normalized}.php`];
    } else if (cmd.startsWith('php artisan make:model ')) {
      const name = cmd.replace('php artisan make:model ', '').trim() || 'Item';
      const normalized = name.charAt(0).toUpperCase() + name.slice(1);
      output = [`Modèle créé (via stubs/model.stub) : app/Models/${normalized}.php`];
    } else if (cmd.startsWith('php artisan make:migration ')) {
      const name = cmd.replace('php artisan make:migration ', '').trim().toLowerCase() || 'create_items_table';
      output = [`Migration créée (via stubs/migration.stub) : database/migrations/2026_11_24_112045_${name}.php`];
    } else if (cmd.startsWith('php artisan serve')) {
      output = [
        'Serveur Mecene démarré sur http://127.0.0.1:8000',
        'Document root : packages/app/public',
        'Appuyez sur Ctrl+C pour arrêter.',
      ];
    } else {
      output = [
        `Commande non reconnue : ${cmd}`,
        'Commandes : mecene new, key:generate, migrate, migrate --rollback, make:controller, make:model, make:migration, serve',
      ];
    }

    setHistory((prev) => [...prev, { command: cmd, output }]);
  };

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <div className="lg:col-span-6 space-y-4">
        <div className="rounded-lg border border-slate-200 bg-white p-5">
          <div className="flex items-center justify-between gap-2">
            <div>
              <h2 className="text-base font-semibold text-slate-900">Console CLI Artisan &amp; Installer</h2>
              <p className="text-xs text-slate-500">
                mecene/installer (mecene new) · mecene/framework (artisan)
              </p>
            </div>
            <button
              type="button"
              onClick={() => setHistory([{ command: 'php artisan --version', output: ['Mecene Artisan CLI 1.0.0'] }])}
              className="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 whitespace-nowrap shrink-0"
            >
              <RotateCcw className="h-3.5 w-3.5" />
              <span>Effacer</span>
            </button>
          </div>

          <div className="mt-4 flex flex-wrap gap-2">
            {PRESET_COMMANDS.map((preset) => (
              <button
                key={preset.cmd}
                type="button"
                onClick={() => {
                  setCommandInput(preset.cmd);
                  executeCommand(preset.cmd);
                }}
                className="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono text-[11px] text-slate-700 hover:bg-slate-100 whitespace-nowrap"
              >
                {preset.label}
              </button>
            ))}
          </div>

          <form
            onSubmit={(e) => {
              e.preventDefault();
              executeCommand(commandInput);
            }}
            className="mt-4 flex items-center gap-2"
          >
            <input
              type="text"
              value={commandInput}
              onChange={(e) => setCommandInput(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-xs text-slate-900 focus:border-slate-900 focus:outline-none"
            />
            <button
              type="submit"
              className="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-slate-900 px-3.5 py-2 text-xs font-medium text-white hover:bg-slate-800 whitespace-nowrap"
            >
              <Play className="h-3.5 w-3.5" />
              <span>Exécuter</span>
            </button>
          </form>

          <div className="mt-4 max-h-[340px] overflow-auto rounded-lg bg-slate-950 p-4 font-mono text-xs text-slate-200 space-y-3">
            {history.map((entry, idx) => (
              <div key={idx}>
                <div className="text-slate-400">$ {entry.command}</div>
                {entry.output.map((line, lineIdx) => (
                  <div key={lineIdx} className="mt-0.5 text-emerald-400">
                    {line}
                  </div>
                ))}
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="lg:col-span-6">
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-950 text-slate-100">
          <div className="border-b border-slate-800 bg-slate-900 px-5 py-4">
            <div className="text-xs font-semibold text-red-400">
              RuntimeException · APP_DEBUG=true
            </div>
            <h3 className="mt-1 text-base font-semibold text-white">
              Erreur volontaire de démonstration pour inspecter la page de debug Mecene.
            </h3>
            <p className="mt-1 font-mono text-xs text-slate-400">
              GET http://127.0.0.1:8000/debug-test — routes/web.php:34
            </p>
          </div>

          <div className="p-4 font-mono text-xs">
            <div className="mb-2 text-slate-400">Extrait du fichier source (routes/web.php)</div>
            <div className="rounded bg-slate-900 p-3 space-y-1">
              <div className="flex gap-4 text-slate-400">
                <span className="w-6 text-right tabular-nums">32</span>
                <span>$router-&gt;get('/debug-test', function (): never {'{'}</span>
              </div>
              <div className="flex gap-4 rounded bg-red-950/70 px-1 text-red-200 font-semibold">
                <span className="w-6 text-right tabular-nums">33</span>
                <span>    throw new RuntimeException('Erreur volontaire...');</span>
              </div>
              <div className="flex gap-4 text-slate-300">
                <span className="w-6 text-right tabular-nums">34</span>
                <span>{'}'});</span>
              </div>
            </div>

            <div className="mt-4 mb-2 text-slate-400">Pile d’appels (Stack Trace)</div>
            <div className="divide-y divide-slate-800 rounded bg-slate-900">
              <div className="px-3 py-2 text-slate-300">
                #0 Mecene\Container\Container-&gt;call() — vendor/mecene/framework/src/Routing/Router.php
              </div>
              <div className="px-3 py-2 text-slate-300">
                #1 Mecene\Routing\Router-&gt;dispatch() — vendor/mecene/framework/src/Foundation/Application.php
              </div>
              <div className="px-3 py-2 text-slate-300">
                #2 Mecene\Foundation\Application-&gt;handle() — public/index.php
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
