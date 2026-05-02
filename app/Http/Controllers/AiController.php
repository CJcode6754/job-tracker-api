<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    // -------------------------------------------------------
    // 💬 CHATBOT
    // -------------------------------------------------------
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message'        => 'required|string|max:1000',
            'history'        => 'array',
            'history.*.role' => 'required|in:user,model',
            'history.*.text' => 'required|string',
        ]);

        $user         = $request->user();
        $applications = $user->applications()
            ->with(['contacts', 'interviewRounds'])
            ->latest()
            ->get();

        $appContext = $this->buildApplicationContext($applications);

        $systemPrompt = <<<PROMPT
You are a helpful job search assistant. The user is tracking their job applications.
Here is their current application data:

{$appContext}

Today's date is: {$this->todayDate()}.

Answer questions about their applications clearly and concisely.
When listing applications, use bullet points.
If asked about deadlines or dates, calculate how many days remain from today.
If asked for advice, be encouraging but realistic.
Do not make up any application data — only use what is provided above.
If there are no applications, tell the user to start adding some.
PROMPT;

        $reply = $this->gemini->chat(
            $systemPrompt,
            $request->input('history', []),
            $request->input('message')
        );

        return response()->json(['reply' => $reply]);
    }

    // -------------------------------------------------------
    // ✍️ COVER LETTER GENERATOR
    // -------------------------------------------------------
    public function coverLetter(Request $request): JsonResponse
    {
        $request->validate([
            'company'         => 'required|string',
            'role'            => 'required|string',
            'job_description' => 'nullable|string|max:3000',
            'notes'           => 'nullable|string|max:1000',
            'user_background' => 'nullable|string|max:1000',
        ]);

        $company    = $request->company;
        $role       = $request->role;
        $jd         = $request->input('job_description', 'Not provided');
        $notes      = $request->input('notes', 'None');
        $background = $request->input('user_background', 'Not provided');

        $prompt = <<<PROMPT
Write a professional, concise cover letter for the following job application.

Company: {$company}
Role: {$role}
Job Description: {$jd}
My Notes about this role: {$notes}
My Background: {$background}

Guidelines:
- Keep it to 3 paragraphs maximum
- Opening: Express genuine interest in the specific role and company
- Middle: Connect 2-3 relevant skills/experiences to the job requirements
- Closing: Call to action, professional sign-off
- Tone: Professional but personable, not robotic
- Do NOT use placeholder text like [Your Name] — write it as a ready-to-use draft
- Do NOT include date or address headers — just the body paragraphs
PROMPT;

        $letter = $this->gemini->generate($prompt);

        return response()->json(['cover_letter' => $letter]);
    }

    // -------------------------------------------------------
    // 📊 PIPELINE INSIGHTS
    // -------------------------------------------------------
    public function insights(Request $request): JsonResponse
    {
        $user         = $request->user();
        $applications = $user->applications()->with('interviewRounds')->get();

        if ($applications->isEmpty()) {
            return response()->json([
                'insights' => 'You have no applications yet. Start adding some to get AI-powered insights!',
            ]);
        }

        $appContext = $this->buildApplicationContext($applications);

        $prompt = <<<PROMPT
Analyze this job seeker's application pipeline and provide actionable insights.

{$appContext}

Today's date: {$this->todayDate()}

Provide exactly 4-5 insights covering:
1. Pipeline health (response rate, conversion between stages)
2. Any applications that need follow-up (applied 7+ days ago, no update)
3. Deadlines coming up in the next 7 days
4. One encouraging observation
5. One specific recommendation to improve their job search

Format each insight as a short paragraph with an emoji at the start.
Be specific — reference actual companies and numbers from the data.
PROMPT;

        $insights = $this->gemini->generate($prompt);

        return response()->json(['insights' => $insights]);
    }

    // -------------------------------------------------------
    // 🏷️ JOB DESCRIPTION AUTO-TAGGER
    // -------------------------------------------------------
    public function tagJobDescription(Request $request): JsonResponse
    {
        $request->validate([
            'job_description' => 'required|string|max:5000',
        ]);

        $jd = $request->job_description;

        $prompt = <<<PROMPT
Extract structured information from this job description.
Return ONLY a valid JSON object with no explanation, no markdown, no code fences.

Job Description:
{$jd}

Return this exact JSON structure:
{
  "role_title": "extracted job title",
  "seniority": "junior | mid | senior | lead | staff | not specified",
  "employment_type": "full-time | part-time | contract | freelance | not specified",
  "remote_policy": "remote | hybrid | on-site | not specified",
  "tech_stack": ["list", "of", "technologies"],
  "key_requirements": ["top 3-4 must-have requirements"],
  "salary_range": "extracted range or null",
  "company_size_hint": "startup | mid-size | enterprise | not mentioned",
  "estimated_priority": "high | medium | low",
  "priority_reason": "one sentence explaining why"
}
PROMPT;

        $raw  = $this->gemini->generate($prompt);
        $json = preg_replace('/```json|```/', '', $raw);
        $tags = json_decode(trim($json), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Could not parse AI response. Try again.'], 422);
        }

        return response()->json(['tags' => $tags]);
    }

    // -------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------
    private function buildApplicationContext($applications): string
    {
        if ($applications->isEmpty()) {
            return 'The user has no applications yet.';
        }

        $lines = [];

        foreach ($applications as $app) {
            $line = "- [{$app->status}] {$app->company} — {$app->role}";

            if ($app->applied_date) $line .= " | Applied: {$app->applied_date}";
            if ($app->deadline)     $line .= " | Deadline: {$app->deadline}";
            if ($app->priority)     $line .= " | Priority: {$app->priority}";

            if ($app->salary_min || $app->salary_max) {
                $min   = $app->salary_min ? number_format($app->salary_min) : '?';
                $max   = $app->salary_max ? number_format($app->salary_max) : '?';
                $line .= " | Salary: {$app->salary_currency} {$min}–{$max}";
            }

            if ($app->interviewRounds->count() > 0) {
                $line .= " | Interview rounds: {$app->interviewRounds->count()}";
            }

            if ($app->notes) {
                $notes = substr(strip_tags($app->notes), 0, 150);
                $line .= " | Notes: {$notes}";
            }

            $lines[] = $line;
        }

        $total    = $applications->count();
        $byStatus = $applications->groupBy('status')->map->count();
        $summary  = "Total: {$total} applications. ";

        foreach ($byStatus as $status => $count) {
            $summary .= "{$status}: {$count}, ";
        }

        return rtrim($summary, ', ') . "\n\n" . implode("\n", $lines);
    }

    private function todayDate(): string
    {
        return now()->format('Y-m-d');
    }
}
