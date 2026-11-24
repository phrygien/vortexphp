#!/usr/bin/env bash
set -euo pipefail

# Script de publication automatique des 3 packages Mecene vers GitHub
# Usage : GITHUB_OWNER=votre-compte VERSION=v1.0.0 bash publish.sh

GITHUB_OWNER="${GITHUB_OWNER:-mecene}"
VERSION="${VERSION:-v1.0.0}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Publication Mecene (${VERSION}) vers github.com/${GITHUB_OWNER}/* ==="

publish_package() {
  local pkg_dir="$1"
  local repo_name="$2"
  local repo_url="git@github.com:${GITHUB_OWNER}/${repo_name}.git"

  echo ""
  echo "--> Préparation de packages/${pkg_dir} -> ${repo_url}"

  local tmp_dir
  tmp_dir="$(mktemp -d)"
  cp -a "${ROOT_DIR}/packages/${pkg_dir}/." "${tmp_dir}/"

  (
    cd "${tmp_dir}"
    git init -q -b main
    git add .
    git commit -q -m "Release ${VERSION}"
    git tag -a "${VERSION}" -m "Release ${VERSION}"
    git remote add origin "${repo_url}"
    git push -u origin main --force
    git push origin "${VERSION}" --force
  )

  rm -rf "${tmp_dir}"
  echo "OK : https://github.com/${GITHUB_OWNER}/${repo_name} (${VERSION})"
}

publish_package "framework" "framework"
publish_package "app" "app"
publish_package "installer" "installer"

echo ""
echo "=== Étapes suivantes sur Packagist ==="
echo "1. Ouvrez https://packagist.org/packages/submit"
echo "2. Soumettez dans l'ordre :"
echo "   - https://github.com/${GITHUB_OWNER}/framework"
echo "   - https://github.com/${GITHUB_OWNER}/app"
echo "   - https://github.com/${GITHUB_OWNER}/installer"
echo "3. Testez l'installation :"
echo "   composer create-project ${GITHUB_OWNER}/app test-app"
