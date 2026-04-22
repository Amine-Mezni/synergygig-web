#!/bin/bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$REPO_DIR"

REMOTE="https://github.com/bouallegueMohamedSeji/synergygig-web.git"
BRANCH="authentifcation"
TARGET_ROOT="web_synergygig"

git config user.name "bouallegueMohamedSeji"
git config user.email "MohamedSeji.Bouallegue@esprit.tn"
git remote set-url origin "$REMOTE"

current_branch="$(git branch --show-current)"
if [ "$current_branch" != "$BRANCH" ]; then
    git checkout "$BRANCH"
fi

if git diff --quiet -- "$TARGET_ROOT" && git diff --cached --quiet -- "$TARGET_ROOT"; then
    echo "No changes detected under $TARGET_ROOT."
    exit 0
fi

commit_if_staged() {
    local message="$1"
    if ! git diff --cached --quiet -- "$TARGET_ROOT"; then
        git commit -m "$message"
    else
        echo "  nothing staged for: $message"
    fi
}

echo "Starting batched commits on $BRANCH"

echo "▸ Commit 1/6: auth config and security"
git add \
    "$TARGET_ROOT/composer.json" \
    "$TARGET_ROOT/config/" \
    "$TARGET_ROOT/src/EventListener/" \
    "$TARGET_ROOT/src/Security/" 2>/dev/null || true
commit_if_staged "chore(auth): update security wiring and service config"

echo "▸ Commit 2/6: user auth flow"
git add \
    "$TARGET_ROOT/src/Controller/AuthController.php" \
    "$TARGET_ROOT/src/Controller/ProfileController.php" \
    "$TARGET_ROOT/src/Controller/UserController.php" \
    "$TARGET_ROOT/src/Entity/User.php" \
    "$TARGET_ROOT/src/Form/RegistrationType.php" \
    "$TARGET_ROOT/src/Form/UserType.php" 2>/dev/null || true
commit_if_staged "feat(auth): refresh account and user management flow"

echo "▸ Commit 3/6: controllers and services"
git add \
    "$TARGET_ROOT/src/Controller/" \
    "$TARGET_ROOT/src/Service/" 2>/dev/null || true
commit_if_staged "feat(app): update controllers and supporting services"

echo "▸ Commit 4/6: entities and forms"
git add \
    "$TARGET_ROOT/src/Entity/" \
    "$TARGET_ROOT/src/Form/" 2>/dev/null || true
commit_if_staged "refactor(forms): align entities and forms with workflow changes"

echo "▸ Commit 5/6: auth and shared UI"
git add \
    "$TARGET_ROOT/templates/auth/" \
    "$TARGET_ROOT/templates/layouts/" \
    "$TARGET_ROOT/templates/profile/" \
    "$TARGET_ROOT/templates/user/" \
    "$TARGET_ROOT/public/css/" 2>/dev/null || true
commit_if_staged "ui(auth): refresh auth, profile, and shared layout views"

echo "▸ Commit 6/6: remaining web module updates"
git add "$TARGET_ROOT/" 2>/dev/null || true
commit_if_staged "feat(web): finalize module templates and training updates"

echo "Pushing $BRANCH to origin"
git push -u origin "$BRANCH"

echo "Push complete: $REMOTE ($BRANCH)"
