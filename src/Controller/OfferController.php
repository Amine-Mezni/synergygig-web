<?php

namespace App\Controller;

use App\Repository\OffersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}