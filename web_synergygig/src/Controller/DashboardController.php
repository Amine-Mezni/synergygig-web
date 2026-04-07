<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\DepartmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\OfferRepository;
use App\Repository\ContractRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\NotificationRepository;
use App\Repository\PostRepository;
use App\Repository\TaskRepository;
use App\Repository\TrainingCourseRepository;
use App\Repository\InterviewRepository;
use App\Repository\AttendanceRepository;
use App\Repository\LeaveRepository;
use App\Repository\PayrollRepository;
use App\Repository\ChatRoomRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private const QUOTES = [
        ['text' => 'Have a vision. Be demanding.', 'author' => 'Colin Powell'],
        ['text' => 'The only way to do great work is to love what you do.', 'author' => 'Steve Jobs'],
        ['text' => 'Innovation distinguishes between a leader and a follower.', 'author' => 'Steve Jobs'],
        ['text' => 'Success is not final, failure is not fatal: it is the courage to continue that counts.', 'author' => 'Winston Churchill'],
        ['text' => 'The best time to plant a tree was 20 years ago. The second best time is now.', 'author' => 'Chinese Proverb'],
        ['text' => 'Quality is not an act, it is a habit.', 'author' => 'Aristotle'],
        ['text' => 'Alone we can do so little; together we can do so much.', 'author' => 'Helen Keller'],
        ['text' => 'The greatest glory in living lies not in never falling, but in rising every time we fall.', 'author' => 'Nelson Mandela'],
    ];

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepo,
        DepartmentRepository $deptRepo,
        ProjectRepository $projectRepo,
        OfferRepository $offerRepo,
        ContractRepository $contractRepo,
        JobApplicationRepository $appRepo,
        NotificationRepository $notifRepo,
        PostRepository $postRepo,
        TaskRepository $taskRepo,
        TrainingCourseRepository $trainingRepo,
        InterviewRepository $interviewRepo,
        AttendanceRepository $attendanceRepo,
        LeaveRepository $leaveRepo,
        PayrollRepository $payrollRepo,
        ChatRoomRepository $chatRoomRepo,
        MessageRepository $messageRepo,
    ): Response {
        $totalUsers = $userRepo->count([]);
        $employees = $userRepo->count(['role' => 'EMPLOYEE']);
        $gig = $userRepo->count(['role' => 'MANAGER']);
        $interviews = $interviewRepo->count(['status' => 'PENDING']);

        $quote = self::QUOTES[array_rand(self::QUOTES)];

        // Chart data: tasks by status
        $taskStatuses = ['TODO', 'IN_PROGRESS', 'SUBMITTED', 'DONE'];
        $taskChart = [];
        foreach ($taskStatuses as $s) {
            $taskChart[$s] = $taskRepo->count(['status' => $s]);
        }

        // Chart data: leaves by status
        $leaveStatuses = ['PENDING', 'APPROVED', 'REJECTED'];
        $leaveChart = [];
        foreach ($leaveStatuses as $s) {
            $leaveChart[$s] = $leaveRepo->count(['status' => $s]);
        }

        // Chart data: users by role
        $roles = ['ADMIN', 'HR', 'MANAGER', 'EMPLOYEE'];
        $roleChart = [];
        foreach ($roles as $r) {
            $roleChart[$r] = $userRepo->count(['role' => $r]);
        }

        // Chart data: offers by status
        $offerStatuses = ['DRAFT', 'OPEN', 'CLOSED', 'CANCELLED'];
        $offerChart = [];
        foreach ($offerStatuses as $s) {
            $offerChart[$s] = $offerRepo->count(['status' => $s]);
        }

        // Chart data: applications by status
        $appStatuses = ['PENDING', 'REVIEWED', 'ACCEPTED', 'REJECTED'];
        $appChart = [];
        foreach ($appStatuses as $s) {
            $appChart[$s] = $appRepo->count(['status' => $s]);
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'users' => $totalUsers,
                'employees' => $employees,
                'gig_workers' => $gig,
                'interviews' => $interviews,
                'departments' => $deptRepo->count([]),
                'projects' => $projectRepo->count([]),
                'offers' => $offerRepo->count([]),
                'offers_open' => $offerRepo->count(['status' => 'OPEN']),
                'offers_draft' => $offerRepo->count(['status' => 'DRAFT']),
                'contracts' => $contractRepo->count([]),
                'applications' => $appRepo->count([]),
                'apps_pending' => $appRepo->count(['status' => 'PENDING']),
                'apps_accepted' => $appRepo->count(['status' => 'ACCEPTED']),
                'tasks' => $taskRepo->count([]),
                'training' => $trainingRepo->count([]),
                'chat_rooms' => $chatRoomRepo->count([]),
                'messages' => $messageRepo->count([]),
                'pending_leaves' => $leaveRepo->count(['status' => 'PENDING']),
                'pending_payroll' => $payrollRepo->count(['status' => 'PENDING']),
            ],
            'taskChart' => $taskChart,
            'leaveChart' => $leaveChart,
            'roleChart' => $roleChart,
            'offerChart' => $offerChart,
            'appChart' => $appChart,
            'quote' => $quote,
            'recent_users' => $userRepo->findBy([], ['id' => 'DESC'], 5),
            'recent_departments' => $deptRepo->findBy([], ['id' => 'DESC'], 5),
        ]);
    }
}
