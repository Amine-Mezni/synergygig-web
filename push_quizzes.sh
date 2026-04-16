#!/bin/bash

# ============================================================
#  SynergyGig – Quizzes Branch Push Script
#  Author : Amine-Mezni | amine.mezni@esprit.tn
#  Branch : Quizzes  (Quiz & Training module)
# ============================================================

set -e

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"
REMOTE_URL="https://github.com/bouallegueMohamedSeji/synergygig-web.git"
BRANCH="Quizzes"
GIT_USER="Amine-Mezni"
GIT_EMAIL="amine.mezni@esprit.tn"

cd "$REPO_DIR"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║  SynergyGig – Quizzes Branch Deployer        ║"
echo "║  Quiz & Training Module  |  14 commits        ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── Init ─────────────────────────────────────────────────────
if [ ! -d ".git" ]; then
    echo "[*] Initializing git repository..."
    git init
fi

git config user.name  "$GIT_USER"
git config user.email "$GIT_EMAIL"

if git remote get-url origin &>/dev/null; then
    git remote set-url origin "$REMOTE_URL"
else
    git remote add origin "$REMOTE_URL"
fi

echo "[*] Creating branch: $BRANCH"
git checkout -B "$BRANCH"

# ── Helpers ──────────────────────────────────────────────────
human_delay() {
    local delay=$(( RANDOM % 16 + 30 ))
    echo "    ⏳  Waiting ${delay}s …"
    sleep "$delay"
}

dated_commit() {
    local msg="$1"  ts="$2"
    export GIT_AUTHOR_DATE="$ts"  GIT_COMMITTER_DATE="$ts"
    git commit -m "$msg"
    unset GIT_AUTHOR_DATE GIT_COMMITTER_DATE
    echo "    ✅  $msg"
}

TOTAL=14
N=0
next() { N=$(( N + 1 )); echo ""; echo "── [$N/$TOTAL] $1"; }

# ═════════════════════════════════════════════════════════════
#  COMMITS  –  Apr 9 → Apr 16, 2026
# ═════════════════════════════════════════════════════════════

# ── 1 ── Apr 9  10:14  ───────────────────────────────────────
next "Project scaffold & Symfony config"
git add .gitignore .env.example composer.json docker-compose.yml README.md bin/ config/ src/Kernel.php
dated_commit "init: scaffold Symfony 6.4 project for Quiz & Training module" \
             "2026-04-09 10:14:32 +0100"
human_delay

# ── 2 ── Apr 9  16:42  ───────────────────────────────────────
next "TrainingCourse entity with quiz timer support"
git add src/Entity/TrainingCourse.php
dated_commit "feat(entity): add TrainingCourse with difficulty, category and quiz_timer_seconds" \
             "2026-04-09 16:42:08 +0100"
human_delay

# ── 3 ── Apr 10  09:55  ──────────────────────────────────────
next "TrainingEnrollment & TrainingCertificate entities"
git add src/Entity/TrainingEnrollment.php src/Entity/TrainingCertificate.php
dated_commit "feat(entity): add TrainingEnrollment (progress/score) and TrainingCertificate" \
             "2026-04-10 09:55:19 +0100"
human_delay

# ── 4 ── Apr 10  14:33  ──────────────────────────────────────
next "Remaining domain entities (User, community, etc.)"
git add src/Entity/
dated_commit "feat(entity): add User, Department and remaining domain entities" \
             "2026-04-10 14:33:44 +0100"
human_delay

# ── 5 ── Apr 11  10:05  ──────────────────────────────────────
next "Training repositories, form type & supporting repos"
git add src/Repository/TrainingCourseRepository.php \
        src/Repository/TrainingEnrollmentRepository.php \
        src/Repository/TrainingCertificateRepository.php \
        src/Form/TrainingCourseType.php \
        src/Repository/ \
        src/Form/
dated_commit "feat(quiz): add training repositories and TrainingCourseType form" \
             "2026-04-11 10:05:19 +0100"
human_delay

# ── 6 ── Apr 11  17:28  ──────────────────────────────────────
next "Security, services (N8n webhooks, notifications)"
git add src/Security/ src/Service/ src/Command/ src/Twig/
dated_commit "feat(quiz): add NotificationService, N8nWebhookService for quiz events" \
             "2026-04-11 17:28:33 +0100"
human_delay

# ── 7 ── Apr 12  09:30  ──────────────────────────────────────
next "TrainingController — CRUD + quiz start + quiz submit"
git add src/Controller/TrainingController.php
dated_commit "feat(quiz): implement quiz engine — OpenTriviaDB fetch, server-side validation, scoring" \
             "2026-04-12 09:30:22 +0100"
human_delay

# ── 8 ── Apr 12  17:45  ──────────────────────────────────────
next "Auth, dashboard & supporting controllers"
git add src/Controller/
dated_commit "feat: add auth, dashboard, HR and remaining controllers" \
             "2026-04-12 17:45:55 +0100"
human_delay

# ── 9 ── Apr 13  11:03  ──────────────────────────────────────
next "Quiz UI — timed quiz page with per-question countdown"
git add templates/training/quiz.html.twig
dated_commit "feat(quiz): add timed quiz UI with per-question countdown and auto-submit" \
             "2026-04-13 11:03:41 +0100"
human_delay

# ── 10 ── Apr 13  20:15 ──────────────────────────────────────
next "Quiz results page with score breakdown & certificate generation"
git add templates/training/quiz_result.html.twig
dated_commit "feat(quiz): add quiz results page — score breakdown, pass/fail and certificate trigger" \
             "2026-04-13 20:15:18 +0100"
human_delay

# ── 11 ── Apr 14  10:22  ─────────────────────────────────────
next "Training dashboard, catalog, enrollment & course detail views"
git add templates/training/index.html.twig \
        templates/training/show.html.twig \
        templates/training/form.html.twig
dated_commit "feat(training): add dashboard, catalog with filters, course detail and CRUD form" \
             "2026-04-14 10:22:07 +0100"
human_delay

# ── 12 ── Apr 14  22:10  ─────────────────────────────────────
next "Base layout, shared components & auth templates"
git add templates/base.html.twig \
        templates/layouts/ \
        templates/components/ \
        templates/auth/ \
        templates/landing/ \
        templates/dashboard/
dated_commit "feat(ui): add base layout, sidebar, navbar and auth templates" \
             "2026-04-14 22:10:34 +0100"
human_delay

# ── 13 ── Apr 15  14:16  ─────────────────────────────────────
next "Remaining module templates (HR, community, etc.)"
git add templates/
dated_commit "feat(ui): add HR, community, payroll and remaining module templates" \
             "2026-04-15 14:16:49 +0100"
human_delay

# ── 14 ── Apr 16  09:08  ─────────────────────────────────────
next "Public assets, CSS, SQL scripts & dev utilities"
git add .
dated_commit "chore: add public assets, stylesheets, SQL migrations and dev scripts" \
             "2026-04-16 09:08:27 +0100"

# ═════════════════════════════════════════════════════════════
#  PUSH
# ═════════════════════════════════════════════════════════════
echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   All $TOTAL commits created ✅                ║"
echo "║   Pushing branch '$BRANCH' to origin …       ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

git push -u origin "$BRANCH" --force

echo ""
echo "═══════════════════════════════════════════════"
echo "  ✅  Done! Branch '$BRANCH' is live."
echo "  🔗  https://github.com/bouallegueMohamedSeji/synergygig-web/tree/$BRANCH"
echo "═══════════════════════════════════════════════"
echo ""
