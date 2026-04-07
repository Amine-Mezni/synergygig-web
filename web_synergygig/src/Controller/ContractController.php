<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\N8nWebhookService;
use App\Service\NotificationService;

#[Route('/contracts')]
class ContractController extends AbstractController
{
    #[Route('/', name: 'app_contract_index')]
    public function index(Request $request, ContractRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('c')->orderBy('c.id', 'DESC');

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        return $this->render('contract/index.html.twig', [
            'contracts' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_contract_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $contract = new Contract();
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract->setCreatedAt(new \DateTime());
            $contract->setStatus($contract->getStatus() ?? 'DRAFT');
            $em->persist($contract);
            $em->flush();
            $this->addFlash('success', 'Contract created.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_contract_show', requirements: ['id' => '\d+'])]
    public function show(Contract $contract): Response
    {
        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contract_edit', requirements: ['id' => '\d+'])]
    public function edit(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contract updated.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/form.html.twig', [
            'form' => $form->createView(),
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_contract_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contract->getId(), $request->request->get('_token'))) {
            $em->remove($contract);
            $em->flush();
            $this->addFlash('success', 'Contract deleted.');
        }
        return $this->redirectToRoute('app_contract_index');
    }

    #[Route('/{id}/negotiate', name: 'app_contract_negotiate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function negotiate(Contract $contract, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if (!$this->isCsrfTokenValid('negotiate' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        $counterAmount = $request->request->get('counter_amount');
        $counterTerms = trim((string) $request->request->get('counter_terms', ''));
        $notes = trim((string) $request->request->get('negotiation_notes', ''));

        // Validate counter amount
        if ($counterAmount !== null && $counterAmount !== '') {
            $counterAmount = (float) $counterAmount;
            if ($counterAmount <= 0) {
                $this->addFlash('error', 'Counter amount must be a positive number.');
                return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
            }
            if ($counterAmount > 99999999) {
                $this->addFlash('error', 'Counter amount is too large.');
                return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
            }
            $contract->setCounterAmount($counterAmount);
        }

        // Validate counter terms
        if ($counterTerms !== '') {
            $violations = $validator->validate($counterTerms, [
                new Assert\Length([
                    'min' => 10, 'minMessage' => 'Counter terms must be at least {{ limit }} characters.',
                    'max' => 5000, 'maxMessage' => 'Counter terms cannot exceed {{ limit }} characters.',
                ]),
            ]);
            if (count($violations) > 0) {
                foreach ($violations as $v) { $this->addFlash('error', $v->getMessage()); }
                return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
            }
            $contract->setCounterTerms($counterTerms);
        }

        // Validate notes
        if ($notes !== '') {
            $violations = $validator->validate($notes, [
                new Assert\Length(['max' => 2000, 'maxMessage' => 'Notes cannot exceed {{ limit }} characters.']),
            ]);
            if (count($violations) > 0) {
                foreach ($violations as $v) { $this->addFlash('error', $v->getMessage()); }
                return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
            }
            $contract->setNegotiationNotes($notes);
        }

        // Must have at least counter amount or counter terms
        $hasAmount = $counterAmount !== null && $counterAmount !== '' && (is_float($counterAmount) && $counterAmount > 0);
        if ($counterTerms === '' && !$hasAmount) {
            $this->addFlash('error', 'Please provide a counter amount or counter terms.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        $round = ($contract->getNegotiationRound() ?? 0) + 1;
        $contract->setNegotiationRound($round);
        $contract->setStatus('COUNTER_PROPOSED');

        $em->flush();
        $this->addFlash('success', 'Counter-proposal submitted (Round ' . $round . ').');
        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/{id}/sign', name: 'app_contract_sign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sign(Contract $contract, Request $request, EntityManagerInterface $em, N8nWebhookService $n8n, NotificationService $notifier): Response
    {
        if (!$this->isCsrfTokenValid('sign' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        $signatureData = $request->request->get('signature_data', '');

        if (empty($signatureData) || strlen($signatureData) < 100) {
            $this->addFlash('error', 'Please draw your signature before signing.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        // Validate it's a valid data URL
        if (!str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Invalid signature data format.');
            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        $contract->setSignatureData($signatureData);
        $contract->setSignedAt(new \DateTime());
        $contract->setStatus('ACTIVE');

        // Generate a simple blockchain-like hash for verification
        $hashPayload = $contract->getId() . '|' . ($contract->getApplicant() ? $contract->getApplicant()->getId() : '') . '|' . date('c') . '|' . substr($signatureData, 0, 200);
        $contract->setBlockchainHash(hash('sha256', $hashPayload));

        // Calculate risk score from contract data
        $risk = $this->calculateRiskScore($contract);
        $contract->setRiskScore($risk['score']);
        $contract->setRiskFactors($risk['factors']);

        $em->flush();

        $n8n->contractSigned(
            $contract->getId(),
            $contract->getApplicant() ? $contract->getApplicant()->getFirstName() . ' ' . $contract->getApplicant()->getLastName() : 'N/A',
            $contract->getType() ?? 'N/A'
        );

        if ($contract->getApplicant()) {
            $notifier->contractSigned($contract->getApplicant(), $contract->getId());
        }

        $this->addFlash('success', 'Contract signed successfully.');
        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    private function calculateRiskScore(Contract $contract): array
    {
        $score = 0;
        $factors = [];

        if (!$contract->getAmount() || $contract->getAmount() <= 0) {
            $score += 25;
            $factors[] = 'No amount specified';
        } elseif ($contract->getAmount() > 100000) {
            $score += 15;
            $factors[] = 'High value contract';
        }

        if (!$contract->getEndDate()) {
            $score += 20;
            $factors[] = 'No end date defined';
        } elseif ($contract->getStartDate() && $contract->getEndDate()) {
            $diff = $contract->getStartDate()->diff($contract->getEndDate())->days;
            if ($diff > 365) {
                $score += 10;
                $factors[] = 'Long duration (' . $diff . ' days)';
            }
        }

        if (!$contract->getTerms() || strlen($contract->getTerms()) < 50) {
            $score += 20;
            $factors[] = 'Insufficient terms detail';
        }

        if ($contract->getNegotiationRound() && $contract->getNegotiationRound() > 3) {
            $score += 10;
            $factors[] = 'Multiple negotiation rounds';
        }

        return [
            'score' => min(100, $score),
            'factors' => implode('; ', $factors ?: ['No risk factors identified']),
        ];
    }
}
