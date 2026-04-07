<?php

namespace App\Controller;

use App\Service\N8nWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/job-scanner')]
class JobScannerController extends AbstractController
{
    public function __construct(private N8nWebhookService $n8n) {}

    #[Route('', name: 'app_job_scanner')]
    public function index(): Response
    {
        return $this->render('job_scanner/index.html.twig');
    }

    #[Route('/scan', name: 'app_job_scanner_scan', methods: ['POST'])]
    public function scan(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $query    = trim($data['query'] ?? '');
        $source   = $data['source'] ?? 'all';
        $jobType  = $data['jobType'] ?? 'all';
        $datePosted = $data['datePosted'] ?? 'any';
        $location = trim($data['location'] ?? '');

        if (strlen($query) < 2) {
            return $this->json(['error' => 'Please enter a job title to search.'], 422);
        }

        $result = $this->n8n->fire('/webhook/job-search', [
            'query'      => $query,
            'source'     => strtolower($source),
            'jobType'    => $jobType === 'All Types' ? 'all' : strtolower($jobType),
            'datePosted' => $datePosted === 'Any time' ? 'any' : strtolower($datePosted),
            'location'   => $location,
        ]);

        if ($result === null) {
            return $this->json(['error' => 'Job search service unavailable. Please try again later.'], 503);
        }

        $jobs  = $result['results'] ?? [];
        $count = $result['count'] ?? count($jobs);

        return $this->json([
            'results' => $jobs,
            'count'   => $count,
        ]);
    }
}
