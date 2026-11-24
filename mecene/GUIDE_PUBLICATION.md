# Guide de publication sur Packagist & Développement local

Ce monorepo contient les 3 packages officiels du micro-framework **Mecene** :

- `packages/framework` (`mecene/framework`) — Cœur du framework (`type: library`)
- `packages/app` (`mecene/app`) — Squelette applicatif (`type: project`)
- `packages/installer` (`mecene/installer`) — Binaire global `mecene` (`type: library`, `bin`)

---

## 0. Développement local avant publication (Repository `path`)

Avant que les packages ne soient publiés sur Packagist, utilisez `packages/app/composer.local.json` (ou ajoutez la section `repositories` dans `packages/app/composer.json`) afin que Composer lie `../framework` par lien symbolique :

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../framework",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "php": "^8.3",
    "mecene/framework": "@dev"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Test local immédiat :

```bash
cd packages/app
COMPOSER=composer.local.json composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

---

## 1. Découpage en 3 dépôts GitHub (ou split du monorepo avec `splitsh-lite`)

Créez trois dépôts publics sur GitHub :
- `github.com/mecene/framework`
- `github.com/mecene/app`
- `github.com/mecene/installer`

Depuis la racine du monorepo, vous pouvez publier chaque sous-dossier vers son dépôt dédié avec `splitsh-lite` :

```bash
git init
git add .
git commit -m "feat: initial release of Mecene 1.0.0"

# Envoi des sous-arborescences vers les 3 dépôts GitHub
git remote add framework git@github.com:mecene/framework.git
git remote add app git@github.com:mecene/app.git
git remote add installer git@github.com:mecene/installer.git

splitsh-lite --prefix=packages/framework --target=refs/heads/split-framework
git push framework split-framework:main

splitsh-lite --prefix=packages/app --target=refs/heads/split-app
git push app split-app:main

splitsh-lite --prefix=packages/installer --target=refs/heads/split-installer
git push installer split-installer:main
```

*(Alternative sans `splitsh-lite` : `git subtree push --prefix=packages/framework framework main`.)*

---

## 2. Tag Git sémantique `v1.0.0`

Packagist utilise les tags Git conformes au versioning sémantique (`v1.0.0`, `v1.0.1`, etc.) pour résoudre `"mecene/framework": "^1.0"` :

```bash
# Dans chaque dépôt (ou sur chaque branche splittée)
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

Publiez **`mecene/framework` en premier** afin que `mecene/app` trouve la contrainte `^1.0` lors de sa soumission.

---

## 3. Soumission sur Packagist & Webhook GitHub automatique

1. Connectez-vous sur [https://packagist.org](https://packagist.org) avec votre compte GitHub.
2. Cliquez sur **Submit** et soumettez successivement :
   - `https://github.com/mecene/framework`
   - `https://github.com/mecene/app`
   - `https://github.com/mecene/installer`
3. Dans les paramètres Packagist de votre profil, récupérez votre **API Token** et activez l'intégration **GitHub App / Webhook** (`https://packagist.org/api/github?username=<votre-user>`) dans **Settings -> Webhooks** de chaque dépôt GitHub : chaque nouveau `git push` ou `git tag` déclenchera automatiquement la mise à jour sur Packagist.

---

## 4. Test final sur une machine propre

Une fois les 3 packages indexés sur Packagist :

```bash
# Test via Composer create-project (mode standard)
composer create-project mecene/app test-app
cd test-app
php artisan migrate
php artisan serve
```

Et avec l'installateur global :

```bash
composer global require mecene/installer
mecene new test-app-cli
cd test-app-cli
php artisan migrate
php artisan serve
```
