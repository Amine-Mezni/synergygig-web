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
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        $form = $this->createForm(UserType::class, $user, ['show_admin_fields' => false]);
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

    #[Route('/profile/upload-avatar', name: 'app_profile_upload_avatar', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadAvatar(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$this->isCsrfTokenValid('avatar-self', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile');
        }

        $file = $request->files->get('avatar');
        if ($file && $file->isValid()) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                $this->addFlash('error', 'Only JPG, PNG, GIF or WEBP images are allowed.');
                return $this->redirectToRoute('app_profile');
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Image must be smaller than 5 MB.');
                return $this->redirectToRoute('app_profile');
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if ($user->getAvatar_path()) {
                $old = $uploadDir . '/' . $user->getAvatar_path();
                if (file_exists($old)) {
                    unlink($old);
                }
            }

            $filename = 'user_' . $user->getId() . '_' . uniqid() . '.' . $file->guessExtension();
            $file->move($uploadDir, $filename);
            $user->setAvatarPath($filename);
            $em->flush();
            $this->addFlash('success', 'Profile photo updated.');
        }

        return $this->redirectToRoute('app_profile');
    }
}
