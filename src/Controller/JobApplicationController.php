<?php

namespace App\Controller;

use App\Entity\JobApplication;
use App\Form\JobApplicationType;
use App\Repository\JobApplicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/applications')]
class JobApplicationController extends AbstractController
{
    #[Route('/', name: 'app_application_index')]
    #[IsGranted('ROLE_HR')]
    public function index(Request $request, JobApplicationRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('a')
            ->leftJoin('a.offer', 'o')->addSelect('o')
            ->leftJoin('a.applicant', 'u')->addSelect('u')
            ->orderBy('a.id', 'DESC');

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(o.title) LIKE :q OR LOWER(u.first_name) LIKE :q OR LOWER(u.last_name) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        return $this->render('application/index.html.twig', [
            'applications' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/my', name: 'app_my_applications')]
    public function myApplications(Request $request, JobApplicationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_offer_index');
        }

        $status = $request->query->get('status');
        $keyword = trim($request->query->get('q', ''));

        $qb = $repo->createQueryBuilder('a')
            ->leftJoin('a.offer', 'o')->addSelect('o')
            ->where('a.applicant = :user')
            ->setParameter('user', $user)
            ->orderBy('a.applied_at', 'DESC');

        if ($status && in_array($status, ['PENDING', 'REVIEWED', 'ACCEPTED', 'REJECTED', 'SHORTLISTED', 'WITHDRAWN'])) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        if ($keyword !== '') {
            $qb->andWhere('LOWER(o.title) LIKE :kw OR LOWER(o.description) LIKE :kw')
               ->setParameter('kw', '%' . mb_strtolower($keyword) . '%');
        }

        $applications = $qb->getQuery()->getResult();

        $pendingCount = $repo->count(['applicant' => $user, 'status' => 'PENDING']);
        $acceptedCount = $repo->count(['applicant' => $user, 'status' => 'ACCEPTED']);
        $rejectedCount = $repo->count(['applicant' => $user, 'status' => 'REJECTED']);
        $totalCount = $repo->count(['applicant' => $user]);

        return $this->render('application/my_applications.html.twig', [
            'applications' => $applications,
            'currentStatus' => $status,
            'currentKeyword' => $keyword,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'rejectedCount' => $rejectedCount,
            'totalCount' => $totalCount,
        ]);
    }

    #[Route('/new', name: 'app_application_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $app = new JobApplication();
        $form = $this->createForm(JobApplicationType::class, $app);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $app->setAppliedAt(new \DateTime());
            $app->setStatus($app->getStatus() ?? 'PENDING');
            $em->persist($app);
            $em->flush();
            $this->addFlash('success', 'Application submitted.');
            return $this->redirectToRoute('app_application_show', ['id' => $app->getId()]);
        }

        return $this->render('application/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_application_show', requirements: ['id' => '\d+'])]
    public function show(JobApplication $application): Response
    {
        if (!$this->isGranted('ROLE_HR') && $application->getApplicant() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own applications.');
        }
        return $this->render('application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{id}/accept', name: 'app_application_accept', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_HR')]
    public function accept(JobApplication $application, EntityManagerInterface $em): Response
    {
        $application->setStatus('ACCEPTED');
        $application->setReviewedAt(new \DateTime());
        $em->flush();
        $this->addFlash('success', 'Application accepted.');
        return $this->redirectToRoute('app_application_index');
    }

    #[Route('/{id}/reject', name: 'app_application_reject', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_HR')]
    public function reject(JobApplication $application, EntityManagerInterface $em): Response
    {
        $application->setStatus('REJECTED');
        $application->setReviewedAt(new \DateTime());
        $em->flush();
        $this->addFlash('success', 'Application rejected.');
        return $this->redirectToRoute('app_application_index');
    }

    #[Route('/{id}/edit', name: 'app_application_edit', requirements: ['id' => '\d+'])]
    public function edit(JobApplication $application, Request $request, EntityManagerInterface $em): Response
    {
        if ($application->getApplicant() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own applications.');
        }
        $form = $this->createForm(JobApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Application updated.');
            return $this->redirectToRoute('app_application_show', ['id' => $application->getId()]);
        }

        return $this->render('application/form.html.twig', [
            'form' => $form->createView(),
            'application' => $application,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_application_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(JobApplication $application, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_HR') && $application->getApplicant() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own applications.');
        }
        if ($this->isCsrfTokenValid('delete' . $application->getId(), $request->request->get('_token'))) {
            $em->remove($application);
            $em->flush();
            $this->addFlash('success', 'Application deleted.');
        }
        return $this->redirectToRoute('app_application_index');
    }
}
