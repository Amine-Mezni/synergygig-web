<?php

namespace App\Controller;

use App\Entity\Payroll;
use App\Entity\User;
use App\Form\PayrollType;
use App\Repository\AttendanceRepository;
use App\Repository\PayrollRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\N8nWebhookService;
use App\Service\NotificationService;

#[Route('/payroll')]
#[IsGranted('ROLE_HR')]
class PayrollController extends AbstractController
{
    #[Route('/', name: 'app_payroll_index')]
    public function index(Request $request, PayrollRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('p')->orderBy('p.id', 'DESC');

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        return $this->render('payroll/index.html.twig', [
            'payrolls' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_payroll_new')]
    #[IsGranted('ROLE_HR')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $payroll = new Payroll();
        $form = $this->createForm(PayrollType::class, $payroll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payroll->setGeneratedAt(new \DateTime());
            $em->persist($payroll);
            $em->flush();
            $this->addFlash('success', 'Payroll record created.');
            return $this->redirectToRoute('app_payroll_index');
        }

        return $this->render('payroll/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/generate', name: 'app_payroll_generate')]
    #[IsGranted('ROLE_HR')]
    public function generate(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        AttendanceRepository $attendanceRepo,
        PayrollRepository $payrollRepo,
        N8nWebhookService $n8n,
        NotificationService $notifier
    ): Response {
        $users = $userRepo->findBy(['is_active' => true]);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('payroll_generate', $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_payroll_generate');
            }

            $userId = (int) $request->request->get('user_id');
            $month = (int) $request->request->get('month');
            $year = (int) $request->request->get('year');
            $bonusInput = (float) $request->request->get('bonus', 0);
            $deductionsInput = (float) $request->request->get('deductions', 0);

            // Validation
            if ($month < 1 || $month > 12) {
                $this->addFlash('error', 'Month must be between 1 and 12.');
                return $this->redirectToRoute('app_payroll_generate');
            }
            if ($year < 2020 || $year > 2099) {
                $this->addFlash('error', 'Year must be between 2020 and 2099.');
                return $this->redirectToRoute('app_payroll_generate');
            }
            if ($bonusInput < 0) {
                $this->addFlash('error', 'Bonus cannot be negative.');
                return $this->redirectToRoute('app_payroll_generate');
            }
            if ($deductionsInput < 0) {
                $this->addFlash('error', 'Deductions cannot be negative.');
                return $this->redirectToRoute('app_payroll_generate');
            }

            $user = $userRepo->find($userId);
            if (!$user) {
                $this->addFlash('error', 'Employee not found.');
                return $this->redirectToRoute('app_payroll_generate');
            }

            // Check duplicate
            $existing = $payrollRepo->findOneBy(['user' => $user, 'month' => $month, 'year' => $year]);
            if ($existing) {
                $this->addFlash('error', sprintf(
                    'Payroll already exists for %s %s (%s/%d). Edit or delete the existing record first.',
                    $user->getFirstName(), $user->getLastName(), $month, $year
                ));
                return $this->redirectToRoute('app_payroll_generate');
            }

            // Calculate hours from attendance
            $totalHours = $this->calculateMonthlyHours($attendanceRepo, $user, $month, $year);
            $hourlyRate = $user->getHourlyRate() ?? 0;
            $monthlySalary = $user->getMonthlySalary() ?? 0;

            // Base salary: use monthly salary if set, otherwise hourlyRate * hours
            if ($monthlySalary > 0) {
                $baseSalary = $monthlySalary;
            } else {
                $baseSalary = $hourlyRate * $totalHours;
            }

            $netSalary = $baseSalary + $bonusInput - $deductionsInput;

            $payroll = new Payroll();
            $payroll->setUser($user);
            $payroll->setMonth($month);
            $payroll->setYear($year);
            $payroll->setBaseSalary((string) round($baseSalary, 2));
            $payroll->setBonus((string) round($bonusInput, 2));
            $payroll->setDeductions((string) round($deductionsInput, 2));
            $payroll->setNetSalary((string) round($netSalary, 2));
            $payroll->setAmount((string) round($netSalary, 2));
            $payroll->setTotalHoursWorked(round($totalHours, 2));
            $payroll->setHourlyRate($hourlyRate);
            $payroll->setStatus('PENDING');
            $payroll->setGeneratedAt(new \DateTime());

            $em->persist($payroll);
            $em->flush();

            $n8n->payrollGenerated(
                $user->getId(),
                $user->getFirstName() . ' ' . $user->getLastName(),
                $month . '/' . $year,
                $netSalary
            );

            $notifier->payrollGenerated($user, $payroll->getId(), $month . '/' . $year, $netSalary);

            $this->addFlash('success', sprintf(
                'Payroll generated for %s %s — Net: %.2f',
                $user->getFirstName(), $user->getLastName(), $netSalary
            ));
            return $this->redirectToRoute('app_payroll_show', ['id' => $payroll->getId()]);
        }

        return $this->render('payroll/generate.html.twig', [
            'users' => $users,
            'current_month' => (int) date('m'),
            'current_year' => (int) date('Y'),
        ]);
    }

    #[Route('/{id}', name: 'app_payroll_show', requirements: ['id' => '\d+'])]
    public function show(Payroll $payroll): Response
    {
        return $this->render('payroll/show.html.twig', [
            'payroll' => $payroll,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payroll_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_HR')]
    public function edit(Request $request, Payroll $payroll, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PayrollType::class, $payroll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Payroll record updated.');
            return $this->redirectToRoute('app_payroll_index');
        }

        return $this->render('payroll/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'payroll' => $payroll,
        ]);
    }

    #[Route('/{id}/mark-paid', name: 'app_payroll_mark_paid', methods: ['POST'])]
    public function markPaid(Request $request, Payroll $payroll, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('pay' . $payroll->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_payroll_show', ['id' => $payroll->getId()]);
        }

        if ($payroll->getStatus() === 'PAID') {
            $this->addFlash('error', 'This payroll is already marked as paid.');
            return $this->redirectToRoute('app_payroll_show', ['id' => $payroll->getId()]);
        }

        $payroll->setStatus('PAID');
        $em->flush();
        $this->addFlash('success', 'Payroll marked as paid.');
        return $this->redirectToRoute('app_payroll_show', ['id' => $payroll->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_payroll_delete', methods: ['POST'])]
    #[IsGranted('ROLE_HR')]
    public function delete(Request $request, Payroll $payroll, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $payroll->getId(), $request->request->get('_token'))) {
            $em->remove($payroll);
            $em->flush();
            $this->addFlash('success', 'Payroll record deleted.');
        }
        return $this->redirectToRoute('app_payroll_index');
    }

    /**
     * Sum total hours worked from attendance for a user in a specific month/year.
     */
    private function calculateMonthlyHours(AttendanceRepository $repo, User $user, int $month, int $year): float
    {
        $monthStart = new \DateTime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01");
        $monthEnd   = (clone $monthStart)->modify('+1 month');

        $records = $repo->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.date >= :monthStart')
            ->andWhere('a.date < :monthEnd')
            ->setParameter('user', $user)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->getQuery()
            ->getResult();

        $total = 0.0;
        foreach ($records as $att) {
            $checkIn = $att->getCheckIn();
            $checkOut = $att->getCheckOut();
            if ($checkIn && $checkOut) {
                $diff = $checkOut->diff($checkIn);
                $hours = $diff->h + ($diff->i / 60);
                $total += $hours;
            }
        }

        return $total;
    }
}
