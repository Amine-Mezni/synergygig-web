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
    private const FACE_LOGIN_THRESHOLD = 0.11;
    private const FACE_SINGLE_USER_THRESHOLD = 0.08;
    private const FACE_RATIO_MARGIN = 0.50;
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
                if ($distance === null || $distance > self::FACE_SINGLE_USER_THRESHOLD) {
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

                $allDistances = [];

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

                    $allDistances[] = $distance;

                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestUser = $candidate;
                    }
                }

                $effectiveThreshold = count($allDistances) === 1
                    ? self::FACE_SINGLE_USER_THRESHOLD
                    : self::FACE_LOGIN_THRESHOLD;

                if (!$bestUser || $bestDistance > $effectiveThreshold) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Face not recognized. Use email/password or re-enroll Face ID.',
                    ], 401);
                }

                // Ratio test: best must be significantly better than second best
                if (count($allDistances) >= 2) {
                    sort($allDistances);
                    $secondBest = $allDistances[1];
                    if ($secondBest > 1e-9 && ($bestDistance / $secondBest) > self::FACE_RATIO_MARGIN) {
                        return $this->json([
                            'success' => false,
                            'message' => 'Face match is ambiguous. Please enter your email and try again.',
                        ], 401);
                    }
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
            $user->setRole($data['role']);
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

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            if ($email === '') {
                $this->addFlash('error', 'Please enter your email address.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'No account found with this email address.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Generate 6-digit OTP
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setResetToken(password_hash($otp, PASSWORD_BCRYPT));
            $user->setResetTokenExpiresAt(new \DateTime('+10 minutes'));
            $em->flush();

            // Store email in session for the verify step
            $request->getSession()->set('reset_email', $email);

            // Send OTP via Brevo HTTP API (direct curl)
            $emailSent = false;
            $apiKey = $_ENV['BREVO_API_KEY'] ?? '';

            if ($apiKey !== '') {
                $htmlBody =
                    '<div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#0f172a;color:#e2e8f0;border-radius:12px">' .
                    '<h2 style="text-align:center;color:#8b5cf6;margin:0 0 8px">SynergyGig</h2>' .
                    '<p style="text-align:center;color:#94a3b8;font-size:14px;margin:0 0 24px">Password Reset Code</p>' .
                    '<div style="text-align:center;padding:20px;background:rgba(139,92,246,0.1);border-radius:8px;margin:0 0 24px">' .
                    '<span style="font-size:32px;font-weight:800;letter-spacing:8px;color:#3b82f6">' . $otp . '</span>' .
                    '</div>' .
                    '<p style="text-align:center;color:#94a3b8;font-size:13px;margin:0">This code expires in 10 minutes.<br>If you did not request this, ignore this email.</p>' .
                    '</div>';

                $payload = json_encode([
                    'sender' => ['name' => 'SynergyGig', 'email' => 'mohamedsejibouallegue@gmail.com'],
                    'to' => [['email' => $email, 'name' => $user->getFirstName() . ' ' . $user->getLastName()]],
                    'subject' => 'SynergyGig - Password Reset Code',
                    'htmlContent' => $htmlBody,
                ]);

                $ch = curl_init('https://api.brevo.com/v3/smtp/email');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => [
                        'api-key: ' . $apiKey,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $emailSent = ($httpCode >= 200 && $httpCode < 300);
            }

            // Show OTP on screen as fallback if email fails
            $request->getSession()->set('reset_otp_display', $emailSent ? null : $otp);

            return $this->redirectToRoute('app_verify_otp');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/verify-otp', name: 'app_verify_otp', methods: ['GET', 'POST'])]
    public function verifyOtp(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $email = $request->getSession()->get('reset_email', '');
        if ($email === '') {
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $otp = trim((string) $request->request->get('otp', ''));
            if (strlen($otp) !== 6) {
                $this->addFlash('error', 'Please enter the 6-digit code.');
                return $this->render('auth/verify_otp.html.twig', ['email' => $email]);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user || !$user->getResetToken() || !$user->getResetTokenExpiresAt()) {
                $this->addFlash('error', 'Invalid or expired reset request. Please try again.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if ($user->getResetTokenExpiresAt() < new \DateTime()) {
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();
                $this->addFlash('error', 'The code has expired. Please request a new one.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if (!password_verify($otp, $user->getResetToken())) {
                $this->addFlash('error', 'Invalid code. Please try again.');
                return $this->render('auth/verify_otp.html.twig', ['email' => $email]);
            }

            // OTP verified — allow password reset
            $request->getSession()->set('reset_verified', true);
            return $this->redirectToRoute('app_reset_password');
        }

        $otpDisplay = $request->getSession()->get('reset_otp_display');
        $request->getSession()->remove('reset_otp_display');

        return $this->render('auth/verify_otp.html.twig', [
            'email' => $email,
            'otp_display' => $otpDisplay,
        ]);
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $email = $request->getSession()->get('reset_email', '');
        $verified = $request->getSession()->get('reset_verified', false);
        if ($email === '' || !$verified) {
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password', '');
            $confirm = (string) $request->request->get('password_confirm', '');

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Password must be at least 8 characters.');
                return $this->render('auth/reset_password.html.twig');
            }
            if ($password !== $confirm) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->render('auth/reset_password.html.twig');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                return $this->redirectToRoute('app_forgot_password');
            }

            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $em->flush();

            // Cleanup session
            $request->getSession()->remove('reset_email');
            $request->getSession()->remove('reset_verified');

            $this->addFlash('success', 'Password reset successfully! You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig');
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

        $out = trim($stdout);
        if ($out === '') {
            throw new \RuntimeException('Face recognition service unavailable.');
        }

        return $out;
    }
}
