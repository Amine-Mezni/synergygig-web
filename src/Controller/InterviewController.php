<?php

namespace App\Controller;

use App\Entity\Interview;
use App\Form\InterviewType;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/interviews')]
#[IsGranted('ROLE_USER')]
class InterviewController extends AbstractController
{
    #[Route('/', name: 'app_interview_index')]
    public function index(Request $request, InterviewRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('i')->orderBy('i.id', 'DESC');

        // Gig workers only see interviews where they are the candidate
        if (!$this->isGranted('ROLE_HR')) {
            $qb->andWhere('i.candidate = :user')
               ->setParameter('user', $this->getUser());
        }

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $status);
        }

        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(i.candidate_name) LIKE :q OR LOWER(i.position) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        return $this->render('interview/index.html.twig', [
            'interviews' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_interview_new')]
    #[IsGranted('ROLE_HR')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $interview = new Interview();
        $form = $this->createForm(InterviewType::class, $interview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $interview->setCreatedAt(new \DateTime());
            $em->persist($interview);
            $em->flush();
            $this->addFlash('success', 'Interview scheduled.');
            return $this->redirectToRoute('app_interview_index');
        }

        return $this->render('interview/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_interview_show', requirements: ['id' => '\d+'])]
    public function show(Interview $interview): Response
    {
        return $this->render('interview/show.html.twig', [
            'interview' => $interview,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_interview_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_HR')]
    public function edit(Request $request, Interview $interview, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InterviewType::class, $interview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Interview updated.');
            return $this->redirectToRoute('app_interview_index');
        }

        return $this->render('interview/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'interview' => $interview,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_interview_delete', methods: ['POST'])]
    #[IsGranted('ROLE_HR')]
    public function delete(Request $request, Interview $interview, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $interview->getId(), $request->request->get('_token'))) {
            $em->remove($interview);
            $em->flush();
            $this->addFlash('success', 'Interview deleted.');
        }
        return $this->redirectToRoute('app_interview_index');
    }
}
