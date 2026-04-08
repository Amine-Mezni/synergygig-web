<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class N8nWebhookService
{
    private string $baseUrl;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $n8nBaseUrl = 'http://localhost:5678'
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->baseUrl = rtrim($n8nBaseUrl, '/');
    }

    /**
     * Fire a webhook to n8n.
     *
     * @param string $path Webhook path (e.g. /webhook/training-enroll)
     * @param array  $data Payload to send
     * @return array|null Response data or null on failure
     */
    public function fire(string $path, array $data): ?array
    {
        $url = $this->baseUrl . $path;

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $data,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('n8n webhook fired successfully', [
                    'path' => $path,
                    'status' => $statusCode,
                ]);
                return $response->toArray(false);
            }

            $this->logger->warning('n8n webhook returned non-2xx', [
                'path' => $path,
                'status' => $statusCode,
            ]);
            return null;
        } catch (\Exception $e) {
            // Don't let webhook failures break the app flow
            $this->logger->error('n8n webhook failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Convenience methods ──

    public function trainingEnrolled(int $userId, string $userName, int $courseId, string $courseTitle): ?array
    {
        return $this->fire('/webhook/training-enroll', [
            'user_id' => $userId,
            'user_name' => $userName,
            'course_id' => $courseId,
            'course_title' => $courseTitle,
            'enrolled_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function trainingCompleted(int $userId, string $userName, int $courseId, string $courseTitle, float $score): ?array
    {
        return $this->fire('/webhook/training-complete', [
            'user_id' => $userId,
            'user_name' => $userName,
            'course_id' => $courseId,
            'course_title' => $courseTitle,
            'score' => $score,
            'completed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function leaveStatusChanged(int $leaveId, string $employeeName, string $type, string $status, string $approverName): ?array
    {
        return $this->fire('/webhook/leave-status', [
            'leave_id' => $leaveId,
            'employee_name' => $employeeName,
            'leave_type' => $type,
            'status' => $status,
            'approver_name' => $approverName,
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function contractSigned(int $contractId, string $title, string $signerName, float $amount): ?array
    {
        return $this->fire('/webhook/contract-signed', [
            'contract_id' => $contractId,
            'title' => $title,
            'signer' => $signerName,
            'amount' => $amount,
            'signed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function payrollGenerated(int $count, string $month, float $totalAmount): ?array
    {
        return $this->fire('/webhook/payroll-generated', [
            'count' => $count,
            'month' => $month,
            'total_amount' => $totalAmount,
            'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function interviewScheduled(int $interviewId, string $candidateName, string $position, string $date): ?array
    {
        return $this->fire('/webhook/interview-scheduled', [
            'interview_id' => $interviewId,
            'candidate_name' => $candidateName,
            'position' => $position,
            'date' => $date,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
