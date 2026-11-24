import { useState } from 'react';
import { Check, Copy } from 'lucide-react';
import { VortexFile } from '../data/vortexFiles';

interface CodeViewerProps {
  file: VortexFile | null;
}

export function CodeViewer({ file }: CodeViewerProps) {
  const [copied, setCopied] = useState(false);

  if (!file) {
    return (
      <div className="flex h-full min-h-[420px] items-center justify-center rounded-lg border border-slate-200 bg-white p-8 text-center">
        <div className="max-w-sm">
          <p className="text-sm font-semibold text-slate-900">Aucun fichier sélectionné</p>
          <p className="mt-1 text-sm text-slate-500">
            Sélectionnez un fichier dans l’arborescence ou choisissez un composant architectural pour inspecter le code PHP complet.
          </p>
        </div>
      </div>
    );
  }

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(file.content);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1800);
    } catch {
      setCopied(false);
    }
  };

  const lines = file.content.split('\n');

  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3">
        <div className="min-w-0">
          <h2 className="truncate font-mono text-sm font-semibold text-slate-900">
            {file.path}
          </h2>
          <div className="mt-0.5 flex items-center gap-2 text-xs text-slate-500 tabular-nums">
            <span>{file.lines} lignes</span>
            <span aria-hidden="true">·</span>
            <span>{file.bytes} octets</span>
            <span aria-hidden="true">·</span>
            <span>UTF-8</span>
          </div>
        </div>

        <button
          type="button"
          onClick={handleCopy}
          className="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900 whitespace-nowrap focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900"
        >
          {copied ? (
            <>
              <Check className="h-3.5 w-3.5 text-emerald-600" />
              <span>Copié</span>
            </>
          ) : (
            <>
              <Copy className="h-3.5 w-3.5 text-slate-500" />
              <span>Copier le fichier</span>
            </>
          )}
        </button>
      </div>

      <div className="max-h-[68vh] overflow-auto bg-slate-950 p-4 text-[13px] leading-relaxed text-slate-100">
        <table className="w-full border-collapse font-mono">
          <tbody>
            {lines.map((line, idx) => (
              <tr key={idx} className="hover:bg-slate-900/70">
                <td className="w-12 select-none pr-4 text-right text-xs text-slate-500 tabular-nums align-top">
                  {idx + 1}
                </td>
                <td className="whitespace-pre text-slate-100">{line || ' '}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
