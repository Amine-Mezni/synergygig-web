#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "$ROOT_DIR"

git config user.name "Anas-Kordoghli"
git config user.email "anaskor03@gmail.com"

commit_if_changed() {
  local message="$1"
  shift

  git add -- "$@"

  if ! git diff --cached --quiet; then
    git commit -m "$message"
  fi
}

commit_if_changed "Update web project configuration" \
  web_synergygig/.gitignore \
  web_synergygig/.env.example \
  web_synergygig/composer.json \
  web_synergygig/composer.lock \
  web_synergygig/config \
  web_synergygig/docker-compose.yml \
  web_synergygig/phpstan.neon \
  web_synergygig/phpunit.xml.dist

commit_if_changed "Update web domain model" \
  web_synergygig/src/Entity \
  web_synergygig/src/Repository \
  web_synergygig/src/DTO \
  web_synergygig/migrations

commit_if_changed "Update web application logic" \
  web_synergygig/src/Controller \
  web_synergygig/src/Form \
  web_synergygig/src/Security \
  web_synergygig/src/Service \
  web_synergygig/src/EventListener \
  web_synergygig/src/Command \
  web_synergygig/src/Twig \
  web_synergygig/src/Kernel.php

commit_if_changed "Update web interface" \
  web_synergygig/templates \
  web_synergygig/public \
  web_synergygig/assets

commit_if_changed "Update web tests and tools" \
  web_synergygig/tests \
  web_synergygig/scripts \
  web_synergygig/test_*.php \
  web_synergygig/check_enums.php \
  web_synergygig/debug_form.php \
  web_synergygig/fix_entities.php \
  web_synergygig/list_tables.php \
  web_synergygig/reset_admin_password.php \
  web_synergygig/reverse-engineer.php \
  web_synergygig/_tmp_create_table.php \
  web_synergygig/seed_and_test.php

if ! git remote get-url synergygig-web >/dev/null 2>&1; then
  git remote add synergygig-web https://github.com/bouallegueMohamedSeji/synergygig-web.git
fi

git fetch synergygig-web hr
split_commit="$(git subtree split --prefix=web_synergygig HEAD)"
git push --force-with-lease=hr synergygig-web "${split_commit}:hr"
