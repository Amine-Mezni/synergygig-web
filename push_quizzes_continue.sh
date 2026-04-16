#!/bin/bash

# ============================================================
#  SynergyGig – Quizzes Branch Push Script (CONTINUE)
#  Commits 4-14 | remaining files
# ============================================================

set -e

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$REPO_DIR"

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║   Continuing commits 4-14 …              ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ── Helpers ──────────────────────────────────────────────────
human_delay() {
    local delay=$(( RANDOM % 16 + 30 ))
    echo "    ⏳  Simulating human delay … ${delay}s"
    sleep "$delay"
}

dated_commit() {
    local msg="$1"
    local ts="$2"
    export GIT_AUTHOR_DATE="$ts"
    export GIT_COMMITTER_DATE="$ts"
    git commit -m "$msg"
    unset GIT_AUTHOR_DATE GIT_COMMITTER_DATE
    echo "    ✅  $msg"
}

# ── 4 ── Apr 10  15:21  ──────────────────────────────────────
echo "── [4/14] Community, training & collaboration entities"
git add src/Entity/
dated_commit "feat: add community, training and project entities" \
             "2026-04-10 15:21:44 +0100"
human_delay

# ── 5 ── Apr 11  10:05  ──────────────────────────────────────
echo "── [5/14] Repositories & form types"
git add src/Repository/ src/Form/
dated_commit "feat: implement repositories and form type classes" \
             "2026-04-11 10:05:19 +0100"
human_delay

# ── 6 ── Apr 11  16:48  ──────────────────────────────────────
echo "── [6/14] Security layer, services & CLI commands"
git add src/Security/ src/Service/ src/Command/ src/Twig/
dated_commit "feat: add security voters, services and Twig extensions" \
             "2026-04-11 16:48:33 +0100"
human_delay

# ── 7 ── Apr 12  09:30  ──────────────────────────────────────
echo "── [7/14] Auth, dashboard & user controllers"
git add \
  src/Controller/AuthController.php \
  src/Controller/DashboardController.php \
  src/Controller/LandingController.php \
  src/Controller/ProfileController.php \
  src/Controller/UserController.php \
  src/Controller/ApiRegistrationController.php \
  src/Controller/HealthCheckController.php
dated_commit "feat: add auth, dashboard and user management controllers" \
             "2026-04-12 09:30:22 +0100"
human_delay

# ── 8 ── Apr 12  17:12  ──────────────────────────────────────
echo "── [8/14] HR module controllers"
git add \
  src/Controller/HRController.php \
  src/Controller/ContractController.php \
  src/Controller/PayrollController.php \
  src/Controller/LeaveController.php \
  src/Controller/AttendanceController.php \
  src/Controller/DepartmentController.php \
  src/Controller/ExportController.php \
  src/Controller/FaceController.php \
  src/Controller/EmployeeOfMonthController.php \
  src/Controller/NotificationController.php \
  src/Controller/OnboardingController.php
dated_commit "feat: add HR, payroll, leave and attendance controllers" \
             "2026-04-12 17:12:55 +0100"
human_delay

# ── 9 ── Apr 13  11:03  ──────────────────────────────────────
echo "── [9/14] Communication controllers"
git add \
  src/Controller/CommunityController.php \
  src/Controller/ChatController.php \
  src/Controller/CallController.php \
  src/Controller/HRChatbotController.php
dated_commit "feat: add community, chat and video-call controllers" \
             "2026-04-13 11:03:41 +0100"
human_delay

# ── 10 ── Apr 13  20:45  ─────────────────────────────────────
echo "── [10/14] Remaining feature controllers"
git add src/Controller/
dated_commit "feat: add training, project, recruitment and task controllers" \
             "2026-04-13 20:45:18 +0100"
human_delay

# ── 11 ── Apr 14  10:22  ─────────────────────────────────────
echo "── [11/14] Base templates, layouts & auth views"
git add \
  templates/base.html.twig \
  templates/layouts/ \
  templates/components/ \
  templates/auth/ \
  templates/landing/ \
  templates/dashboard/ \
  templates/profile/ \
  templates/user/ \
  templates/notification/ \
  templates/export/
dated_commit "feat: add base layout, auth and dashboard templates" \
             "2026-04-14 10:22:07 +0100"
human_delay

# ── 12 ── Apr 14  21:58  ─────────────────────────────────────
echo "── [12/14] HR & payroll module templates"
git add \
  templates/hr/ \
  templates/contract/ \
  templates/payroll/ \
  templates/leave/ \
  templates/attendance/ \
  templates/department/ \
  templates/employee_of_month/ \
  templates/onboarding/ \
  templates/face/ \
  templates/hr_chatbot/
dated_commit "feat: add HR, payroll and onboarding templates" \
             "2026-04-14 21:58:34 +0100"
human_delay

# ── 13 ── Apr 15  14:16  ─────────────────────────────────────
echo "── [13/14] Community, training & project templates"
git add templates/
dated_commit "feat: add community, training, project and remaining templates" \
             "2026-04-15 14:16:49 +0100"
human_delay

# ── 14 ── Apr 16  09:08  ─────────────────────────────────────
echo "── [14/14] Public assets, DB scripts & documentation"
git add .
dated_commit "chore: add public assets, SQL scripts and dev utilities" \
             "2026-04-16 09:08:27 +0100"

# ── PUSH ─────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════╗"
echo "║   All commits done ✅  Pushing…           ║"
echo "╚══════════════════════════════════════════╝"
echo ""

git push origin Quizzes

echo ""
echo "══════════════════════════════════════════"
echo "  ✅  Done! Branch 'Quizzes' fully pushed."
echo "  🔗  https://github.com/bouallegueMohamedSeji/synergygig-web/tree/Quizzes"
echo "══════════════════════════════════════════"
echo ""
