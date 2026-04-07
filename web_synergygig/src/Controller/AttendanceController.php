<?php

namespace App\Controller;

use App\Entity\Attendance;
use App\Form\AttendanceType;
use App\Repository\AttendanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/attendance')]
class AttendanceController extends AbstractController
{
    #[Route('/', name: 'app_attendance_index')]
    public function index(Request $request, AttendanceRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('a')->orderBy('a.id', 'DESC');

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        if ($dateFrom) {
            $qb->andWhere('a.date >= :dateFrom')->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        if ($dateTo) {
            $qb->andWhere('a.date <= :dateTo')->setParameter('dateTo', new \DateTime($dateTo));
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('attendance/index.html.twig', [
            'records' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_attendance_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $attendance = new Attendance();
        $form = $this->createForm(AttendanceType::class, $attendance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attendance->setCreatedAt(new \DateTime());
            // Auto-detect late status
            $this->detectLateStatus($attendance);
            $em->persist($attendance);
            $em->flush();
            $this->addFlash('success', 'Attendance record created.');
            return $this->redirectToRoute('app_attendance_index');
        }

        return $this->render('attendance/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_attendance_show', requirements: ['id' => '\d+'])]
    public function show(Attendance $attendance): Response
    {
        return $this->render('attendance/show.html.twig', [
            'record' => $attendance,
            'hoursWorked' => $this->calculateHours($attendance),
            'overtime' => $this->calculateOvertime($attendance),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_attendance_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Attendance $attendance, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AttendanceType::class, $attendance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->detectLateStatus($attendance);
            $em->flush();
            $this->addFlash('success', 'Attendance record updated.');
            return $this->redirectToRoute('app_attendance_index');
        }

        return $this->render('attendance/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'record' => $attendance,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_attendance_delete', methods: ['POST'])]
    public function delete(Request $request, Attendance $attendance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $attendance->getId(), $request->request->get('_token'))) {
            $em->remove($attendance);
            $em->flush();
            $this->addFlash('success', 'Attendance record deleted.');
        }
        return $this->redirectToRoute('app_attendance_index');
    }

    // ── Check-in ──
    #[Route('/checkin', name: 'app_attendance_checkin', methods: ['POST'])]
    public function checkin(Request $request, EntityManagerInterface $em, AttendanceRepository $repo): Response
    {
        if (!$this->isCsrfTokenValid('attendance_checkin', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_attendance_index');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in.');
            return $this->redirectToRoute('app_login');
        }

        // Check if already checked in today
        $today = new \DateTime('today');
        $existing = $repo->findOneBy(['user' => $user, 'date' => $today]);

        if ($existing && $existing->getCheckIn()) {
            $this->addFlash('warning', 'You have already checked in today.');
            return $this->redirectToRoute('app_attendance_index');
        }

        $now = new \DateTime();
        if (!$existing) {
            $existing = new Attendance();
            $existing->setUser($user);
            $existing->setDate($today);
            $existing->setCreatedAt($now);
            $em->persist($existing);
        }

        $existing->setCheckIn($now);
        $existing->setStatus('PRESENT');
        $this->detectLateStatus($existing);

        $em->flush();
        $this->addFlash('success', 'Checked in at ' . $now->format('H:i') . ($existing->getStatus() === 'LATE' ? ' (Late)' : ''));

        return $this->redirectToRoute('app_attendance_index');
    }

    // ── Check-out ──
    #[Route('/checkout', name: 'app_attendance_checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, AttendanceRepository $repo): Response
    {
        if (!$this->isCsrfTokenValid('attendance_checkout', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_attendance_index');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'You must be logged in.');
            return $this->redirectToRoute('app_login');
        }

        $today = new \DateTime('today');
        $existing = $repo->findOneBy(['user' => $user, 'date' => $today]);

        if (!$existing || !$existing->getCheckIn()) {
            $this->addFlash('warning', 'You haven\'t checked in today.');
            return $this->redirectToRoute('app_attendance_index');
        }

        if ($existing->getCheckOut()) {
            $this->addFlash('warning', 'You have already checked out today.');
            return $this->redirectToRoute('app_attendance_index');
        }

        $now = new \DateTime();
        $existing->setCheckOut($now);
        $em->flush();

        $hours = $this->calculateHours($existing);
        $this->addFlash('success', sprintf('Checked out at %s — worked %.1f hours', $now->format('H:i'), $hours));

        return $this->redirectToRoute('app_attendance_index');
    }

    // ── Business logic ──

    private function detectLateStatus(Attendance $attendance): void
    {
        $checkIn = $attendance->getCheckIn();
        if (!$checkIn) {
            return;
        }

        // Late if check-in after 09:15
        $lateThreshold = (clone $checkIn)->setTime(9, 15, 0);
        if ($checkIn > $lateThreshold && $attendance->getStatus() !== 'ABSENT') {
            $attendance->setStatus('LATE');
        }
    }

    private function calculateHours(Attendance $attendance): float
    {
        $checkIn = $attendance->getCheckIn();
        $checkOut = $attendance->getCheckOut();
        if (!$checkIn || !$checkOut) {
            return 0.0;
        }

        $diff = $checkOut->getTimestamp() - $checkIn->getTimestamp();
        return round($diff / 3600, 2);
    }

    private function calculateOvertime(Attendance $attendance): float
    {
        $hours = $this->calculateHours($attendance);
        return max(0, $hours - 8.0);
    }
}
