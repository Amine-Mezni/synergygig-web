<?php

namespace App\Controller;

use App\Entity\TrainingCourse;
use App\Entity\TrainingCertificate;
use App\Entity\TrainingEnrollment;
use App\Form\TrainingCourseType;
use App\Repository\TrainingCourseRepository;
use App\Repository\TrainingEnrollmentRepository;
use App\Repository\TrainingCertificateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\N8nWebhookService;
use App\Service\NotificationService;

#[Route('/training')]
#[IsGranted('ROLE_USER')]
class TrainingController extends AbstractController
{
    #[Route('/', name: 'app_training_index')]
    public function index(
        Request $request,
        TrainingCourseRepository $courseRepo,
        TrainingEnrollmentRepository $enrollRepo,
        TrainingCertificateRepository $certRepo,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        $tab = $request->query->get('tab', 'dashboard');

        // ── Dashboard stats ──
        $totalCourses = $courseRepo->count(['status' => 'ACTIVE']);
        $myEnrollments = $user ? $enrollRepo->findBy(['user' => $user]) : [];
        $completedCount = 0;
        $totalProgress = 0;
        foreach ($myEnrollments as $e) {
            if ($e->getStatus() === 'COMPLETED') $completedCount++;
            $totalProgress += $e->getProgress() ?? 0;
        }
        $avgProgress = count($myEnrollments) > 0 ? round($totalProgress / count($myEnrollments)) : 0;
        $myCertificates = $user ? $certRepo->findBy(['user' => $user], ['issued_at' => 'DESC']) : [];

        // ── Catalog (paginated) ──
        $qb = $courseRepo->createQueryBuilder('c')->orderBy('c.id', 'DESC');
        $q = $request->query->get('q');
        if ($q) {
            $qb->andWhere('LOWER(c.title) LIKE :q')->setParameter('q', '%' . mb_strtolower($q) . '%');
        }
        $catFilter = $request->query->get('category');
        if ($catFilter) {
            $qb->andWhere('c.category = :cat')->setParameter('cat', $catFilter);
        }
        $diffFilter = $request->query->get('difficulty');
        if ($diffFilter) {
            $qb->andWhere('c.difficulty = :diff')->setParameter('diff', $diffFilter);
        }
        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 12);

        // Build enrollment lookup for current user
        $enrollmentMap = [];
        foreach ($myEnrollments as $e) {
            $enrollmentMap[$e->getCourse()->getId()] = $e;
        }

        // ── Recent enrollments for dashboard ──
        $recentEnrollments = $user ? $enrollRepo->findBy(['user' => $user], ['enrolled_at' => 'DESC'], 5) : [];

        // ── My Learning (sorted: IN_PROGRESS → ENROLLED → COMPLETED) ──
        $statusOrder = ['IN_PROGRESS' => 0, 'ENROLLED' => 1, 'COMPLETED' => 2, 'DROPPED' => 3];
        $learningList = array_filter($myEnrollments, fn($e) => $e->getStatus() !== 'DROPPED');
        usort($learningList, function ($a, $b) use ($statusOrder) {
            return ($statusOrder[$a->getStatus()] ?? 9) - ($statusOrder[$b->getStatus()] ?? 9);
        });

        // ── Certificates (HR/Admin see all, others see own) ──
        $allCertificates = $myCertificates;
        if ($this->isGranted('ROLE_HR')) {
            $allCertificates = $certRepo->findBy([], ['issued_at' => 'DESC']);
        }

        // ── Manage tab courses (HR/Admin only) ──
        $manageCourses = $this->isGranted('ROLE_HR') ? $courseRepo->findBy([], ['id' => 'DESC']) : [];

