<?php

namespace App\Controller\Admin;

use App\Repository\OffersRepository;
use App\Repository\ApplicationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        OffersRepository $offersRepository,
        ApplicationsRepository $applicationsRepository
    ): Response {
        // Offres
        $publishedOffers = $offersRepository->count(['status' => 'PUBLISHED']);
        $draftOffers = $offersRepository->count(['status' => 'DRAFT']);
        $inProgressOffers = $offersRepository->count(['status' => 'IN_PROGRESS']);
        $completedOffers = $offersRepository->count(['status' => 'COMPLETED']);
        $cancelledOffers = $offersRepository->count(['status' => 'CANCELLED']);

        // Candidatures
        $pendingApplications = $applicationsRepository->count(['status' => 'PENDING']);
        $acceptedApplications = $applicationsRepository->count(['status' => 'ACCEPTED']);
        $rejectedApplications = $applicationsRepository->count(['status' => 'REJECTED']);

        // Totaux métier
        $totalOffersActivity = $publishedOffers + $draftOffers + $inProgressOffers + $completedOffers + $cancelledOffers;
        $totalApplicationsActivity = $pendingApplications + $acceptedApplications + $rejectedApplications;
        $totalActivity = $totalOffersActivity + $totalApplicationsActivity;

        // Activité récente
        $recentOffers = $offersRepository->findBy([], ['id' => 'DESC'], 5);
        $recentApplications = $applicationsRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'publishedOffers' => $publishedOffers,
            'draftOffers' => $draftOffers,
            'inProgressOffers' => $inProgressOffers,
            'completedOffers' => $completedOffers,
            'cancelledOffers' => $cancelledOffers,

            'pendingApplications' => $pendingApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,

            'totalActivity' => $totalActivity,
            'totalOffersActivity' => $totalOffersActivity,
            'totalApplicationsActivity' => $totalApplicationsActivity,

            'recentOffers' => $recentOffers,
            'recentApplications' => $recentApplications,
        ]);
    }
}