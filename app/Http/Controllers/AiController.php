<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    // CHATBOT
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

    // COVER LETTER GENERATOR
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
        Write a professional, compelling cover letter for the following job application.

        Company: {$company}
        Role: {$role}
        Job Description: {$jd}
        My Notes about this role: {$notes}
        My Background: {$background}

        Guidelines:
        - Write 4 full paragraphs — do not cut short
        - Opening: Express genuine enthusiasm for the specific role and company, mention something specific about the company
        - Second paragraph: Highlight 2-3 concrete achievements or experiences directly relevant to the role
        - Third paragraph: Connect your skills to the job requirements, show you understand what they need
        - Closing: Strong call to action, express eagerness for an interview, professional sign-off
        - Tone: Confident, professional but personable — not robotic or generic
        - Be specific — use numbers, results, and real examples where possible
        - Do NOT use placeholder text like [Your Name] or [Date] — write it as a complete ready-to-send draft
        - Do NOT include date or address headers — just the body paragraphs
        - Aim for 300-400 words
        PROMPT;

        $letter = $this->gemini->generate($prompt);

        return response()->json(['cover_letter' => $letter]);
    }

    // PIPELINE INSIGHTS
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
        Analyze this job seeker's application pipeline and provide detailed, actionable insights.

        {$appContext}

        Today's date: {$this->todayDate()}

        Provide 5 detailed insights covering:
        1. 📊 Pipeline health — response rate, conversion between stages, how active the search is
        2. 🔔 Follow-up needed — applications with no update after 7+ days, specific companies to follow up with
        3. ⏰ Upcoming deadlines — any deadlines in the next 7 days, urgency level
        4. ⭐ Wins & encouragement — highlight positive progress, interviews, offers
        5. 💡 Specific recommendation — one concrete action to improve their job search this week

        For each insight:
        - Write 2-4 sentences minimum
        - Reference specific company names and numbers from the data
        - Be direct and actionable, not vague
        - If data is missing for a section, provide general advice based on what you can see
        - Do NOT use markdown formatting, headings, or bold text — plain text only
        PROMPT;

        $insights = $this->gemini->generate($prompt);

        return response()->json(['insights' => $insights]);
    }

    // JOB DESCRIPTION AUTO-TAGGER
    public function tagJobDescription(Request $request): JsonResponse
    {
        $request->validate([
            'job_description' => 'required|string|max:5000',
        ]);

        $jd = mb_substr($request->job_description, 0, 2000);

        $prompt = <<<PROMPT
        Extract key info from this job description. Be concise.

        Job Description:
        {$jd}

        Return this JSON (no extra text, no markdown, no code fences):
        {
        "role_title": "job title",
        "company": "company name or null",
        "location": "location or null",
        "seniority": "junior|mid|senior|lead|not specified",
        "employment_type": "full-time|part-time|contract|freelance|not specified",
        "remote_policy": "remote|hybrid|on-site|not specified",
        "tech_stack": ["max 5 main technologies"],
        "key_requirements": ["top 3 must-haves"],
        "salary_range": "range or null",
        "company_size_hint": "startup|mid-size|enterprise|not mentioned",
        "estimated_priority": "high|medium|low",
        "priority_reason": "one short sentence"
        }
        PROMPT;

        $raw  = $this->gemini->generateJson($prompt);
        Log::info('Gemini raw response', ['raw' => $raw]);

        // Extract JSON — handle markdown fences and any surrounding text
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $raw, $matches)) {
            $json = $matches[1];
        } else {
            $json = $raw;
        }

        $tags = json_decode(trim($json), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AI tag parse failed', ['raw' => $raw]);
            return response()->json(['error' => 'Could not parse AI response. Try again.'], 422);
        }

        return response()->json(['tags' => $tags]);
    }

    // HELPERS
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
                $rounds = $app->interviewRounds->map(fn($r) =>
                    "{$r->type}" . ($r->date ? " on {$r->date}" : '') . ($r->self_rating ? " (rated {$r->self_rating}/5)" : '')
                )->join(', ');
                $line .= " | Rounds: {$rounds}";
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
