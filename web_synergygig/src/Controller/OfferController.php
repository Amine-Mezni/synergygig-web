<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\JobApplication;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\UserRepository;
use App\Service\OfferAiInsightService;
use App\Service\ApplicationMatchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/offers')]
class OfferController extends AbstractController
{
    #[Route('/', name: 'app_offer_index')]
    public function index(Request $request, OfferRepository $repo, JobApplicationRepository $appRepo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('o')->orderBy('o.id', 'DESC');

        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(o.title) LIKE :q OR LOWER(o.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        $type = $request->query->get('type');
        if ($type) {
            $qb->andWhere('o.offer_type = :type')->setParameter('type', $type);
        }

        $sort = $request->query->get('sort', 'recent');
        switch ($sort) {
            case 'budget_asc':  $qb->orderBy('o.amount', 'ASC'); break;
            case 'budget_desc': $qb->orderBy('o.amount', 'DESC'); break;
            case 'oldest':      $qb->orderBy('o.id', 'ASC'); break;
            default:            $qb->orderBy('o.id', 'DESC'); break;
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        // Track which offers the current user already applied to
        $appliedOfferIds = [];
        $user = $this->getUser();
        if ($user) {
            $applications = $appRepo->findBy(['applicant' => $user]);
            foreach ($applications as $app) {
                if ($app->getOffer()) {
                    $appliedOfferIds[] = $app->getOffer()->getId();
                }
            }
        }

        return $this->render('offer/index.html.twig', [
            'offers' => $pagination,
            'pagination' => $pagination,
            'appliedOfferIds' => $appliedOfferIds,
        ]);
    }

    #[Route('/new', name: 'app_offer_new')]
    #[IsGranted('ROLE_PROJECT_OWNER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setCreatedAt(new \DateTime());
            if (!$offer->getOwner() && $this->getUser()) {
                $offer->setOwner($this->getUser());
            }
            $em->persist($offer);
            $em->flush();
            $this->addFlash('success', 'Offer created.');
            return $this->redirectToRoute('app_offer_index');
        }

        return $this->render('offer/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_offer_show', requirements: ['id' => '\d+'])]
    public function show(
        Offer $offer,
        JobApplicationRepository $appRepo,
        OfferAiInsightService $aiInsightService,
        ApplicationMatchService $matchService
    ): Response {
        $user = $this->getUser();
        $existingApplication = null;
        $alreadyApplied = false;
        $matchData = null;

        if ($user) {
            $existingApplication = $appRepo->findOneBy([
                'offer' => $offer,
                'applicant' => $user,
            ]);
            $alreadyApplied = $existingApplication !== null;
            $matchData = $matchService->matchOfferToUser($offer, (int) $user->getId());
        }

        $aiInsight = $aiInsightService->analyzeOffer($offer);

        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
            'alreadyApplied' => $alreadyApplied,
            'existingApplication' => $existingApplication,
            'aiInsight' => $aiInsight,
            'matchData' => $matchData,
        ]);
    }

    #[Route('/{id}/apply', name: 'app_offer_apply', requirements: ['id' => '\d+'])]
    public function apply(
        Offer $offer,
        EntityManagerInterface $em,
        JobApplicationRepository $appRepo
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to apply.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offer->getId()]);
        }

        if ($offer->getOwner() === $user) {
            $this->addFlash('error', 'You cannot apply to your own offer.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offer->getId()]);
        }

        if ($offer->getStatus() !== 'OPEN') {
            $this->addFlash('error', 'This offer is not currently accepting applications.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offer->getId()]);
        }

        $existing = $appRepo->findOneBy([
            'offer' => $offer,
            'applicant' => $user,
        ]);

        if ($existing) {
            return $this->render('offer/apply_success.html.twig', [
                'offer' => $offer,
                'alreadyApplied' => true,
            ]);
        }

        $application = new JobApplication();
        $application->setOffer($offer);
        $application->setApplicant($user);
        $application->setStatus('PENDING');
        $application->setAppliedAt(new \DateTime());

        $em->persist($application);
        $em->flush();

        return $this->render('offer/apply_success.html.twig', [
            'offer' => $offer,
            'alreadyApplied' => false,
        ]);
    }

    #[Route('/{id}/publish', name: 'app_offer_publish', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_PROJECT_OWNER')]
    public function publish(Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only publish your own offers.');
        }
        $offer->setStatus('OPEN');
        $em->flush();
        $this->addFlash('success', 'Offer published.');
        return $this->redirectToRoute('app_offer_index');
    }

    #[Route('/{id}/close', name: 'app_offer_close', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_PROJECT_OWNER')]
    public function close(Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only close your own offers.');
        }
        $offer->setStatus('CLOSED');
        $em->flush();
        $this->addFlash('success', 'Offer closed.');
        return $this->redirectToRoute('app_offer_index');
    }

    #[Route('/{id}/edit', name: 'app_offer_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_PROJECT_OWNER')]
    public function edit(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own offers.');
        }
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Offer updated.');
            return $this->redirectToRoute('app_offer_index');
        }

        return $this->render('offer/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_offer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_OWNER')]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own offers.');
        }
        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->request->get('_token'))) {
            $em->remove($offer);
            $em->flush();
            $this->addFlash('success', 'Offer deleted.');
        }
        return $this->redirectToRoute('app_offer_index');
    }
}
