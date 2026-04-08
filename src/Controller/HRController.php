<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\DepartmentRepository;
use App\Repository\AttendanceRepository;
use App\Repository\LeaveRepository;
use App\Repository\PayrollRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_HR')]
class HRController extends AbstractController
{
    private const PUBLIC_HOLIDAYS = [
        ['name' => 'New Year\'s Day', 'date' => '2026-01-01', 'type' => 'National'],
        ['name' => 'Revolution Day', 'date' => '2026-01-14', 'type' => 'National'],
        ['name' => 'Independence Day', 'date' => '2026-03-20', 'type' => 'National'],
        ['name' => 'Martyrs\' Day', 'date' => '2026-04-09', 'type' => 'National'],
        ['name' => 'Labour Day', 'date' => '2026-05-01', 'type' => 'National'],
        ['name' => 'Republic Day', 'date' => '2026-07-25', 'type' => 'National'],
        ['name' => 'Women\'s Day', 'date' => '2026-08-13', 'type' => 'National'],
        ['name' => 'Evacuation Day', 'date' => '2026-10-15', 'type' => 'National'],
    ];

    private const TEAM_BUILDING = [
        ['title' => 'Escape Room Challenge', 'desc' => 'Solve puzzles together to strengthen team bonds and communication skills.', 'icon' => '🧩'],
        ['title' => 'Cooking Workshop', 'desc' => 'Collaborative cooking session to build teamwork in a fun, informal setting.', 'icon' => '🍳'],
        ['title' => 'Outdoor Adventure', 'desc' => 'Hiking or obstacle course to encourage trust and cooperation.', 'icon' => '🏔️'],
        ['title' => 'Hackathon Day', 'desc' => 'Cross-team innovation sprint to solve real company challenges.', 'icon' => '💡'],
    ];

    #[Route('/hr', name: 'app_hr_dashboard')]
    public function index(
        UserRepository $userRepo,
        DepartmentRepository $deptRepo,
        AttendanceRepository $attendanceRepo,
        LeaveRepository $leaveRepo,
        PayrollRepository $payrollRepo,
    ): Response {
        $totalEmployees = $userRepo->count(['role' => 'EMPLOYEE']);
        $departments = $deptRepo->count([]);

        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $allAttendance = $attendanceRepo->findAll();
        $todayAttendance = array_filter($allAttendance, function ($a) use ($today, $tomorrow) {
            $d = $a->getDate();
            return $d && $d >= $today && $d < $tomorrow;
        });
        $presentToday = count($todayAttendance);

        $pendingLeaves = $leaveRepo->count(['status' => 'PENDING']);
        $pendingPayroll = $payrollRepo->count(['status' => 'PENDING']);

        $recentLeaves = $leaveRepo->findBy([], ['id' => 'DESC'], 5);

        $now = new \DateTime();
        $holidays = array_map(function ($h) use ($now) {
            $date = new \DateTime($h['date']);
            $diff = $now->diff($date);
            $h['date_obj'] = $date;
            $h['days_until'] = $diff->invert ? -$diff->days : $diff->days;
            return $h;
        }, self::PUBLIC_HOLIDAYS);
        $holidays = array_filter($holidays, fn($h) => $h['days_until'] >= 0);
        usort($holidays, fn($a, $b) => $a['days_until'] - $b['days_until']);
        $holidays = array_slice($holidays, 0, 4);

        $teamBuilding = self::TEAM_BUILDING[array_rand(self::TEAM_BUILDING)];

        return $this->render('hr/index.html.twig', [
            'stats' => [
                'employees' => $totalEmployees,
                'departments' => $departments,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves,
                'pending_payroll' => $pendingPayroll,
            ],
            'holidays' => $holidays,
            'team_building' => $teamBuilding,
            'recent_leaves' => $recentLeaves,
            'today_attendance' => array_slice(array_values($todayAttendance), 0, 5),
        ]);
    }

    #[Route('/hr/backlog', name: 'app_hr_backlog')]
    public function backlog(
        UserRepository $userRepo,
        DepartmentRepository $deptRepo,
        AttendanceRepository $attendanceRepo,
        LeaveRepository $leaveRepo,
        PayrollRepository $payrollRepo,
        TaskRepository $taskRepo,
    ): Response {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        // Stats
        $totalEmployees = $userRepo->count([]);
        $pendingLeaves = $leaveRepo->count(['status' => 'PENDING']);
        $allAttendance = $attendanceRepo->findAll();
        $presentToday = count(array_filter($allAttendance, function ($a) use ($today, $tomorrow) {
            $d = $a->getDate();
            return $d && $d >= $today && $d < $tomorrow;
        }));
        $pendingPayroll = $payrollRepo->count(['status' => 'PENDING']);
        $openTasks = $taskRepo->count(['status' => 'TODO']) + $taskRepo->count(['status' => 'IN_PROGRESS']);

        // Employees list
        $employees = $userRepo->findBy([], ['id' => 'DESC'], 20);

        // Pending leaves
        $pendingLeavesList = $leaveRepo->findBy(['status' => 'PENDING'], ['id' => 'DESC'], 10);

        // Open tasks
        $tasks = $taskRepo->findBy([], ['id' => 'DESC'], 15);

        // Today attendance
        $todayAttendanceList = array_values(array_filter($allAttendance, function ($a) use ($today, $tomorrow) {
            $d = $a->getDate();
            return $d && $d >= $today && $d < $tomorrow;
        }));

        // Department distribution for pie chart
        $departments = $deptRepo->findAll();
        $deptDistribution = [];
        foreach ($departments as $dept) {
            $count = $userRepo->count(['department' => $dept]);
            if ($count > 0) {
                $deptDistribution[$dept->getName()] = $count;
            }
        }

        // Leave type distribution for pie chart
        $allLeaves = $leaveRepo->findBy(['status' => 'APPROVED']);
        $leaveTypeDistribution = [];
        foreach ($allLeaves as $leave) {
            $type = $leave->getType() ?? 'OTHER';
            $leaveTypeDistribution[$type] = ($leaveTypeDistribution[$type] ?? 0) + 1;
        }

        return $this->render('hr/backlog.html.twig', [
            'stats' => [
                'employees' => $totalEmployees,
                'pending_leaves' => $pendingLeaves,
                'present_today' => $presentToday,
                'pending_payroll' => $pendingPayroll,
                'open_tasks' => $openTasks,
            ],
            'employees' => $employees,
            'pending_leaves_list' => $pendingLeavesList,
            'tasks' => $tasks,
            'today_attendance' => array_slice($todayAttendanceList, 0, 10),
            'dept_distribution' => $deptDistribution,
            'leave_type_distribution' => $leaveTypeDistribution,
        ]);
    }
}
