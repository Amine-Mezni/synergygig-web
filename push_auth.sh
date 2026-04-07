#!/bin/bash
# ─── push_auth.sh ──────────────────────────────────────────────
# Commits the latest web_synergygig changes to the authentifcation
# branch in 6 human-paced commits with random delays (30-45s).
# ────────────────────────────────────────────────────────────────
set -e

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$REPO_DIR"

# ── Git identity ──
git config user.name  "bouallegueMohamedSeji"
git config user.email "MohamedSeji.Bouallegue@esprit.tn"

REMOTE="https://github.com/bouallegueMohamedSeji/synergygig-web.git"
BRANCH="authentifcation"

# ── Helper: random sleep 30-45s ──
human_pause() {
    local delay=$(( RANDOM % 16 + 30 ))
    echo "  ⏳  waiting ${delay}s ..."
    sleep "$delay"
}

# ── 1. Fast backup via tar (skip vendor, var, .env, composer.lock) ──
echo "▸ Backing up web_synergygig/ ..."
BACKUP="/tmp/web_backup_$$.tar"
tar cf "$BACKUP" \
    --exclude='web_synergygig/vendor' \
    --exclude='web_synergygig/var' \
    --exclude='web_synergygig/.env' \
    --exclude='web_synergygig/.env.local' \
    --exclude='web_synergygig/composer.lock' \
    web_synergygig/
echo "  backup done ($(du -h "$BACKUP" | cut -f1))"

# ── 2. Force-checkout authentifcation ──
echo "▸ Switching to $BRANCH ..."
git checkout -f "$BRANCH"

# ── 3. Extract backup over tracked files ──
echo "▸ Syncing latest web files ..."
rm -rf web_synergygig/src web_synergygig/templates web_synergygig/config \
       web_synergygig/public web_synergygig/bin web_synergygig/python
tar xf "$BACKUP"
rm -f "$BACKUP"
echo "  files synced."

# ── 4. Grouped commits ──
echo ""
echo "════════════════════════════════════════"
echo "  Starting commits on $BRANCH"
echo "════════════════════════════════════════"
echo ""

# --- Commit 1: Entity & config ---
echo "▸ Commit 1/6: Entity & config changes"
git add web_synergygig/src/Entity/ 2>/dev/null || true
git add web_synergygig/config/ 2>/dev/null || true
git add web_synergygig/composer.json 2>/dev/null || true
git add web_synergygig/.gitignore 2>/dev/null || true
git add web_synergygig/.env.example 2>/dev/null || true
git add web_synergygig/docker-compose.yml 2>/dev/null || true
git add web_synergygig/bin/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "feat(entity): add reset_token fields, mailer config, security access rules"
human_pause

# --- Commit 2: Auth controller + forms ---
echo "▸ Commit 2/6: Auth controller & password reset flow"
git add web_synergygig/src/Controller/AuthController.php 2>/dev/null || true
git add web_synergygig/src/Controller/ProfileController.php 2>/dev/null || true
git add web_synergygig/src/Form/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "feat(auth): forgot-password OTP flow, reset via email, signup role choice"
human_pause

# --- Commit 3: Other controllers ---
echo "▸ Commit 3/6: Controllers – role guards & data filtering"
git add web_synergygig/src/Controller/ 2>/dev/null || true
git add web_synergygig/src/Command/ 2>/dev/null || true
git add web_synergygig/src/Repository/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "fix(controllers): role-based access guards, per-user data filtering"
human_pause

# --- Commit 4: Auth templates ---
echo "▸ Commit 4/6: Auth templates & UI upgrade"
git add web_synergygig/templates/auth/ 2>/dev/null || true
git add web_synergygig/templates/layouts/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "ui(auth): upgraded login/signup pages, forgot-password & OTP verify templates"
human_pause

# --- Commit 5: Remaining templates ---
echo "▸ Commit 5/6: Dashboard, admin & page templates"
git add web_synergygig/templates/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "ui(templates): dashboard chart fix, interview visibility, sidebar updates"
human_pause

# --- Commit 6: Everything else ---
echo "▸ Commit 6/6: Assets, scripts & remaining files"
git add web_synergygig/ 2>/dev/null || true
git add -A 2>/dev/null || true
git diff --cached --quiet || git commit -m "chore: static assets, python helpers, public resources"

echo ""
echo "════════════════════════════════════════"
echo "  All commits done. Pushing ..."
echo "════════════════════════════════════════"
echo ""

# ── 5. Push ──
git remote set-url origin "$REMOTE" 2>/dev/null || true
git push -u origin "$BRANCH"

echo ""
echo "✅  Pushed to $REMOTE ($BRANCH)"
echo ""

# ── 6. Switch back ──
git checkout user 2>/dev/null || true
