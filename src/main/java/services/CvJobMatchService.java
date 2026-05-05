package services;

import java.util.*;

/**
 * Keyword-based CV ↔ Job scoring service.
 * Mirrors the logic of web_synergygig/src/Service/CvJobMatchService.php.
 *
 * Usage:
 *   CvJobMatchService svc = new CvJobMatchService();
 *   Map<String,Object> result = svc.score(cvText, jobTitle, jobDescription);
 *   int score   = (int)    result.get("score");   // 0-100
 *   String label= (String) result.get("label");   // "Excellent" / "Good" / "Fair" / "Low"
 *   List<String> matched = (List<String>) result.get("matched");
 *   List<String> missing = (List<String>) result.get("missing");
 */
public class CvJobMatchService {

    // 25 skill category maps: category name → keywords (lower-case)
    private static final Map<String, List<String>> SKILL_CATEGORIES = new LinkedHashMap<>();

    static {
        SKILL_CATEGORIES.put("Java", Arrays.asList("java", "spring", "spring boot", "hibernate", "maven", "gradle", "jvm", "javafx", "junit"));
        SKILL_CATEGORIES.put("Python", Arrays.asList("python", "django", "flask", "fastapi", "pandas", "numpy", "scikit", "tensorflow", "pytorch", "keras"));
        SKILL_CATEGORIES.put("JavaScript", Arrays.asList("javascript", "typescript", "node", "nodejs", "react", "vue", "angular", "next.js", "webpack", "babel"));
        SKILL_CATEGORIES.put("PHP", Arrays.asList("php", "symfony", "laravel", "wordpress", "composer", "doctrine", "twig", "psr"));
        SKILL_CATEGORIES.put("C#/.NET", Arrays.asList("c#", ".net", "asp.net", "blazor", "entity framework", "nuget", "dotnet"));
        SKILL_CATEGORIES.put("C/C++", Arrays.asList("c++", "c ", "embedded", "stl", "cmake", "opencv", "boost", "qt"));
        SKILL_CATEGORIES.put("Mobile", Arrays.asList("android", "ios", "swift", "kotlin", "flutter", "react native", "xamarin", "mobile"));
        SKILL_CATEGORIES.put("Database", Arrays.asList("sql", "mysql", "postgresql", "mongodb", "redis", "sqlite", "oracle", "nosql", "database", "jdbc", "orm"));
        SKILL_CATEGORIES.put("Cloud/DevOps", Arrays.asList("aws", "azure", "gcp", "docker", "kubernetes", "ci/cd", "jenkins", "gitlab", "github actions", "terraform", "devops", "cloud"));
        SKILL_CATEGORIES.put("Web Frontend", Arrays.asList("html", "css", "scss", "sass", "bootstrap", "tailwind", "responsive", "ui", "ux", "figma", "design"));
        SKILL_CATEGORIES.put("Security", Arrays.asList("security", "oauth", "jwt", "ssl", "tls", "penetration", "cybersecurity", "firewall", "encryption", "owasp"));
        SKILL_CATEGORIES.put("Data Science", Arrays.asList("data science", "machine learning", "deep learning", "nlp", "computer vision", "ai", "neural", "model", "dataset", "analytics"));
        SKILL_CATEGORIES.put("Testing", Arrays.asList("testing", "unit test", "integration test", "selenium", "jest", "pytest", "tdd", "bdd", "qa", "quality assurance"));
        SKILL_CATEGORIES.put("Agile/PM", Arrays.asList("agile", "scrum", "kanban", "jira", "confluence", "sprint", "backlog", "project management", "pm"));
        SKILL_CATEGORIES.put("Linux/Systems", Arrays.asList("linux", "unix", "bash", "shell", "system", "kernel", "server", "networking", "tcp", "http"));
        SKILL_CATEGORIES.put("Version Control", Arrays.asList("git", "github", "gitlab", "bitbucket", "svn", "version control"));
        SKILL_CATEGORIES.put("Microservices/API", Arrays.asList("microservices", "rest", "graphql", "api", "soap", "grpc", "rabbitmq", "kafka", "message queue"));
        SKILL_CATEGORIES.put("HR/Management", Arrays.asList("hr", "human resources", "recruitment", "payroll", "onboarding", "performance review", "employee", "talent", "hiring"));
        SKILL_CATEGORIES.put("Finance/Accounting", Arrays.asList("finance", "accounting", "budget", "invoice", "erp", "sap", "quickbooks", "audit", "tax", "excel"));
        SKILL_CATEGORIES.put("Communication", Arrays.asList("communication", "teamwork", "leadership", "presentation", "negotiation", "english", "french", "arabic"));
        SKILL_CATEGORIES.put("Ruby/Go/Rust", Arrays.asList("ruby", "rails", "golang", "go", "rust", "elixir", "erlang", "haskell"));
        SKILL_CATEGORIES.put("Scala/Kotlin", Arrays.asList("scala", "kotlin", "akka", "play framework", "spark", "flink", "hadoop", "big data"));
        SKILL_CATEGORIES.put("Low-code/BI", Arrays.asList("power bi", "tableau", "looker", "bi", "salesforce", "crm", "low-code", "no-code", "zapier", "n8n"));
        SKILL_CATEGORIES.put("Research/Academia", Arrays.asList("research", "thesis", "publication", "academic", "laboratory", "experiment", "paper", "dissertation"));
        SKILL_CATEGORIES.put("Soft Skills", Arrays.asList("problem solving", "critical thinking", "adaptability", "creativity", "time management", "collaboration", "mentoring", "coaching"));
    }

