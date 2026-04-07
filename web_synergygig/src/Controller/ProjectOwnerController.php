<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Repository\JobApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/project-owner')]
#[IsGranted('ROLE_PROJECT_OWNER')]
class ProjectOwnerController extends AbstractController
{
    #[Route('/offers', name: 'app_project_owner_offers')]
    public function myOffers(Request $request, OfferRepository $repo, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_offer_index');
        }

        $qb = $repo->createQueryBuilder('o')
            ->where('o.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC');

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(o.title) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        // Counts
        $draftCount = $repo->count(['owner' => $user, 'status' => 'DRAFT']);
        $openCount = $repo->count(['owner' => $user, 'status' => 'OPEN']);
        $closedCount = $repo->count(['owner' => $user, 'status' => 'CLOSED']);
        $totalCount = $repo->count(['owner' => $user]);

        return $this->render('project_owner/offers.html.twig', [
            'offers' => $pagination,
            'pagination' => $pagination,
            'draftCount' => $draftCount,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'totalCount' => $totalCount,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/offers/new', name: 'app_project_owner_offer_new')]
    public function newOffer(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_offer_index');
        }

        $offer = new Offer();
        $offer->setOwner($user);
        $offer->setStatus('DRAFT');

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setCreatedAt(new \DateTime());
            $em->persist($offer);
            $em->flush();
            $this->addFlash('success', 'Offer created successfully.');
            return $this->redirectToRoute('app_project_owner_offers');
        }

        return $this->render('project_owner/offer_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/offers/{id}/edit', name: 'app_project_owner_offer_edit', requirements: ['id' => '\d+'])]
    public function editOffer(Offer $offer, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $offer->getOwner() !== $user) {
            $this->addFlash('error', 'You can only edit your own offers.');
            return $this->redirectToRoute('app_project_owner_offers');
        }

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Offer updated.');
            return $this->redirectToRoute('app_project_owner_offers');
        }

        return $this->render('project_owner/offer_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'offer' => $offer,
        ]);
    }

    #[Route('/offers/{id}/applications', name: 'app_project_owner_applications', requirements: ['id' => '\d+'])]
    public function offerApplications(Offer $offer, JobApplicationRepository $appRepo): Response
    {
        $user = $this->getUser();
        if (!$user || $offer->getOwner() !== $user) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_project_owner_offers');
        }

        $applications = $appRepo->findBy(['offer' => $offer], ['applied_at' => 'DESC']);

        return $this->render('project_owner/applications.html.twig', [
            'offer' => $offer,
            'applications' => $applications,
        ]);
    }
}
