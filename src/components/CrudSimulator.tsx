import { useState } from 'react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';

interface DemoPost {
  id: number;
  userId: number;
  authorName: string;
  authorEmail: string;
  title: string;
  slug: string;
  content: string;
  published: boolean;
  createdAt: string;
  updatedAt: string;
}

interface SqlTrace {
  id: number;
  method: string;
  route: string;
  sql: string;
  bindings: string;
}

const INITIAL_POSTS: DemoPost[] = [
  {
    id: 1,
    userId: 1,
    authorName: 'Camille Laurent',
    authorEmail: 'camille@vortex.local',
    title: 'Auto-wiring PSR-11 par réflexion en PHP 8.3',
    slug: 'auto-wiring-psr-11-par-reflexion-a3f19c',
    content:
      'Le conteneur Vortex analyse les constructeurs via ReflectionClass et résout récursivement chaque dépendance typée sans configuration manuelle.',
    published: true,
    createdAt: '2026-11-24 09:15:00',
    updatedAt: '2026-11-24 09:15:00',
  },
  {
    id: 2,
    userId: 1,
    authorName: 'Camille Laurent',
    authorEmail: 'camille@vortex.local',
    title: 'Compilateur Blade et protection XSS native',
    slug: 'compilateur-blade-protection-xss-9b22d1',
    content:
      'Chaque expression {{ $var }} est compilée vers htmlspecialchars() et mise en cache dans storage/cache/ pour des rendus instantanés.',
    published: true,
    createdAt: '2026-11-24 10:02:00',
    updatedAt: '2026-11-24 10:02:00',
  },
];