    /**
     * Score a CV against a job.
     *
     * @param cvText         raw text extracted from the CV (may be null)
     * @param jobTitle       job posting title
     * @param jobDescription job posting description / required skills
     * @return map with keys: score (int 0-100), label (String), matched (List<String>), missing (List<String>)
     */
    public Map<String, Object> score(String cvText, String jobTitle, String jobDescription) {
        String haystack = normalise(cvText);
        String needle   = normalise(jobTitle) + " " + normalise(jobDescription);

        List<String> matched = new ArrayList<>();
        List<String> missing = new ArrayList<>();

        // Determine which categories are relevant to the job
        List<String> relevant = getRelevantCategories(needle);
        if (relevant.isEmpty()) {
            // Fall back: all categories are relevant
            relevant.addAll(SKILL_CATEGORIES.keySet());
        }

        for (String category : relevant) {
            List<String> keywords = SKILL_CATEGORIES.get(category);
            boolean foundInCv  = keywords.stream().anyMatch(k -> haystack.contains(k));
            boolean foundInJob = keywords.stream().anyMatch(k -> needle.contains(k));
            if (foundInJob) {
                if (foundInCv) matched.add(category);
                else           missing.add(category);
            }
        }

        int total = matched.size() + missing.size();
        int score = total > 0 ? (int) Math.round((matched.size() * 100.0) / total) : 0;
        String label = scoreLabel(score);

        Map<String, Object> result = new LinkedHashMap<>();
        result.put("score", score);
        result.put("label", label);
        result.put("matched", matched);
        result.put("missing", missing);
        return result;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private String normalise(String text) {
        if (text == null) return "";
        return text.toLowerCase(Locale.ROOT).replaceAll("[^a-z0-9+#./\\s]", " ");
    }

    private List<String> getRelevantCategories(String jobText) {
        List<String> relevant = new ArrayList<>();
        for (Map.Entry<String, List<String>> entry : SKILL_CATEGORIES.entrySet()) {
            if (entry.getValue().stream().anyMatch(k -> jobText.contains(k))) {
                relevant.add(entry.getKey());
            }
        }
        return relevant;
    }

    private String scoreLabel(int score) {
        if (score >= 70) return "Excellent";
        if (score >= 50) return "Good";
        if (score >= 30) return "Fair";
        return "Low";
    }
}
