<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $current = $request->request->get('current_password', '');
        $newPass = $request->request->get('new_password', '');
        $confirm = $request->request->get('confirm_password', '');

        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('app_settings');
        }
        if (strlen($newPass) < 6) {
            $this->addFlash('error', 'New password must be at least 6 characters.');
            return $this->redirectToRoute('app_settings');
        }
        if ($newPass !== $confirm) {
            $this->addFlash('error', 'Passwords do not match.');
            return $this->redirectToRoute('app_settings');
        }

        $user->setPassword($hasher->hashPassword($user, $newPass));
        $em->flush();
        $this->addFlash('success', 'Password changed successfully.');
        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings', name: 'app_settings')]
    public function settings(): Response
    {
        return $this->render('profile/settings.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
