<?php

namespace App\Controller\Admin;

use App\Entity\Applications;
use App\Repository\ApplicationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationController extends AbstractController
{
    #[Route('/admin/applications', name: 'app_admin_applications')]
    public function index(ApplicationsRepository $repo, Request $request): Response
    {
        $status = $request->query->get('status');

        if ($status && in_array($status, ['PENDING', 'ACCEPTED', 'REJECTED'])) {
            $applications = $repo->findBy(['status' => $status], ['id' => 'DESC']);
        } else {
            $applications = $repo->findBy([], ['id' => 'DESC']);
        }

        return $this->render('admin/application/index.html.twig', [
            'applications' => $applications,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/admin/applications/{id}/accept', name: 'app_admin_application_accept')]
    public function accept(Applications $application, EntityManagerInterface $entityManager): RedirectResponse
    {
        $application->setStatus('ACCEPTED');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_applications');
    }

    #[Route('/admin/applications/{id}/reject', name: 'app_admin_application_reject')]
    public function reject(Applications $application, EntityManagerInterface $entityManager): RedirectResponse
    {
        $application->setStatus('REJECTED');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_applications');
    }
}