# Mecene — Monorepo PHP 8.3+ (Framework, App, Installer)

Micro-framework PHP 8.3+ inspiré de Laravel, conçu pour être distribué via Packagist en 3 packages indépendants :

| Package | Type | Rôle |
|---|---|---|
| `mecene/framework` | `library` | Cœur complet : DI PSR-11, Routeur, Middlewares, PSR-7, Mecene, Query Builder, ORM, Migrations, Validation, Sessions/CSRF, CLI Artisan, Exceptions, Helpers & Stubs |
| `mecene/app` | `project` | Squelette applicatif prêt pour `composer create-project mecene/app mon-projet` avec démo CRUD Posts |
| `mecene/installer` | `library` (`bin`) | Installateur global exposant la commande `mecene new mon-projet` |

Consultez [`GUIDE_PUBLICATION.md`](GUIDE_PUBLICATION.md) pour le workflow complet de développement local (`repositories` de type `path`), split GitHub, tagging `v1.0.0` et soumission Packagist.
