<?php

namespace App\Controller\Front;

use App\Entity\Offers;
use App\Entity\Applications;
use App\Repository\OffersRepository;
use App\Repository\UsersRepository;
use App\Repository\ApplicationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\OfferAiInsightService;
use App\Service\ApplicationMatchService;

class OfferController extends AbstractController
{
    #[Route('/offers', name: 'app_front_offers')]
public function index(
    OffersRepository $offersRepository,
    ApplicationsRepository $applicationsRepository,
    UsersRepository $usersRepository,
    Request $request
): Response {
    $user = $this->getUser();
    if (!$user) {
        $user = $usersRepository->find(1);
    }

    $search = trim($request->query->get('q', ''));
    $type = $request->query->get('type', '');
    $sort = $request->query->get('sort', 'recent');

    $qb = $offersRepository->createQueryBuilder('o')
        ->where('o.status = :status')
        ->setParameter('status', 'PUBLISHED');

    if ($search !== '') {
        $qb->andWhere('o.title LIKE :search OR o.description LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($type && in_array($type, ['GIG', 'INTERNAL'])) {
        $qb->andWhere('o.type = :type')
           ->setParameter('type', $type);
    }

    switch ($sort) {
        case 'budget_asc':
            $qb->orderBy('o.amount', 'ASC');
            break;

        case 'budget_desc':
            $qb->orderBy('o.amount', 'DESC');
            break;

        case 'oldest':
            $qb->orderBy('o.id', 'ASC');
            break;

        case 'recent':
        default:
            $qb->orderBy('o.id', 'DESC');
            break;
    }

    $offers = $qb->getQuery()->getResult();

    $appliedOfferIds = [];

    if ($user) {
        $applications = $applicationsRepository->findBy(['applicant_id' => $user]);

        foreach ($applications as $application) {
            if ($application->getOffer_id()) {
                $appliedOfferIds[] = $application->getOffer_id()->getId();
            }
        }
    }

    return $this->render('offer/index.html.twig', [
        'offers' => $offers,
        'appliedOfferIds' => $appliedOfferIds,
        'currentSearch' => $search,
        'currentType' => $type,
        'currentSort' => $sort,
        'offersCount' => count($offers),
    ]);
}

#[Route('/offers/{id}', name: 'app_front_offer_show', requirements: ['id' => '\d+'])]
public function show(
    Offers $offer,
    ApplicationsRepository $applicationsRepository,
    UsersRepository $usersRepository,
    OfferAiInsightService $offerAiInsightService,
    ApplicationMatchService $applicationMatchService
): Response {
    if ($offer->getStatus() !== 'PUBLISHED') {
        throw $this->createNotFoundException('Cette offre n’est pas disponible.');
    }

    $user = $this->getUser();
    if (!$user) {
        $user = $usersRepository->find(1);
    }

    $existingApplication = null;
    $alreadyApplied = false;
    $matchData = null;

    if ($user) {
        $existingApplication = $applicationsRepository->findOneBy([
            'offer_id' => $offer,
            'applicant_id' => $user,
        ]);

        $alreadyApplied = $existingApplication !== null;
        $matchData = $applicationMatchService->matchOfferToUser($offer, (int) $user->getId());
    }

    $aiInsight = $offerAiInsightService->analyzeOffer($offer);

    return $this->render('offer/show.html.twig', [
        'offer' => $offer,
        'alreadyApplied' => $alreadyApplied,
        'existingApplication' => $existingApplication,
        'aiInsight' => $aiInsight,
        'matchData' => $matchData,
    ]);
}

    #[Route('/offers/{id}/apply', name: 'app_front_offer_apply', requirements: ['id' => '\d+'])]
    public function apply(
        Offers $offer,
        EntityManagerInterface $em,
        UsersRepository $usersRepository,
        ApplicationsRepository $applicationsRepository
    ): Response {
        if ($offer->getStatus() !== 'PUBLISHED') {
            throw $this->createNotFoundException('Cette offre n’est pas disponible.');
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $usersRepository->find(1);
        }

        if (!$user) {
            throw new \RuntimeException('Utilisateur introuvable.');
        }

        $existingApplication = $applicationsRepository->findOneBy([
            'offer_id' => $offer,
            'applicant_id' => $user,
        ]);

        if ($existingApplication) {
            return $this->render('offer/apply_success.html.twig', [
                'offer' => $offer,
                'alreadyApplied' => true,
            ]);
        }

        $application = new Applications();
        $application->setOffer_id($offer);
        $application->setApplicant_id($user);
        $application->setStatus('PENDING');
        $application->setApplied_at(new \DateTime());

        $em->persist($application);
        $em->flush();

        return $this->render('offer/apply_success.html.twig', [
            'offer' => $offer,
            'alreadyApplied' => false,
        ]);
    }
#[Route('/my-applications', name: 'app_front_my_applications')]
public function myApplications(
    ApplicationsRepository $applicationsRepository,
    UsersRepository $usersRepository,
    Request $request
): Response {
    $user = $this->getUser();

    if (!$user) {
        $user = $usersRepository->find(1);
    }

    if (!$user) {
        throw new \RuntimeException('Utilisateur introuvable.');
    }

    $status = $request->query->get('status');
    $keyword = trim($request->query->get('q', ''));

    $qb = $applicationsRepository->createQueryBuilder('a')
        ->leftJoin('a.offer_id', 'o')
        ->addSelect('o')
        ->where('a.applicant_id = :user')
        ->setParameter('user', $user)
        ->orderBy('a.applied_at', 'DESC');

    if ($status && in_array($status, ['PENDING', 'ACCEPTED', 'REJECTED'])) {
        $qb->andWhere('a.status = :status')
           ->setParameter('status', $status);
    }

    if ($keyword !== '') {
        $qb->andWhere('o.title LIKE :keyword OR o.description LIKE :keyword OR o.type LIKE :keyword')
           ->setParameter('keyword', '%' . $keyword . '%');
    }

    $applications = $qb->getQuery()->getResult();

    $pendingCount = $applicationsRepository->count([
        'applicant_id' => $user,
        'status' => 'PENDING',
    ]);

    $acceptedCount = $applicationsRepository->count([
        'applicant_id' => $user,
        'status' => 'ACCEPTED',
    ]);

    $rejectedCount = $applicationsRepository->count([
        'applicant_id' => $user,
        'status' => 'REJECTED',
    ]);

    $totalCount = $applicationsRepository->count([
        'applicant_id' => $user,
    ]);

    return $this->render('offer/my_applications.html.twig', [
        'applications' => $applications,
        'currentStatus' => $status,
        'currentKeyword' => $keyword,
        'pendingCount' => $pendingCount,
        'acceptedCount' => $acceptedCount,
        'rejectedCount' => $rejectedCount,
        'totalCount' => $totalCount,
    ]);
}
}