<?php

namespace App\Service;

use App\Entity\Offers;
use Doctrine\DBAL\Connection;

class ApplicationMatchService
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function matchOfferToUser(Offers $offer, int $userId): array
    {
        $userSkills = $this->getUserSkills($userId);
        $expectedSkills = $this->extractExpectedSkills($offer);

        if (empty($expectedSkills)) {
            return [
                'score' => 50,
                'label' => 'Analyse partielle',
                'matched_skills' => [],
                'missing_skills' => [],
                'user_skills' => array_keys($userSkills),
                'expected_skills' => [],
                'summary' => 'Aucune compétence cible claire n’a pu être détectée dans cette offre. Le matching reste partiel.',
                'strengths' => ['Profil utilisateur chargé'],
                'warnings' => ['Compétences attendues non détectées automatiquement'],
            ];
        }

        $matched = [];
        $missing = [];
        $weightedScore = 0;
        $maxScore = 0;

        foreach ($expectedSkills as $skill) {
            $maxScore += 100;

            $normalizedSkill = $this->normalize($skill);

            if (isset($userSkills[$normalizedSkill])) {
                $level = $userSkills[$normalizedSkill];

                $skillWeight = match ($level) {
                    'ADVANCED' => 100,
                    'INTERMEDIATE' => 75,
                    'BEGINNER' => 55,
                    default => 50,
                };

                $weightedScore += $skillWeight;
                $matched[] = [
                    'name' => $skill,
                    'level' => $level,
                ];
            } else {
                $missing[] = $skill;
            }
        }

        $score = $maxScore > 0 ? (int) round(($weightedScore / $maxScore) * 100) : 0;

        $label = match (true) {
            $score >= 85 => 'Très favorable',
            $score >= 70 => 'Bon match',
            $score >= 50 => 'Moyen',
            default => 'Faible compatibilité',
        };

        $strengths = [];
        $warnings = [];

        if (!empty($matched)) {
            $strengths[] = count($matched) . ' compétence(s) détectée(s) en correspondance';
        }

        if (count($missing) === 0) {
            $strengths[] = 'Aucune compétence clé manquante détectée';
        } else {
            $warnings[] = count($missing) . ' compétence(s) clé(s) semblent manquer';
        }

        if ($score >= 70) {
            $strengths[] = 'Le profil semble cohérent avec les besoins de l’offre';
        } else {
            $warnings[] = 'Le profil nécessite un renforcement pour améliorer la compatibilité';
        }

        return [
            'score' => $score,
            'label' => $label,
            'matched_skills' => $matched,
            'missing_skills' => $missing,
            'user_skills' => array_keys($userSkills),
            'expected_skills' => $expectedSkills,
            'summary' => $this->buildSummary($offer, $score, $matched, $missing),
            'strengths' => $strengths,
            'warnings' => $warnings,
        ];
    }

    private function getUserSkills(int $userId): array
    {
        $sql = <<<SQL
            SELECT s.name, us.level
            FROM user_skills us
            INNER JOIN skills s ON s.id = us.skill_id
            WHERE us.user_id = :userId
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql, [
            'userId' => $userId,
        ]);

        $skills = [];

        foreach ($rows as $row) {
            $skills[$this->normalize($row['name'])] = $row['level'];
        }

        return $skills;
    }

    private function extractExpectedSkills(Offers $offer): array
    {
       $text = $this->normalizeText(
    trim(($offer->getTitle() ?? '') . ' ' . ($offer->getDescription() ?? ''))
);

       $map = [
    'Java' => ['java', 'jdbc'],
    'Spring Boot' => ['spring boot', 'spring'],
    'Symfony' => ['symfony'],
    'PHP' => ['php'],
    'MySQL' => ['mysql', 'mariadb', 'sql'],
    'API REST' => ['api', 'rest', 'restful'],
    'UI/UX' => ['ui', 'ux', 'figma', 'design interface', 'design', 'maquette'],
    'JavaScript' => ['javascript', 'js'],
    'React' => ['react', 'next.js', 'nextjs'],
    'SEO' => ['seo', 'search console', 'analytics'],
    'Docker' => ['docker', 'container'],
    'Git' => ['git', 'github', 'gitlab'],

    // AJOUTS IMPORTANTS
    'Web Development' => ['web', 'site web', 'website', 'développement web', 'developpement web'],
    'Frontend' => ['frontend', 'front-end', 'html', 'css', 'bootstrap', 'tailwind'],
    'Backend' => ['backend', 'back-end', 'serveur', 'server'],
    'WordPress' => ['wordpress', 'cms'],
    'Graphic Design' => ['logo', 'photoshop', 'illustrator', 'branding'],
];

        $detected = [];

        foreach ($map as $skillName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, mb_strtolower($keyword))) {
                    $detected[] = $skillName;
                    break;
                }
            }
        }

        return array_values(array_unique($detected));
    }

    private function buildSummary(Offers $offer, int $score, array $matched, array $missing): string
    {
        $matchedCount = count($matched);
        $missingCount = count($missing);

        if ($score >= 85) {
            return "Le profil utilisateur est fortement aligné avec cette offre. {$matchedCount} compétence(s) clé(s) correspondent directement aux besoins détectés.";
        }

        if ($score >= 70) {
            return "Le profil présente une bonne compatibilité avec cette offre. {$matchedCount} compétence(s) correspondent, avec quelques axes de renforcement.";
        }

        if ($score >= 50) {
            return "La compatibilité reste moyenne. Le profil couvre une partie des besoins, mais {$missingCount} compétence(s) importante(s) semblent manquer.";
        }

        return "La compatibilité actuelle paraît faible. Plusieurs compétences clés ne sont pas encore couvertes par le profil utilisateur.";
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
    private function normalizeText(string $text): string
{
    $text = mb_strtolower(trim($text));

    $replacements = [
        'professionel' => 'professionnel',
        'profesionel' => 'professionnel',
        'developpement' => 'développement',
        'developpeur' => 'développeur',
        'siteweb' => 'site web',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}
}