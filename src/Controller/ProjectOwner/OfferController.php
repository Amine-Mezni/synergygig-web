<?php

namespace App\Controller\ProjectOwner;

use App\Entity\Offers;
use App\Form\ProjectOwnerOfferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class OfferController extends AbstractController
{
    #[Route('/project-owner/offers/new', name: 'app_project_owner_offer_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $offer = new Offers();

        $form = $this->createForm(ProjectOwnerOfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setStatus('DRAFT');
            $offer->setCreatedAt(new \DateTime());

            $user = $this->getUser();
            if ($user) {
                $offer->setCreatedBy($user);
            }

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('offers_images_directory'),
                        $newFilename
                    );
                    $offer->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l’upload de l’image.');
                }
            }

            $entityManager->persist($offer);
            $entityManager->flush();

            $this->addFlash('success', 'Offre créée avec succès.');

            return $this->redirectToRoute('app_project_owner_offer_list');
        }

        return $this->render('project_owner/offer/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/project-owner/offers/{id}/edit', name: 'app_project_owner_offer_edit', requirements: ['id' => '\d+'])]
public function edit(
    Offers $offer,
    Request $request,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger
): Response {
    $user = $this->getUser();

    if (!$user) {
        throw $this->createAccessDeniedException('Utilisateur non authentifié.');
    }

    if ($offer->getCreatedBy() !== $user) {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette offre.');
    }

    if ($offer->getStatus() !== 'DRAFT') {
        $this->addFlash('error', 'Cette offre ne peut plus être modifiée car elle a déjà été publiée ou traitée.');
        return $this->redirectToRoute('app_project_owner_offer_list');
    }

    $oldImage = $offer->getImageUrl();

    $form = $this->createForm(ProjectOwnerOfferType::class, $offer);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('offers_images_directory'),
                    $newFilename
                );
                $offer->setImageUrl($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’upload de l’image.');
            }
        } else {
            $offer->setImageUrl($oldImage);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Offre modifiée avec succès.');

        return $this->redirectToRoute('app_project_owner_offer_list');
    }

    return $this->render('project_owner/offer/edit.html.twig', [
        'form' => $form->createView(),
        'offer' => $offer,
    ]);
}
#[Route('/project-owner/offers', name: 'app_project_owner_offer_list')]
public function list(EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();

    if (!$user) {
        throw $this->createAccessDeniedException('Utilisateur non authentifié.');
    }

    $offers = $entityManager->getRepository(Offers::class)->findBy(
        ['created_by' => $user],
        ['created_at' => 'DESC']
    );

    return $this->render('project_owner/offer/list.html.twig', [
        'offers' => $offers,
    ]);
}
}