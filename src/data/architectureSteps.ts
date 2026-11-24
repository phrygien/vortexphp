export interface ArchitectureStep {
  id: number;
  title: string;
  summary: string;
  files: string[];
}

export const ARCHITECTURE_STEPS: ArchitectureStep[] = [
  {
    id: 1,
    title: '01. Conteneur DI (PSR-11)',
    summary:
      'Conteneur PSR-11 dans mecene/framework résolvant automatiquement constructeurs et méthodes via ReflectionClass, avec support des bindings, singletons et détection de cycles.',
    files: [
      'packages/framework/composer.json',
      'packages/framework/src/Container/Container.php',
      'packages/framework/src/Container/ContainerException.php',
      'packages/framework/src/Container/NotFoundException.php',
    ],
  },
  {
    id: 2,
    title: '02. Application & Service Providers',
    summary:
      'Noyau Application totalement agnostique de l’application hôte : orchestre register() puis boot() sur les Service Providers configurés dans config/app.php.',
    files: [
      'packages/framework/src/Foundation/Application.php',
      'packages/framework/src/Support/ServiceProvider.php',
      'packages/app/bootstrap/app.php',
      'packages/app/app/Providers/AppServiceProvider.php',
      'packages/app/app/Providers/RouteServiceProvider.php',
    ],
  },
  {
    id: 3,
    title: '03. Configuration & .env',
    summary:
      'Chargement immuable de .env via vlucas/phpdotenv et dépôt Repository accessible par notation par points via env() et config().',
    files: [
      'packages/framework/src/Config/Repository.php',
      'packages/app/.env.example',
      'packages/app/config/app.php',
      'packages/app/config/database.php',
      'packages/app/config/view.php',
    ],
  },
  {
    id: 4,
    title: '04. Routeur HTTP',
    summary:
      'Routeur REST complet (GET/POST/PUT/PATCH/DELETE), groupes, préfixes, paramètres {id}, routes nommées et résolution par le conteneur.',
    files: [
      'packages/framework/src/Routing/Router.php',
      'packages/framework/src/Routing/Route.php',
      'packages/framework/src/Routing/Controller.php',
      'packages/app/routes/web.php',
    ],
  },
  {
    id: 5,
    title: '05. Pipeline de Middlewares',
    summary:
      'Exécution en oignon des middlewares globaux et par route via array_reduce, avec résolution automatique des classes par le conteneur DI.',
    files: [
      'packages/framework/src/Http/Middleware/MiddlewareInterface.php',
      'packages/framework/src/Http/Middleware/Pipeline.php',
      'packages/app/app/Middlewares/LogRequestMiddleware.php',
    ],
  },
  {
    id: 6,
    title: '06. Request & Response (PSR-7)',
    summary:
      'Couche HTTP adossée à nyholm/psr7 avec capture des superglobales, payloads JSON, _method spoofing et réponses HTML, JSON, redirect ou view.',
    files: [
      'packages/framework/src/Http/Request.php',
      'packages/framework/src/Http/Response.php',
      'packages/app/public/index.php',
    ],
  },
  {
    id: 7,
    title: '07. Moteur de templates Mecene',
    summary:
      'Compilateur MeceneCompiler (.mecene.php) vers PHP natif mis en cache gérant @extends, @section, @yield, @include, {{ }} échappé XSS, @if, @foreach, @forelse, @csrf et @error.',
    files: [
      'packages/framework/src/View/MeceneCompiler.php',
      'packages/framework/src/View/ViewFactory.php',
      'packages/app/resources/views/layouts/app.mecene.php',
      'packages/app/resources/views/partials/flash.mecene.php',
    ],
  },
  {
    id: 8,
    title: '08. Query Builder PDO',
    summary:
      'Constructeur fluent (select, where, whereIn, join, orderBy, limit, insert, update, delete) compilant des requêtes préparées pour SQLite, MySQL et PostgreSQL.',
    files: [
      'packages/framework/src/Database/Connection.php',
      'packages/framework/src/Database/QueryBuilder.php',
    ],
  },
  {
    id: 9,
    title: '09. ORM type Eloquent',
    summary:
      'Modèle ActiveRecord avec $fillable, timestamps automatiques, CRUD statique et relations hasMany / belongsTo illustrées par User et Post.',
    files: [
      'packages/framework/src/Database/ORM/Model.php',
      'packages/app/app/Models/User.php',
      'packages/app/app/Models/Post.php',
    ],
  },
  {
    id: 10,
    title: '10. Migrations & Schema Builder',
    summary:
      'Définition fluide des tables via Blueprint et suivi par lots (batches) dans Migrator avec support complet de --rollback.',
    files: [
      'packages/framework/src/Database/Schema/Schema.php',
      'packages/framework/src/Database/Schema/Blueprint.php',
      'packages/framework/src/Database/Migrations/Migration.php',
      'packages/framework/src/Database/Migrations/Migrator.php',
      'packages/app/database/migrations/2026_01_01_000001_create_users_table.php',
      'packages/app/database/migrations/2026_01_01_000002_create_posts_table.php',
    ],
  },
  {
    id: 11,
    title: '11. Validation des données',
    summary:
      'Règles required, string, integer, numeric, email, min, max, in, confirmed et unique:table,col avec redirection + old() ou JSON 422.',
    files: [
      'packages/framework/src/Validation/Validator.php',
      'packages/framework/src/Validation/ValidationException.php',
    ],
  },
  {
    id: 12,
    title: '12. Sessions, CSRF & Flash',
    summary:
      'Cookies HttpOnly/SameSite=Lax, vieillissement automatique des messages flash entre requêtes et protection CSRF vérifiée par hash_equals.',
    files: [
      'packages/framework/src/Session/SessionManager.php',
      'packages/framework/src/Http/Middleware/StartSession.php',
      'packages/framework/src/Http/Middleware/VerifyCsrfToken.php',
    ],
  },
  {
    id: 13,
    title: '13. Console Artisan & Stubs',
    summary:
      'CLI basée sur symfony/console (make:controller, make:model, make:migration via stubs/, migrate, serve, key:generate). Le fichier artisan du squelette délègue au Kernel.',
    files: [
      'packages/app/artisan',
      'packages/framework/src/Console/Kernel.php',
      'packages/framework/src/Console/Commands/KeyGenerateCommand.php',
      'packages/framework/src/Console/Commands/MakeControllerCommand.php',
      'packages/framework/src/Console/Commands/MakeModelCommand.php',
      'packages/framework/src/Console/Commands/MakeMigrationCommand.php',
      'packages/framework/src/Console/Commands/MigrateCommand.php',
      'packages/framework/src/Console/Commands/ServeCommand.php',
      'packages/framework/stubs/controller.stub',
      'packages/framework/stubs/model.stub',
      'packages/framework/stubs/migration.stub',
    ],
  },
  {
    id: 14,
    title: '14. Exceptions & Page Debug',
    summary:
      'Gestion centralisée des erreurs HTTP/validation et rendu d’une page de diagnostic détaillée (extrait source + pile d’appels) si APP_DEBUG=true.',
    files: [
      'packages/framework/src/Exceptions/HttpException.php',
      'packages/framework/src/Exceptions/Handler.php',
    ],
  },
  {
    id: 15,
    title: '15. Helpers globaux & Démo CRUD',
    summary:
      'Fonctions globales autoloadées par mecene/framework et mini-application CRUD Posts complète dans le squelette mecene/app.',
    files: [
      'packages/framework/src/Foundation/helpers.php',
      'packages/app/app/Controllers/PostController.php',
      'packages/app/resources/views/posts/index.mecene.php',
      'packages/app/resources/views/posts/create.mecene.php',
      'packages/app/resources/views/posts/show.mecene.php',
      'packages/app/resources/views/posts/edit.mecene.php',
    ],
  },
  {
    id: 16,
    title: '16. Distribution Packagist & Installer',
    summary:
      'Packaging complet en 3 dépôts (framework, app avec scripts post-create-project-cmd + exemple path, et binaire global mecene/installer) prêt pour Packagist.',
    files: [
      'packages/app/composer.json',
      'packages/app/composer.local.json',
      'packages/installer/composer.json',
      'packages/installer/bin/mecene',
      'packages/installer/src/NewCommand.php',
      'GUIDE_PUBLICATION.md',
    ],
  },
];
