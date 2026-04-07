<?php

namespace App\Controller\Admin;

use App\Entity\Offers;
use App\Form\OffersType;
use App\Repository\OffersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class OfferController extends AbstractController
{
    #[Route('/admin/offers', name: 'app_admin_offers')]
    public function index(OffersRepository $offersRepository): Response
    {
        $offers = $offersRepository->findAll();

        return $this->render('admin/offer/index.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/admin/offers/{id}/publish', name: 'app_admin_offer_publish')]
    public function publish(Offers $offer, EntityManagerInterface $entityManager): RedirectResponse
    {
        $offer->setStatus('PUBLISHED');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_offers');
    }

   

   #[Route('/admin/offers/{id}/edit', name: 'app_admin_offer_edit')]
public function edit(
    Request $request,
    Offers $offer,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger
): Response {
    $form = $this->createForm(OffersType::class, $offer);
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
        }

        $entityManager->flush();

        $this->addFlash('success', 'Modification effectuée avec succès.');

        return $this->redirectToRoute('app_admin_offer_edit', [
            'id' => $offer->getId(),
        ]);
    }

    return $this->render('admin/offer/edit.html.twig', [
        'form' => $form->createView(),
        'offer' => $offer,
    ]);
}
}