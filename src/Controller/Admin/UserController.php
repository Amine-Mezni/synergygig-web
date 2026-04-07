<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_admin_user_list')]
public function index(EntityManagerInterface $entityManager): Response
{
    $users = $entityManager->getRepository(Users::class)->findBy([], [
        'created_at' => 'DESC'
    ]);

    return $this->render('admin/user/list.html.twig', [
        'users' => $users,
    ]);
}
}