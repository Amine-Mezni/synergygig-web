<?php

namespace App\Controller;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/departments')]
#[IsGranted('ROLE_ADMIN')]
class DepartmentController extends AbstractController
{
    #[Route('/', name: 'app_department_index')]
    public function index(Request $request, DepartmentRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('d')->orderBy('d.id', 'DESC');

        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(d.name) LIKE :q')->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 15);

        return $this->render('department/index.html.twig', [
            'departments' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_department_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $department = new Department();
        $department->setCreated_at(new \DateTime());

        $form = $this->createForm(DepartmentType::class, $department);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validateDepartment($department);
            if (!empty($errors)) {
                foreach ($errors as $err) {
                    $this->addFlash('error', $err);
                }
                return $this->render('department/form.html.twig', [
                    'form' => $form->createView(),
                    'department' => $department,
                    'is_edit' => false,
                ]);
            }

            $em->persist($department);
            $em->flush();
            $this->addFlash('success', 'Department created successfully.');
            return $this->redirectToRoute('app_department_index');
        }

        return $this->render('department/form.html.twig', [
            'form' => $form->createView(),
            'department' => $department,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_department_show', requirements: ['id' => '\d+'])]
    public function show(Department $department): Response
    {
        return $this->render('department/show.html.twig', [
            'department' => $department,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_department_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Department $department, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DepartmentType::class, $department);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validateDepartment($department);
            if (!empty($errors)) {
                foreach ($errors as $err) {
                    $this->addFlash('error', $err);
                }
                return $this->render('department/form.html.twig', [
                    'form' => $form->createView(),
                    'department' => $department,
                    'is_edit' => true,
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Department updated successfully.');
            return $this->redirectToRoute('app_department_index');
        }

        return $this->render('department/form.html.twig', [
            'form' => $form->createView(),
            'department' => $department,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_department_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Department $department, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-' . $department->getId(), $request->request->get('_token'))) {
            $em->remove($department);
            $em->flush();
            $this->addFlash('success', 'Department deleted.');
        }

        return $this->redirectToRoute('app_department_index');
    }

    private function validateDepartment(Department $department): array
    {
        $errors = [];

        $name = $department->getName();
        if (!$name || strlen(trim($name)) < 2) {
            $errors[] = 'Department name must be at least 2 characters.';
        }
        if ($name && strlen($name) > 100) {
            $errors[] = 'Department name cannot exceed 100 characters.';
        }

        if (!$department->getManager()) {
            $errors[] = 'Each department must have a manager.';
        }

        $budget = $department->getAllocatedBudget();
        if ($budget !== null && $budget < 0) {
            $errors[] = 'Budget must be a positive number.';
        }

        return $errors;
    }
}