function slugify(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function CrudSimulator() {
  const [posts, setPosts] = useState<DemoPost[]>(INITIAL_POSTS);
  const [mode, setMode] = useState<'index' | 'create' | 'show' | 'edit'>('index');
  const [activeId, setActiveId] = useState<number | null>(null);

  const [titleInput, setTitleInput] = useState('');
  const [contentInput, setContentInput] = useState('');
  const [publishedInput, setPublishedInput] = useState(true);
  const [errors, setErrors] = useState<{ title?: string; content?: string }>({});
  const [flashMessage, setFlashMessage] = useState<string | null>(null);
  const [csrfToken] = useState('e8a4f92c1b07d54e98a1c3f621b4d8e0a7c5f1e2');

  const [sqlHistory, setSqlHistory] = useState<SqlTrace[]>([
    {
      id: 1,
      method: 'GET',
      route: '/posts (posts.index)',
      sql: 'SELECT * FROM posts ORDER BY id DESC',
      bindings: '[]',
    },
  ]);

  const recordSql = (method: string, route: string, sql: string, bindings: unknown) => {
    setSqlHistory((prev) => [
      {
        id: Date.now() + Math.random(),
        method,
        route,
        sql,
        bindings: JSON.stringify(bindings),
      },
      ...prev.slice(0, 5),
    ]);
  };

  const activePost = posts.find((p) => p.id === activeId) ?? null;

  const openCreate = () => {
    setTitleInput('');
    setContentInput('');
    setPublishedInput(true);
    setErrors({});
    setFlashMessage(null);
    setMode('create');
  };

  const openShow = (post: DemoPost) => {
    setActiveId(post.id);
    setErrors({});
    setMode('show');
    recordSql(
      'GET',
      `/posts/${post.id} (posts.show)`,
      'SELECT * FROM posts WHERE id = :p1 LIMIT 1; SELECT * FROM users WHERE id = :p2 LIMIT 1',
      { ':p1': post.id, ':p2': post.userId }
    );
  };

  const openEdit = (post: DemoPost) => {
    setActiveId(post.id);
    setTitleInput(post.title);
    setContentInput(post.content);
    setPublishedInput(post.published);
    setErrors({});
    setFlashMessage(null);
    setMode('edit');
  };

  const validateForm = (): boolean => {
    const nextErrors: { title?: string; content?: string } = {};
    const trimmedTitle = titleInput.trim();
    const trimmedContent = contentInput.trim();

    if (trimmedTitle.length === 0) {
      nextErrors.title = 'Le champ title est obligatoire.';
    } else if (trimmedTitle.length < 3) {
      nextErrors.title = 'Le champ title doit contenir au moins 3 caractères.';
    } else if (trimmedTitle.length > 120) {
      nextErrors.title = 'Le champ title ne doit pas dépasser 120 caractères.';
    }

    if (trimmedContent.length === 0) {
      nextErrors.content = 'Le champ content est obligatoire.';
    } else if (trimmedContent.length < 10) {
      nextErrors.content = 'Le champ content doit contenir au moins 10 caractères.';
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleStore = (e: React.FormEvent) => {
    e.preventDefault();
    if (!validateForm()) {
      return;
    }

    const now = new Date().toISOString().replace('T', ' ').slice(0, 19);
    const nextId = posts.length > 0 ? Math.max(...posts.map((p) => p.id)) + 1 : 1;
    const slug = `${slugify(titleInput) || 'post'}-${Math.random().toString(16).slice(2, 8)}`;

    const created: DemoPost = {
      id: nextId,
      userId: 1,
      authorName: 'Camille Laurent',
      authorEmail: 'camille@mecene.local',
      title: titleInput.trim(),
      slug,
      content: contentInput.trim(),
      published: publishedInput,
      createdAt: now,
      updatedAt: now,
    };

    setPosts((prev) => [created, ...prev]);
    setActiveId(created.id);
    setFlashMessage('Article publié avec succès.');
    setMode('show');
    recordSql(
      'POST',
      '/posts (posts.store)',
      'INSERT INTO posts (user_id, title, slug, content, published, created_at, updated_at) VALUES (:p1, :p2, :p3, :p4, :p5, :p6, :p7)',
      {
        ':p1': 1,
        ':p2': created.title,
        ':p3': created.slug,
        ':p4': created.content.slice(0, 28) + '...',
        ':p5': created.published ? 1 : 0,
      }
    );
  };

  const handleUpdate = (e: React.FormEvent) => {
    e.preventDefault();
    if (!activePost || !validateForm()) {
      return;
    }

    const now = new Date().toISOString().replace('T', ' ').slice(0, 19);
    setPosts((prev) =>
      prev.map((p) =>
        p.id === activePost.id
          ? {
              ...p,
              title: titleInput.trim(),
              content: contentInput.trim(),
              published: publishedInput,
              updatedAt: now,
            }
          : p
      )
    );

    setFlashMessage('Article mis à jour avec succès.');
    setMode('show');
    recordSql(
      'PUT',
      `/posts/${activePost.id} (posts.update)`,
      'UPDATE posts SET title = :p1, content = :p2, published = :p3, updated_at = :p4 WHERE id = :p5',
      { ':p1': titleInput.trim(), ':p3': publishedInput ? 1 : 0, ':p5': activePost.id }
    );
  };

  const handleDelete = (post: DemoPost) => {
    setPosts((prev) => prev.filter((p) => p.id !== post.id));
    setFlashMessage('Article supprimé.');
    setMode('index');
    recordSql('DELETE', `/posts/${post.id} (posts.destroy)`, 'DELETE FROM posts WHERE id = :p1', {
      ':p1': post.id,
    });
  };

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <div className="lg:col-span-7 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-5 py-3.5">
          <div>
            <h2 className="text-base font-semibold text-slate-900">
              Simulateur Web — PostController CRUD
            </h2>
            <p className="text-xs text-slate-500">
              Validation serveur · Jeton CSRF · Messages flash · Relations Eloquent belongsTo
            </p>
          </div>

          <div className="flex items-center gap-2">
            {mode !== 'index' && (
              <button
                type="button"
                onClick={() => {
                  setMode('index');
                  setFlashMessage(null);
                  recordSql('GET', '/posts (posts.index)', 'SELECT * FROM posts ORDER BY id DESC', []);
                }}
                className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap shrink-0"
              >
                Liste des articles
              </button>
            )}
            <button
              type="button"
              onClick={openCreate}
              className="inline-flex items-center gap-1.5 rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 whitespace-nowrap shrink-0"
            >
              <Plus className="h-3.5 w-3.5" />
              <span>Nouvel article</span>
            </button>
          </div>
        </div>

        {flashMessage && (
          <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {flashMessage}
          </div>
        )}

        {mode === 'index' && (
          <div className="space-y-3">
            {posts.length === 0 ? (
              <div className="rounded-lg border border-slate-200 bg-white p-10 text-center">
                <p className="text-sm text-slate-600">Aucun article n’a encore été publié.</p>
                <button
                  type="button"
                  onClick={openCreate}
                  className="mt-4 rounded-md bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800"
                >
                  Rédiger le premier article
                </button>
              </div>
            ) : (
              posts.map((post) => (
                <div key={post.id} className="rounded-lg border border-slate-200 bg-white p-5">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <h3 className="text-base font-semibold text-slate-900">{post.title}</h3>
                      <p className="mt-1 text-xs text-slate-500 tabular-nums">
                        Slug : {post.slug} · {post.createdAt} · {post.published ? 'Publié' : 'Brouillon'}
                      </p>
                    </div>
                  </div>
                  <p className="mt-3 text-sm text-slate-600">{post.content}</p>
                  <div className="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                    <button
                      type="button"
                      onClick={() => openShow(post)}
                      className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap shrink-0"
                    >
                      <Eye className="h-3.5 w-3.5" />
                      <span>Lire</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => openEdit(post)}
                      className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap shrink-0"
                    >
                      <Pencil className="h-3.5 w-3.5" />
                      <span>Modifier</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(post)}
                      className="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 whitespace-nowrap shrink-0"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                      <span>Supprimer</span>
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>
        )}

        {(mode === 'create' || mode === 'edit') && (
          <form
            onSubmit={mode === 'create' ? handleStore : handleUpdate}
            className="rounded-lg border border-slate-200 bg-white p-6 space-y-4"
          >
            <h3 className="text-base font-semibold text-slate-900">
              {mode === 'create' ? 'Créer un article (POST /posts)' : `Modifier l’article #${activePost?.id} (PUT /posts/${activePost?.id})`}
            </h3>

            <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-600">
              _token: {csrfToken} {mode === 'edit' ? '· _method: PUT' : ''}
            </div>

            <div>
              <label htmlFor="sim-title" className="block text-sm font-medium text-slate-800">
                Titre (required|string|min:3|max:120)
              </label>
              <input
                id="sim-title"
                type="text"
                value={titleInput}
                onChange={(e) => setTitleInput(e.target.value)}
                placeholder="Ex: Architecture du routeur Vortex"
                className="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none"
              />
              {errors.title && <p className="mt-1 text-xs font-medium text-red-600">{errors.title}</p>}
            </div>

            <div>
              <label htmlFor="sim-content" className="block text-sm font-medium text-slate-800">
                Contenu (required|string|min:10)
              </label>
              <textarea
                id="sim-content"
                rows={4}
                value={contentInput}
                onChange={(e) => setContentInput(e.target.value)}
                placeholder="Saisissez au moins 10 caractères pour passer la validation..."
                className="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none"
              />
              {errors.content && <p className="mt-1 text-xs font-medium text-red-600">{errors.content}</p>}
            </div>

            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                checked={publishedInput}
                onChange={(e) => setPublishedInput(e.target.checked)}
                className="rounded border-slate-300"
              />
              <span>Publier immédiatement</span>
            </label>

            <div className="flex items-center gap-3 pt-2">
              <button
                type="submit"
                className="rounded-md bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800"
              >
                {mode === 'create' ? 'Enregistrer' : 'Mettre à jour'}
              </button>
              <button
                type="button"
                onClick={() => setMode('index')}
                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
              >
                Annuler
              </button>
            </div>
          </form>
        )}

        {mode === 'show' && activePost && (
          <div className="rounded-lg border border-slate-200 bg-white p-6">
            <h3 className="text-xl font-semibold text-slate-900">{activePost.title}</h3>
            <p className="mt-1 text-xs text-slate-500 tabular-nums">
              Par {activePost.authorName} ({activePost.authorEmail}) · {activePost.createdAt}
            </p>
            <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-700">
              {activePost.content}
            </p>
            <div className="mt-6 flex items-center gap-2 border-t border-slate-100 pt-4">
              <button
                type="button"
                onClick={() => openEdit(activePost)}
                className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              >
                Modifier
              </button>
              <button
                type="button"
                onClick={() => handleDelete(activePost)}
                className="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
              >
                Supprimer
              </button>
            </div>
          </div>
        )}
      </div>

      <div className="lg:col-span-5 space-y-4">
        <div className="rounded-lg border border-slate-200 bg-white p-5">
          <h3 className="text-sm font-semibold text-slate-900">
            Requêtes PDO préparées exécutées
          </h3>
          <p className="mt-0.5 text-xs text-slate-500">
            Journal temps réel des instructions compilées par Mecene\Database\QueryBuilder
          </p>

          <div className="mt-4 divide-y divide-slate-100">
            {sqlHistory.map((item) => (
              <div key={item.id} className="py-3 first:pt-0 last:pb-0">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-mono font-semibold text-slate-900">
                    {item.method} {item.route}
                  </span>
                </div>
                <pre className="mt-1.5 overflow-x-auto rounded bg-slate-950 p-2.5 font-mono text-[11px] text-slate-200">
                  {item.sql}
                </pre>
                <p className="mt-1 truncate font-mono text-[11px] text-slate-500">
                  Bindings : {item.bindings}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