        return $this->render('training/index.html.twig', [
            'tab' => $tab,
            'courses' => $pagination,
            'pagination' => $pagination,
            'enrollmentMap' => $enrollmentMap,
            // Dashboard
            'totalCourses' => $totalCourses,
            'myEnrollmentCount' => count($myEnrollments),
            'completedCount' => $completedCount,
            'certCount' => count($myCertificates),
            'avgProgress' => $avgProgress,
            'recentEnrollments' => $recentEnrollments,
            // My Learning
            'learningList' => $learningList,
            // Certificates
            'certificates' => $allCertificates,
            // Manage
            'manageCourses' => $manageCourses,
        ]);
    }

    #[Route('/{id}/enroll', name: 'app_training_enroll', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function enroll(
        TrainingCourse $course,
        Request $request,
        EntityManagerInterface $em,
        TrainingEnrollmentRepository $enrollRepo,
        NotificationService $notifier,
        N8nWebhookService $n8n
    ): Response {
        if (!$this->isCsrfTokenValid('enroll' . $course->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_training_index', ['tab' => 'catalog']);
        }

        $user = $this->getUser();
        $existing = $enrollRepo->findOneBy(['course' => $course, 'user' => $user]);
        if ($existing && $existing->getStatus() !== 'DROPPED') {
            $this->addFlash('warning', 'You are already enrolled in this course.');
            return $this->redirectToRoute('app_training_index', ['tab' => 'catalog']);
        }

        // Check max participants
        if ($course->getMaxParticipants() > 0) {
            $currentCount = $enrollRepo->count(['course' => $course]);
            if ($currentCount >= $course->getMaxParticipants()) {
                $this->addFlash('danger', 'This course has reached maximum capacity.');
                return $this->redirectToRoute('app_training_index', ['tab' => 'catalog']);
            }
        }

        if ($existing && $existing->getStatus() === 'DROPPED') {
            $existing->setStatus('ENROLLED');
            $existing->setProgress(0);
            $existing->setScore(null);
            $existing->setEnrolledAt(new \DateTime());
            $existing->setCompletedAt(null);
        } else {
            $enrollment = new TrainingEnrollment();
            $enrollment->setCourse($course);
            $enrollment->setUser($user);
            $enrollment->setStatus('ENROLLED');
            $enrollment->setProgress(0);
            $enrollment->setEnrolledAt(new \DateTime());
            $em->persist($enrollment);
        }

        $em->flush();

        // Fire n8n webhook
        $n8n->trainingEnrolled(
            $user->getId(),
            $user->getFirstName() . ' ' . $user->getLastName(),
            $course->getId(),
            $course->getTitle()
        );

        $this->addFlash('success', 'Successfully enrolled in "' . $course->getTitle() . '"!');
        return $this->redirectToRoute('app_training_index', ['tab' => 'learning']);
    }

    #[Route('/{id}/drop', name: 'app_training_drop', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function drop(
        TrainingCourse $course,
        Request $request,
        EntityManagerInterface $em,
        TrainingEnrollmentRepository $enrollRepo
    ): Response {
        if (!$this->isCsrfTokenValid('drop' . $course->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_training_index', ['tab' => 'learning']);
        }

        $enrollment = $enrollRepo->findOneBy(['course' => $course, 'user' => $this->getUser()]);
        if ($enrollment && $enrollment->getStatus() !== 'COMPLETED') {
            $enrollment->setStatus('DROPPED');
            $em->flush();
            $this->addFlash('success', 'You have dropped "' . $course->getTitle() . '".');
        }
        return $this->redirectToRoute('app_training_index', ['tab' => 'learning']);
    }

    #[Route('/new', name: 'app_training_new')]
    #[IsGranted('ROLE_HR')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $course = new TrainingCourse();
        $form = $this->createForm(TrainingCourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $course->setCreatedAt(new \DateTime());
            $em->persist($course);
            $em->flush();
            $this->addFlash('success', 'Course created.');
            return $this->redirectToRoute('app_training_index');
        }

        return $this->render('training/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_training_show', requirements: ['id' => '\d+'])]
    public function show(
        TrainingCourse $course,
        TrainingEnrollmentRepository $enrollRepo,
        TrainingCertificateRepository $certRepo
    ): Response {
        $enrollments = $enrollRepo->findBy(['course' => $course]);
        $certificates = $certRepo->findBy(['course' => $course]);
        return $this->render('training/show.html.twig', [
            'course' => $course,
            'enrollments' => $enrollments,
            'certificates' => $certificates,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_training_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_HR')]
    public function edit(Request $request, TrainingCourse $course, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TrainingCourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Course updated.');
            return $this->redirectToRoute('app_training_index');
        }

        return $this->render('training/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'course' => $course,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_training_delete', methods: ['POST'])]
    #[IsGranted('ROLE_HR')]
    public function delete(Request $request, TrainingCourse $course, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $em->remove($course);
            $em->flush();
            $this->addFlash('success', 'Course deleted.');
        }
        return $this->redirectToRoute('app_training_index');
    }

    // ── Quiz: start ──

    #[Route('/{id}/quiz', name: 'app_training_quiz', requirements: ['id' => '\d+'])]
    public function quiz(TrainingCourse $course, Request $request): Response
    {
        // Determine effective timer per question (matches Java logic)
        $timer = $course->getQuizTimerSeconds();
        if (!$timer || $timer <= 0) {
            $timer = match ($course->getDifficulty()) {
                'ADVANCED' => 15,
                'INTERMEDIATE' => 12,
                default => 10,
            };
        }

        // Determine difficulty for Open Trivia DB
        $diffMap = ['BEGINNER' => 'easy', 'INTERMEDIATE' => 'medium', 'ADVANCED' => 'hard'];
        $apiDifficulty = $diffMap[$course->getDifficulty()] ?? 'medium';

        // Fetch 5 questions from Open Trivia DB
        $questions = [];
        $apiUrl = 'https://opentdb.com/api.php?amount=5&type=multiple&difficulty=' . $apiDifficulty;

        try {
            $json = @file_get_contents($apiUrl);
            if ($json !== false) {
                $data = json_decode($json, true);
                if (isset($data['results']) && is_array($data['results'])) {
                    foreach ($data['results'] as $i => $q) {
                        $options = $q['incorrect_answers'];
                        $options[] = $q['correct_answer'];
                        shuffle($options);
                        $correctIndex = array_search($q['correct_answer'], $options);

                        $questions[] = [
                            'index' => $i,
                            'question' => html_entity_decode($q['question'], ENT_QUOTES | ENT_HTML5),
                            'category' => html_entity_decode($q['category'], ENT_QUOTES | ENT_HTML5),
                            'options' => array_map(fn($o) => html_entity_decode($o, ENT_QUOTES | ENT_HTML5), $options),
                            'correct' => $correctIndex,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // API failed — generate fallback questions
        }

        if (empty($questions)) {
            $this->addFlash('warning', 'Could not load quiz questions. Please try again.');
            return $this->redirectToRoute('app_training_show', ['id' => $course->getId()]);
        }

        // Store correct answers in session for server-side validation
        $session = $request->getSession();
        $correctAnswers = [];
        foreach ($questions as $q) {
            $correctAnswers[$q['index']] = $q['correct'];
        }
        $session->set('quiz_answers_' . $course->getId(), $correctAnswers);
        $session->set('quiz_course_id', $course->getId());

        // Remove correct answer from data sent to template
        $clientQuestions = array_map(function ($q) {
            return [
                'index' => $q['index'],
                'question' => $q['question'],
                'category' => $q['category'],
                'options' => $q['options'],
            ];
        }, $questions);

        return $this->render('training/quiz.html.twig', [
            'course' => $course,
            'questions' => $clientQuestions,
            'timer' => $timer,
        ]);
    }

    // ── Quiz: submit answers (PHP validation) ──

    #[Route('/{id}/quiz-submit', name: 'app_training_quiz_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quizSubmit(
        TrainingCourse $course,
        Request $request,
        EntityManagerInterface $em,
        TrainingEnrollmentRepository $enrollRepo,
        TrainingCertificateRepository $certRepo,
        N8nWebhookService $n8n,
        NotificationService $notifier
    ): Response {
        // CSRF check
        if (!$this->isCsrfTokenValid('quiz' . $course->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_training_show', ['id' => $course->getId()]);
        }

        // Retrieve correct answers from session
        $session = $request->getSession();
        $correctAnswers = $session->get('quiz_answers_' . $course->getId());
        if (!$correctAnswers || $session->get('quiz_course_id') !== $course->getId()) {
            $this->addFlash('danger', 'Quiz session expired. Please start the quiz again.');
            return $this->redirectToRoute('app_training_show', ['id' => $course->getId()]);
        }

        // Validate submitted answers server-side
        $errors = [];
        $submittedAnswers = $request->request->all('answers');
        if (!is_array($submittedAnswers)) {
            $errors[] = 'No answers submitted.';
        }

        $totalQuestions = count($correctAnswers);
        if (count($submittedAnswers) !== $totalQuestions) {
            $errors[] = 'You must answer all ' . $totalQuestions . ' questions.';
        }

        // Validate each answer is an integer 0-3
        foreach ($submittedAnswers as $qIdx => $answer) {
            $answer = (int) $answer;
            if ($answer < 0 || $answer > 3) {
                $errors[] = 'Invalid answer for question ' . ($qIdx + 1) . '.';
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $e) {
                $this->addFlash('danger', $e);
            }
            return $this->redirectToRoute('app_training_quiz', ['id' => $course->getId()]);
        }

        // Calculate score
        $correct = 0;
        $results = [];
        foreach ($correctAnswers as $qIdx => $correctIdx) {
            $submitted = isset($submittedAnswers[$qIdx]) ? (int) $submittedAnswers[$qIdx] : -1;
            $isCorrect = ($submitted === $correctIdx);
            if ($isCorrect) {
                $correct++;
            }
            $results[] = [
                'index' => $qIdx,
                'submitted' => $submitted,
                'correct' => $correctIdx,
                'isCorrect' => $isCorrect,
            ];
        }

        $scorePercent = ($correct / $totalQuestions) * 100;
        $passed = $scorePercent >= 70;

        // Find the current user's enrollment
        $enrollment = $enrollRepo->findOneBy(['course' => $course, 'user' => $this->getUser()]);
        $certificateGenerated = false;

        if ($enrollment) {
            $enrollment->setScore($scorePercent);

            if ($passed) {
                $enrollment->setStatus('COMPLETED');
                $enrollment->setProgress(100);
                $enrollment->setCompletedAt(new \DateTime());

                // Generate certificate if not already exists
                $existingCert = $certRepo->findOneBy(['enrollment' => $enrollment]);
                if (!$existingCert) {
                    $year = date('Y');
                    $uid = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                    $certNumber = 'SG-' . $year . '-C' . $course->getId() . '-E' . $enrollment->getId() . '-' . $uid;

                    $cert = new TrainingCertificate();
                    $cert->setEnrollment($enrollment);
                    $cert->setUser($enrollment->getUser());
                    $cert->setCourse($course);
                    $cert->setCertificateNumber($certNumber);
                    $cert->setIssuedAt(new \DateTime());
                    $em->persist($cert);
                    $certificateGenerated = true;
                }
            }

            $em->flush();

            // Fire n8n webhook on training completion
            if ($passed) {
                $n8n->trainingCompleted(
                    $enrollment->getUser()->getId(),
                    $enrollment->getUser()->getFirstName() . ' ' . $enrollment->getUser()->getLastName(),
                    $course->getId(),
                    $course->getTitle(),
                    $scorePercent
                );
                $notifier->trainingCompleted($enrollment->getUser(), $course->getId(), $course->getTitle(), $scorePercent);
            }
        }

        // Clear quiz session data
        $session->remove('quiz_answers_' . $course->getId());
        $session->remove('quiz_course_id');

        return $this->render('training/quiz_result.html.twig', [
            'course' => $course,
            'results' => $results,
            'correct' => $correct,
            'total' => $totalQuestions,
            'scorePercent' => $scorePercent,
            'passed' => $passed,
            'certificateGenerated' => $certificateGenerated,
        ]);
    }

    // ── Certificate: sign ──

    #[Route('/certificate/{id}/sign', name: 'app_training_certificate_sign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function signCertificate(
        TrainingCertificate $certificate,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('certsign' . $certificate->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_training_show', ['id' => $certificate->getCourse()->getId()]);
        }

        $errors = [];

        // Validate signature data (must be data:image/png;base64,...)
        $signatureData = $request->request->get('signature_data', '');
        if (empty($signatureData)) {
            $errors[] = 'Signature is required.';
        } elseif (!preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $signatureData)) {
            $errors[] = 'Invalid signature format.';
        } elseif (strlen($signatureData) > 500000) {
            $errors[] = 'Signature data is too large.';
        }

        if (!empty($errors)) {
            foreach ($errors as $e) {
                $this->addFlash('danger', $e);
            }
            return $this->redirectToRoute('app_training_show', ['id' => $certificate->getCourse()->getId()]);
        }

        $certificate->setSignatureData($signatureData);
        $certificate->setSignedAt(new \DateTime());
        if ($certificate->getUser()) {
            $certificate->setSignedByUserId($certificate->getUser()->getId());
        }

        $em->flush();
        $this->addFlash('success', 'Certificate signed successfully!');

        return $this->redirectToRoute('app_training_show', ['id' => $certificate->getCourse()->getId()]);
    }
}
