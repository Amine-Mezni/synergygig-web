<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index')]
    public function index(ProjectRepository $repo): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_project_new')]
    #[IsGranted('ROLE_MANAGER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setCreatedAt(new \DateTime());
            $em->persist($project);
            $em->flush();
            $this->addFlash('success', 'Project created.');
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/kanban', name: 'app_project_kanban', requirements: ['id' => '\d+'])]
    public function kanban(Project $project, TaskRepository $taskRepo): Response
    {
        $tasks = $taskRepo->findBy(['project' => $project]);
        $columns = [
            'TODO' => [],
            'IN_PROGRESS' => [],
            'IN_REVIEW' => [],
            'DONE' => [],
        ];
        foreach ($tasks as $task) {
            $status = strtoupper($task->getStatus() ?? 'TODO');
            if (!isset($columns[$status])) {
                $columns['TODO'][] = $task;
            } else {
                $columns[$status][] = $task;
            }
        }

        return $this->render('project/kanban.html.twig', [
            'project' => $project,
            'columns' => $columns,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', requirements: ['id' => '\d+'])]
    public function show(Project $project, TaskRepository $taskRepo): Response
    {
        $tasks = $taskRepo->findBy(['project' => $project], ['id' => 'DESC']);
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function edit(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Project updated.');
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'project' => $project,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $em->remove($project);
            $em->flush();
            $this->addFlash('success', 'Project deleted.');
        }
        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/task/{id}/move', name: 'app_task_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function moveTask(Request $request, Task $task, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newStatus = strtoupper($data['status'] ?? '');
        $token = $data['_token'] ?? '';

        if (!$this->isCsrfTokenValid('task_move', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $valid = ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE'];
        if (!in_array($newStatus, $valid, true)) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }

        $task->setStatus($newStatus);
        $em->flush();

        return new JsonResponse(['success' => true, 'status' => $newStatus]);
    }
}
