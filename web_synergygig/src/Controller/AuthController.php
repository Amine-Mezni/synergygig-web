<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    private const FACE_LOGIN_THRESHOLD = 0.16;
    private const FACE_PYTHON_TIMEOUT_SECONDS = 15;

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        return $this->render('auth/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/face', name: 'app_login_face', methods: ['POST'])]
    public function loginWithFace(
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        @set_time_limit(20);

        $imageData = (string) $request->request->get('face_image', '');
        $email = strtolower(trim((string) $request->request->get('email', '')));

        if ($imageData === '') {
            return $this->json([
                'success' => false,
                'message' => 'No face image captured.',
            ], 400);
        }

        if (str_contains($imageData, ',')) {
            $imageData = explode(',', $imageData, 2)[1];
        }

        $decoded = base64_decode($imageData, true);
        if ($decoded === false) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid face image data.',
            ], 400);
        }

        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'face_login_' . uniqid('', true) . '.png';
        file_put_contents($tmpFile, $decoded);

        try {
            $capturedEncoding = $this->extractFaceEncodingFromImage($tmpFile);
            if ($capturedEncoding === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'No face detected. Try again with better lighting and a centered face.',
                ], 422);
            }

            $bestUser = null;
            $bestDistance = 1.0;

            if ($email !== '') {
                $targetUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if (!$targetUser || !$targetUser->isActive()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Account not found for this email.',
                    ], 401);
                }

                $storedRaw = $targetUser->getFaceEncoding();
                if (!$storedRaw) {
                    return $this->json([
                        'success' => false,
                        'message' => 'No Face ID enrolled for this account. Please enroll first.',
                    ], 409);
                }

                $stored = json_decode($storedRaw, true);
                if (!is_array($stored) || $stored === []) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Stored Face ID data is invalid. Please re-enroll.',
                    ], 409);
                }

                $distance = $this->cosineDistance($capturedEncoding, $stored);
                if ($distance === null || $distance > self::FACE_LOGIN_THRESHOLD) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Face did not match this email account. Try again or use password login.',
                    ], 401);
                }

                $bestUser = $targetUser;
                $bestDistance = $distance;
            } else {
                $users = $em->createQueryBuilder()
                    ->select('u')
                    ->from(User::class, 'u')
                    ->where('u.face_encoding IS NOT NULL')
                    ->andWhere('u.is_active = :active')
                    ->setParameter('active', true)
                    ->getQuery()
                    ->getResult();

                /** @var User $candidate */
                foreach ($users as $candidate) {
                    $storedRaw = $candidate->getFaceEncoding();
                    if (!$storedRaw) {
                        continue;
                    }

                    $stored = json_decode($storedRaw, true);
                    if (!is_array($stored) || $stored === []) {
                        continue;
                    }

                    $distance = $this->cosineDistance($capturedEncoding, $stored);
                    if ($distance === null) {
                        continue;
                    }

                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestUser = $candidate;
                    }
                }

                if (!$bestUser || $bestDistance > self::FACE_LOGIN_THRESHOLD) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Face not recognized. Use email/password or re-enroll Face ID.',
                    ], 401);
                }
            }

            $response = $security->login($bestUser, null, 'main');
            if ($response instanceof Response) {
                return $this->json([
                    'success' => true,
                    'message' => 'Face recognized. Signing you in...',
                    'redirect' => $response->headers->get('Location') ?: $this->generateUrl('app_dashboard'),
                ]);
            }

            return $this->json([
                'success' => true,
                'message' => 'Face recognized. Signing you in...',
                'redirect' => $this->generateUrl('app_dashboard'),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Face login failed: ' . $e->getMessage(),
            ], 500);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('app_signup');
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setRole('EMPLOYEE');
            $user->setCreatedAt(new \DateTime());
            $user->setIsActive(true);
            $user->setIsVerified(false);
            $user->setIsOnline(false);
            $user->setPassword($hasher->hashPassword($user, $data['password']));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Account created! You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Symfony's security system intercepts this route
        throw new \LogicException('This should never be reached.');
    }

    private function extractFaceEncodingFromImage(string $imagePath): ?array
    {
        $pythonScript = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'face_encode_image.py';
        $cmd = sprintf('python "%s" "%s"', str_replace('/', DIRECTORY_SEPARATOR, $pythonScript), $imagePath);
        $output = $this->runProcessWithTimeout($cmd, self::FACE_PYTHON_TIMEOUT_SECONDS);

        if ($output === null || trim($output) === '') {
            throw new \RuntimeException('Face recognition service unavailable.');
        }

        $jsonStart = strpos($output, '{');
        if ($jsonStart === false) {
            throw new \RuntimeException('Unexpected face recognition output.');
        }

        $result = json_decode(substr($output, $jsonStart), true);
        if (!is_array($result) || !array_key_exists('success', $result)) {
            throw new \RuntimeException('Invalid face recognition response.');
        }

        if (!$result['success']) {
            $error = strtolower((string) ($result['error'] ?? ''));
            if (str_contains($error, 'no face') || str_contains($error, 'multiple faces')) {
                return null;
            }
            throw new \RuntimeException((string) ($result['error'] ?? 'Face encoding failed.'));
        }

        $encoding = $result['encoding'] ?? null;
        if (is_string($encoding)) {
            $encoding = json_decode($encoding, true);
        }

        if (!is_array($encoding) || $encoding === []) {
            throw new \RuntimeException('Invalid encoding vector.');
        }

        return $encoding;
    }

    private function cosineDistance(array $a, array $b): ?float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return null;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $normA += $x * $x;
            $normB += $y * $y;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return null;
        }

        $cosine = $dot / (sqrt($normA) * sqrt($normB));
        $cosine = max(-1.0, min(1.0, $cosine));

        return 1.0 - $cosine;
    }

    private function runProcessWithTimeout(string $command, int $timeoutSeconds): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start face recognition process.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);

        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $start) > $timeoutSeconds) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                throw new \RuntimeException('Face verification timed out. Please retry with better lighting.');
            }

            usleep(100000);
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $combined = trim($stdout . PHP_EOL . $stderr);
        if ($combined === '') {
            throw new \RuntimeException('Face recognition service unavailable.');
        }

        return $combined;
    }
}
