<?php

namespace App\Services;

use App\Support\EngagementMix;
use App\Support\EngagementPlan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private string $effort;

    public function __construct()
    {
        $this->apiKey = config('claude.api_key');
        $this->model = config('claude.model');
        $this->maxTokens = config('claude.max_tokens');
        $this->effort = config('claude.effort', 'high');
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function analyzeRfp(string $filePath, string $prompt, array $attachmentPaths = []): array
    {
        // Claude analysis can take 30-90s; raise PHP's execution-time cap for this request.
        @set_time_limit(0);

        $content = $this->buildMultiDocContent($filePath, $prompt, $attachmentPaths);

        $response = $this->sendWithRetry($content);

        return $this->parseResponse($this->firstTextBlock($response));
    }

    private function buildMultiDocContent(string $primaryPath, string $prompt, array $attachmentPaths): array
    {
        $content = [];
        $textPieces = [];

        $docs = array_merge([$primaryPath], $attachmentPaths);

        foreach ($docs as $i => $path) {
            $fullPath = Storage::disk('public')->path($path);
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $label = $i === 0 ? 'PRIMARY RFP DOCUMENT' : 'SUPPORTING DOCUMENT ' . $i . ' (' . basename($path) . ')';

            if ($extension === 'pdf') {
                $content[] = [
                    'type' => 'text',
                    'text' => "--- {$label} ---",
                ];
                $content[] = [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => 'application/pdf',
                        'data' => base64_encode(file_get_contents($fullPath)),
                    ],
                ];
            } else {
                $textPieces[] = "--- BEGIN {$label} ---\n\n"
                    . $this->extractText($fullPath, $extension)
                    . "\n\n--- END {$label} ---";
            }
        }

        $instructions = $prompt;

        if (count($docs) > 1) {
            $instructions .= "\n\nNOTE: Multiple documents are provided — the PRIMARY RFP DOCUMENT plus SUPPORTING DOCUMENTS. Consider all of them together when scoring and summarizing. Supporting documents may clarify requirements, add scope, or change the fit assessment.";
        }

        if (! empty($textPieces)) {
            $instructions .= "\n\n" . implode("\n\n", $textPieces);
        }

        $content[] = ['type' => 'text', 'text' => $instructions];

        return $content;
    }

    /**
     * @param  array{max_tokens?: int, effort?: string, format?: array, timeout?: int}  $options
     *         per-call overrides; "format" is an output_config.format (structured output) block
     */
    private function sendWithRetry(array $content, int $maxRetries = 2, array $options = []): \Illuminate\Http\Client\Response
    {
        $attempt = 0;

        $outputConfig = array_filter([
            'effort' => $options['effort'] ?? $this->effort,
            'format' => $options['format'] ?? null,
        ]);

        while (true) {
            $attempt++;

            $response = Http::timeout($options['timeout'] ?? 180)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                    'output_config' => $outputConfig,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $this->assertNotRefused($response);

                return $response;
            }

            $status = $response->status();
            $body = $response->json();
            $errorType = $body['error']['type'] ?? '';

            Log::warning('Claude API request failed', [
                'attempt' => $attempt,
                'status' => $status,
                'error_type' => $errorType,
            ]);

            // Retry on rate limit (429) or overloaded (529)
            if (in_array($status, [429, 529]) && $attempt < $maxRetries) {
                $retryAfter = (int) $response->header('retry-after', 60);
                $waitSeconds = min($retryAfter, 90);

                Log::info("Rate limited — waiting {$waitSeconds}s before retry (attempt {$attempt}/{$maxRetries})");
                sleep($waitSeconds);
                continue;
            }

            // Friendly error messages
            $message = match ($errorType) {
                'rate_limit_error' => 'Rate limit reached — the document may be too large for your current API tier. Please wait a minute and try again.',
                'overloaded_error' => 'Claude is currently overloaded. Please try again in a few moments.',
                'authentication_error' => 'Invalid API key. Check your ANTHROPIC_API_KEY in Settings.',
                'invalid_request_error' => 'Invalid request — ' . ($body['error']['message'] ?? 'check the document format.'),
                default => 'Claude API error: ' . ($body['error']['message'] ?? $response->body()),
            };

            throw new \RuntimeException($message);
        }
    }

    /**
     * Return the first text block in the response.
     *
     * Opus 5 thinks by default, so content[0] is a thinking block — reading
     * content.0.text would silently yield an empty string.
     */
    private function firstTextBlock(\Illuminate\Http\Client\Response $response): string
    {
        foreach ($response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                return (string) ($block['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * A safety refusal comes back as HTTP 200 with stop_reason "refusal" and
     * no usable content. Fail loudly rather than storing an empty analysis.
     */
    private function assertNotRefused(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->json('stop_reason') !== 'refusal') {
            return;
        }

        Log::warning('Claude declined the request', [
            'category' => $response->json('stop_details.category'),
        ]);

        throw new \RuntimeException(
            'Claude declined to analyze this document. If the content is legitimate, '
            . 'try removing any unrelated attachments and rescanning.'
        );
    }

    private function extractText(string $fullPath, string $extension): string
    {
        $text = match ($extension) {
            'txt', 'csv', 'md' => file_get_contents($fullPath),
            'docx' => $this->extractDocxText($fullPath),
            'doc' => $this->extractDocText($fullPath),
            'xls', 'xlsx' => $this->extractSpreadsheetText($fullPath),
            default => throw new \RuntimeException("Unsupported file type: {$extension}"),
        };

        if (empty(trim($text))) {
            throw new \RuntimeException('Could not extract text from the uploaded document.');
        }

        // Sanitize to valid UTF-8
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    private function extractDocText(string $path): string
    {
        $output = [];
        $returnCode = 0;
        exec('antiword ' . escapeshellarg($path), $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            return implode("\n", $output);
        }

        throw new \RuntimeException(
            'Could not extract text from .doc file. Install antiword for .doc support, or convert to .docx or PDF first.'
        );
    }

    /**
     * Flatten every sheet to CSV-style rows so pricing tables and forms keep
     * their row/column structure in the prompt.
     */
    private function extractSpreadsheetText(string $path): string
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not open spreadsheet: ' . $e->getMessage());
        }

        $sections = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $lines = [];

            foreach ($sheet->toArray(null, true, true, false) as $row) {
                $cells = array_map(fn ($cell) => trim((string) $cell), $row);

                if (implode('', $cells) === '') {
                    continue;
                }

                $lines[] = implode(',', array_map(
                    fn (string $cell) => preg_match('/[",\n]/', $cell) ? '"' . str_replace('"', '""', $cell) . '"' : $cell,
                    $cells,
                ));
            }

            if ($lines) {
                $sections[] = "Sheet: {$sheet->getTitle()}\n" . implode("\n", $lines);
            }
        }

        $spreadsheet->disconnectWorksheets();

        return implode("\n\n", $sections);
    }

    private function extractDocxText(string $path): string
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open .docx file.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('Could not read content from .docx file.');
        }

        $text = strip_tags(str_replace('<', ' <', $xml));
        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * Draft proposal content from a screened RFP, shaped to the engagement the
     * user picked: one billing unit, a quantity of it, and the phases it is
     * split across.
     *
     * $rfp accepts: rfp_name, summary, requirements, red_flags,
     * submission_requirements, contact_name, contact_email, contact_company,
     * locality, file_path, attachment_paths.
     *
     * The returned `phases` array always holds exactly $plan->phaseCount
     * entries whose quantities sum to exactly $plan->quantity — the Investment
     * total has to be exact even when the model drifts.
     */
    public function generateProposalContent(array $rfp, EngagementPlan $plan): array
    {
        // Drafting a full proposal from the source documents takes 60-180s.
        @set_time_limit(0);

        $summary = $rfp['summary'] ?? '';
        $prompt = $this->buildProposalPrompt($rfp, $plan);
        $content = $this->buildProposalContent($prompt, $rfp);

        $response = $this->sendWithRetry($content);
        $parsed = $this->decodeJsonBlock($this->firstTextBlock($response));

        if (! is_array($parsed) || ! isset($parsed['introduction'])) {
            Log::warning('Proposal draft response could not be parsed as JSON', [
                'rfp_name' => $rfp['rfp_name'] ?? null,
            ]);

            return [
                'introduction' => '<p>' . e($summary) . '</p>',
                'cost_notes' => null,
                'phases' => $this->normalisePhases([], $plan),
                'contact_name' => $rfp['contact_name'] ?? null,
                'contact_email' => $rfp['contact_email'] ?? null,
                'contact_company' => $rfp['contact_company'] ?? null,
            ];
        }

        return [
            'introduction' => $this->cleanContactField($parsed['introduction'] ?? null) ?? '<p>' . e($summary) . '</p>',
            'cost_notes' => $this->cleanContactField($parsed['cost_notes'] ?? null),
            'phases' => $this->normalisePhases($parsed['phases'] ?? [], $plan),
            'contact_name' => $this->cleanContactField($parsed['contact_name'] ?? null) ?? ($rfp['contact_name'] ?? null),
            'contact_email' => $this->cleanContactField($parsed['contact_email'] ?? null) ?? ($rfp['contact_email'] ?? null),
            'contact_company' => $this->cleanContactField($parsed['contact_company'] ?? null) ?? ($rfp['contact_company'] ?? null),
        ];
    }

    private function buildProposalPrompt(array $rfp, EngagementPlan $plan): string
    {
        $rfpName = $rfp['rfp_name'] ?? 'Untitled RFP';
        $summary = $rfp['summary'] ?? '';

        $requirementsList = collect($rfp['requirements'] ?? [])
            ->map(fn ($r, $i) => ($i + 1) . ". {$r}")
            ->implode("\n") ?: 'None extracted.';

        $redFlagsList = collect($rfp['red_flags'] ?? [])
            ->map(fn ($r) => "- {$r}")
            ->implode("\n") ?: 'None.';

        $submissionList = collect($rfp['submission_requirements'] ?? [])
            ->map(fn ($r) => "- {$r}")
            ->implode("\n") ?: 'None extracted.';

        $localityLine = filled($rfp['locality'] ?? null) ? "CLIENT LOCALITY: {$rfp['locality']}\n\n" : '';

        return implode("\n\n", array_filter([
            <<<INTRO
            You are the lead proposal writer at divStrong, a boutique web development and custom software studio staffed by veteran technologists. You are drafting the first internal draft of a proposal responding to the RFP below. A human reviews and refines it afterwards, so be specific and substantive rather than hedging.

            RFP NAME: {$rfpName}

            {$localityLine}SUMMARY OF THE RFP:
            {$summary}

            KEY REQUIREMENTS EXTRACTED FROM THE RFP:
            {$requirementsList}

            SUBMISSION REQUIREMENTS (context only — never write about these in the proposal copy):
            {$submissionList}

            RISKS / RED FLAGS (design the delivery plan around these, but never mention them in the proposal copy):
            {$redFlagsList}
            INTRO,
            $this->scopeGuidanceBlock($plan),
            $this->engagementBlock($plan),
            $this->proposalJsonSpec($plan),
        ]));
    }

    /**
     * The optional scope prompt from the modal. Placed ahead of the structural
     * rules and marked as overriding, so the lead's steer wins over the
     * drafter's own read of the RFP.
     */
    private function scopeGuidanceBlock(EngagementPlan $plan): ?string
    {
        if (blank($plan->scopePrompt)) {
            return null;
        }

        return <<<GUIDANCE
        ADDITIONAL GUIDANCE FROM THE PROPOSAL LEAD — TREAT THIS AS AUTHORITATIVE:
        {$plan->scopePrompt}

        This guidance reflects context the RFP document does not contain. Where it conflicts with your own reading of the RFP, follow the guidance. Shape the phase themes, the scope items, and their emphasis around it.
        GUIDANCE;
    }

    private function engagementBlock(EngagementPlan $plan): string
    {
        $phases = $plan->phaseCount;
        $phaseLabel = strtolower($plan->config()['phase_label']);
        $quantityLabel = $plan->quantityLabel();
        $total = number_format($plan->total(), 0);
        $blurb = $plan->blurb();

        $sizing = $plan->unit === 'sprint'
            ? "Each {$phaseLabel} is exactly one sprint, so there are {$phases} of them. Set \"quantity\" to 1 on every {$phaseLabel}."
            : "Divide the {$plan->quantity} {$plan->unitLabel()} across the {$phases} {$phaseLabel}s by setting each one's \"quantity\". They MUST add up to exactly {$plan->quantity}. Weight them by real effort — a heavier {$phaseLabel} gets more {$plan->unitLabel()}.";

        return <<<PLAN_BLOCK
        ENGAGEMENT STRUCTURE — THIS IS THE MOST IMPORTANT PART:
        This engagement is sold as {$quantityLabel} — \${$total} total. One {$plan->unitLabel(1)} is {$blurb}.

        Delivery is organised into exactly {$phases} {$phaseLabel}s. {$sizing}

        Divide the ENTIRE scope of the RFP across those {$phases} {$phaseLabel}s. Rules:
        - The plan must fit inside {$quantityLabel}. Do not propose more work than that buys — if the RFP is larger than the budget, scope the {$phaseLabel}s to the highest-value subset and keep them honest about what is included.
        - Sequence them so each {$phaseLabel} depends only on what came before: discovery and foundations first, core functionality next, integrations and content migration after that, then accessibility and performance hardening, launch, and post-launch support.
        - Assign every requirement you commit to a {$phaseLabel}. Do not silently drop work you claim to cover.
        - Give each {$phaseLabel} a short client-facing theme title of 2-4 words. Do not prefix it with a number — the numbering is added separately.
        - Each {$phaseLabel} holds 2-5 scope items. Each scope item has a title, a one-or-two sentence description, and 2-4 concrete deliverable bullets. Bullets are things that get handed over, not activities.
        - Scope item titles must be specific to THIS RFP, not generic web-project boilerplate.
        PLAN_BLOCK;
    }

    private function proposalJsonSpec(EngagementPlan $plan): string
    {
        $phases = $plan->phaseCount;
        $phaseLabel = strtolower($plan->config()['phase_label']);
        $unitLabel = $plan->unitLabel();
        $quantityNote = $plan->unit === 'sprint'
            ? 'always 1'
            : "how many {$unitLabel} this {$phaseLabel} consumes; all {$phases} must sum to exactly {$plan->quantity}";

        return <<<SPEC
        RESPOND WITH JSON ONLY, IN THIS EXACT SHAPE:
        ```json
        {
            "introduction": "<2-3 paragraph HTML overview wrapped in <p> tags. Paragraph 1: mirror back what this organization is asking for, in their own terms, showing you read and understood the RFP. Paragraph 2: how divStrong's team of veteran technologists will deliver it — reference the {$phases}-{$phaseLabel} structure and the discipline and predictability it brings. Paragraph 3: why this team is the right fit, plus genuine enthusiasm for the work. Address the reader as 'your organization' or 'your team' — never invent a client name. No headings, no lists, no markdown — <p> tags only.>",
            "cost_notes": "<one or two sentences shown under the Investment table explaining the pricing model and the overall timeline. Plain text, no HTML.>",
            "phases": [
                {
                    "number": 1,
                    "title": "<2-4 word theme for this {$phaseLabel}>",
                    "summary": "<one sentence on what this {$phaseLabel} delivers — used as the Investment line item detail>",
                    "quantity": <{$quantityNote}>,
                    "scope_items": [
                        {
                            "title": "<specific scope item title>",
                            "description": "<one or two sentences>",
                            "bullets": ["<concrete deliverable>", "<concrete deliverable>"]
                        }
                    ]
                }
            ],
            "contact_name": "<contact person named in the RFP, or null>",
            "contact_email": "<contact email from the RFP, or null>",
            "contact_company": "<issuing organization or agency name, or null>"
        }
        ```

        The "phases" array MUST contain exactly {$phases} objects, numbered 1 through {$phases}. Output the JSON block and nothing else.
        SPEC;
    }

    /**
     * Attach the source RFP documents when they are still on disk — grounding
     * the draft in the real document beats working from the summary alone.
     * Falls back to a prompt-only request if anything is unreadable.
     */
    private function buildProposalContent(string $prompt, array $rfp): array
    {
        $filePath = $rfp['file_path'] ?? null;

        if (blank($filePath)) {
            return [['type' => 'text', 'text' => $prompt]];
        }

        try {
            return $this->buildMultiDocContent(
                $filePath,
                $prompt,
                array_values(array_filter((array) ($rfp['attachment_paths'] ?? []))),
            );
        } catch (\Throwable $e) {
            Log::warning('Proposal draft falling back to summary-only context', [
                'error' => $e->getMessage(),
            ]);

            return [['type' => 'text', 'text' => $prompt]];
        }
    }

    /**
     * Force the model's phase list to exactly the planned count — extra phases
     * fold their scope into the last one kept, missing ones get a neutral
     * placeholder — then re-allocate the billing units so they sum exactly.
     */
    private function normalisePhases(mixed $phases, EngagementPlan $plan): array
    {
        $clean = [];

        foreach (is_array($phases) ? $phases : [] as $phase) {
            if (! is_array($phase)) {
                continue;
            }

            $clean[] = [
                'title' => $this->cleanContactField($phase['title'] ?? null) ?? 'Delivery',
                'summary' => $this->cleanContactField($phase['summary'] ?? null) ?? '',
                'weight' => is_numeric($phase['quantity'] ?? null) ? (float) $phase['quantity'] : 0.0,
                'scope_items' => $this->normaliseScopeItems($phase['scope_items'] ?? []),
            ];
        }

        if (count($clean) > $plan->phaseCount) {
            $overflow = array_splice($clean, $plan->phaseCount);
            $last = count($clean) - 1;

            foreach ($overflow as $extra) {
                $clean[$last]['scope_items'] = array_merge($clean[$last]['scope_items'], $extra['scope_items']);
                $clean[$last]['weight'] += $extra['weight'];
            }
        }

        while (count($clean) < $plan->phaseCount) {
            $clean[] = ['title' => 'Delivery', 'summary' => '', 'weight' => 0.0, 'scope_items' => []];
        }

        $allocation = $plan->allocate(array_column($clean, 'weight'));

        foreach ($clean as $i => $phase) {
            unset($clean[$i]['weight']);
            $clean[$i]['number'] = $i + 1;
            $clean[$i]['quantity'] = $allocation[$i];
        }

        return array_values($clean);
    }

    /**
     * Draft proposal content from a client brief rather than an RFP document:
     * the only source material is what the proposal lead typed into the modal,
     * plus who the client is and how the engagement was sized.
     *
     * The returned `sprints` array always holds exactly $mix->sprints entries,
     * numbered 1..n — the Investment rows and the milestones are built from it,
     * so the count has to be exact even when the model drifts.
     */
    public function generateClientProposalContent(array $brief, EngagementMix $mix): array
    {
        // Drafting a full proposal takes 60-180s.
        @set_time_limit(0);

        $prompt = $this->buildClientProposalPrompt($brief, $mix);

        $response = $this->sendWithRetry([['type' => 'text', 'text' => $prompt]]);
        $parsed = $this->decodeJsonBlock($this->firstTextBlock($response));

        if (! is_array($parsed)) {
            Log::warning('Client proposal draft response could not be parsed as JSON', [
                'client' => $brief['client_company'] ?? $brief['client_name'] ?? null,
            ]);

            $parsed = [];
        }

        return [
            'project_title' => $this->cleanContactField($parsed['project_title'] ?? null),
            'introduction' => $this->cleanContactField($parsed['introduction'] ?? null)
                ?? '<p>' . e((string) $mix->scopePrompt) . '</p>',
            'cost_notes' => $this->cleanContactField($parsed['cost_notes'] ?? null),
            'sprints' => $this->normaliseSprints($parsed['sprints'] ?? [], $mix),
            'day_work' => $mix->hasDays() ? $this->cleanContactField($parsed['day_work'] ?? null) : null,
            'hour_work' => $mix->hasHours() ? $this->cleanContactField($parsed['hour_work'] ?? null) : null,
        ];
    }

    private function buildClientProposalPrompt(array $brief, EngagementMix $mix): string
    {
        $clientName = $this->cleanContactField($brief['client_name'] ?? null);
        $company = $this->cleanContactField($brief['client_company'] ?? null);
        $title = $this->cleanContactField($brief['project_title'] ?? null);

        $who = $company
            ? "CLIENT: {$company}" . ($clientName ? " (primary contact: {$clientName})" : '')
            : 'CLIENT: ' . ($clientName ?: 'the client');

        $titleLine = $title
            ? "WORKING PROJECT TITLE: {$title}"
            : 'WORKING PROJECT TITLE: none given — propose one.';

        $scopePrompt = (string) $mix->scopePrompt;

        return implode("\n\n", array_filter([
            <<<INTRO
            You are the lead proposal writer at divStrong, a boutique web development and custom software studio staffed by veteran technologists. You are drafting the first internal draft of a proposal for a client relationship — there is no RFP document, only the brief below from the divStrong lead who spoke with them. A human reviews and refines this draft afterwards, so be specific and substantive rather than hedging.

            {$who}

            {$titleLine}

            THE BRIEF — everything known about the project scope. Treat it as authoritative and build the whole proposal around it:
            {$scopePrompt}
            INTRO,
            $this->clientEngagementBlock($mix),
            $this->clientVoiceBlock(),
            $this->clientProposalJsonSpec($mix),
        ]));
    }

    private function clientEngagementBlock(EngagementMix $mix): string
    {
        $sprints = $mix->sprints;
        $blurb = $mix->sprintBlurb();
        $rate = number_format($mix->sprintRate, 0);
        $total = number_format($mix->total(), 0);
        $weeks = $sprints * 2;

        $supplemental = [];

        if ($mix->hasDays()) {
            $supplemental[] = 'ALSO SOLD: ' . $mix->dayLine()
                . ' of day-rate development time, billed as one extra Investment row. This is flexible time held alongside the sprints — refinement, content work, support, or smaller scope the sprints do not cover.';
        }

        if ($mix->hasHours()) {
            $supplemental[] = 'ALSO SOLD: ' . $mix->hourLine()
                . ' of hour-rate development time, billed as one extra Investment row. This is small-batch time for ad-hoc requests and fixes.';
        }

        $supplementalBlock = $supplemental ? "\n\n" . implode("\n\n", $supplemental) : '';

        return <<<PLAN_BLOCK
        ENGAGEMENT STRUCTURE — THIS IS THE MOST IMPORTANT PART:
        Delivery is organised into exactly {$sprints} sprints. One sprint is {$blurb}, sold at \${$rate} each, so the sprints run about {$weeks} weeks end to end. The whole engagement totals \${$total}.{$supplementalBlock}

        Divide the ENTIRE project across those {$sprints} sprints. Rules:
        - The plan must fit inside what the client is buying. Do not propose more work than {$sprints} sprints can deliver — if the brief is larger than that, scope the sprints to the highest-value subset and be honest about what is included.
        - Sequence them so each sprint depends only on what came before: discovery and foundations first, core functionality next, integrations and content after that, then hardening, launch, and handover.
        - Assign every part of the brief you commit to a sprint. Do not silently drop work you claim to cover.
        - Give each sprint a short client-facing theme title of 2-4 words. Do not prefix it with a number — the numbering is added separately.
        - Each sprint holds 2-5 scope items. Each scope item has a title, a one-or-two sentence description, and 2-4 concrete deliverable bullets. Bullets are things that get handed over, not activities.
        - Scope item titles must be specific to THIS project, not generic web-project boilerplate.
        PLAN_BLOCK;
    }

    /**
     * The client reads this document, not an evaluation committee — so the
     * sprint goals have to survive a non-technical reader.
     */
    private function clientVoiceBlock(): string
    {
        return <<<VOICE
        HOW TO WRITE IT:
        The reader is a business owner or a marketing lead, not an engineer. Write so that someone with no technical background finishes each sprint's goal knowing exactly what they will be able to see, use, or show their team when it ends.
        - Say what the client gets, not what we do. "You'll be able to publish a new case study yourself in under five minutes" beats "implement CMS content type with WYSIWYG editing".
        - No jargon, acronyms, framework names, or infrastructure terms in the sprint goals. Scope item bullets may name a concrete deliverable — a page, a form, a dashboard, a migrated archive — but still avoid implementation detail.
        - Short sentences. Plain words. Confident and warm, never salesy or padded with adjectives.
        - Never invent facts about the client's business, their current systems, their traffic, or their team that the brief does not state.
        VOICE;
    }

    private function clientProposalJsonSpec(EngagementMix $mix): string
    {
        $sprints = $mix->sprints;

        $dayWork = $mix->hasDays()
            ? '"day_work": "<3-7 word plain-language label for what the ' . $mix->days
                . ' days of flexible development time cover, e.g. Content migration and design refinements>",'
            : '';

        $hourWork = $mix->hasHours()
            ? '"hour_work": "<3-7 word plain-language label for what the ' . $mix->hours
                . ' hours of development time cover, e.g. Ad-hoc fixes and small enhancements>",'
            : '';

        return <<<SPEC
        RESPOND WITH JSON ONLY, IN THIS EXACT SHAPE:
        ```json
        {
            "project_title": "<short project title, 3-7 words, specific to this engagement>",
            "introduction": "<2-3 paragraph HTML overview wrapped in <p> tags. Paragraph 1: mirror back what this client is trying to achieve, in their own terms, showing you listened. Paragraph 2: how divStrong will deliver it — reference the {$sprints}-sprint structure and the predictability and visible progress it brings. Paragraph 3: why this team is the right fit, plus genuine enthusiasm for the work. Address the reader as 'you' and 'your team'. No headings, no lists, no markdown — <p> tags only.>",
            "cost_notes": "<one or two sentences shown under the Investment table, giving the overall timeline and when the clock starts. Terse and formal, matching the house style of past proposals: \"Project to be completed within two (2) months of project start date, effective upon receipt of initial deposit.\" Write counts as word-then-digits the same way. Plain text, no HTML.>",
            {$dayWork}
            {$hourWork}
            "sprints": [
                {
                    "number": 1,
                    "title": "<2-4 word theme for this sprint>",
                    "goal": "<ONE plain-language sentence naming what the client will be able to see or do when this sprint ends. No jargon. This is the sentence the client reads next to their payment milestone.>",
                    "scope_items": [
                        {
                            "title": "<specific scope item title>",
                            "description": "<one or two sentences in plain language>",
                            "bullets": ["<concrete deliverable>", "<concrete deliverable>"]
                        }
                    ]
                }
            ]
        }
        ```

        The "sprints" array MUST contain exactly {$sprints} objects, numbered 1 through {$sprints}. Output the JSON block and nothing else.
        SPEC;
    }

    /**
     * Force the model's sprint list to exactly the number sold — extra sprints
     * fold their scope into the last one kept, missing ones get a neutral
     * placeholder — so the Investment rows and the milestones always line up.
     */
    private function normaliseSprints(mixed $sprints, EngagementMix $mix): array
    {
        $clean = [];

        foreach (is_array($sprints) ? $sprints : [] as $sprint) {
            if (! is_array($sprint)) {
                continue;
            }

            $clean[] = [
                'title' => $this->cleanContactField($sprint['title'] ?? null) ?? 'Delivery',
                'goal' => $this->cleanContactField($sprint['goal'] ?? null) ?? '',
                'scope_items' => $this->normaliseScopeItems($sprint['scope_items'] ?? []),
            ];
        }

        if (count($clean) > $mix->sprints) {
            $overflow = array_splice($clean, $mix->sprints);
            $last = count($clean) - 1;

            foreach ($overflow as $extra) {
                $clean[$last]['scope_items'] = array_merge($clean[$last]['scope_items'], $extra['scope_items']);
            }
        }

        while (count($clean) < $mix->sprints) {
            $clean[] = ['title' => 'Delivery', 'goal' => '', 'scope_items' => []];
        }

        foreach ($clean as $i => $sprint) {
            $clean[$i]['number'] = $i + 1;
        }

        return array_values($clean);
    }


    private function normaliseScopeItems(mixed $items): array
    {
        $clean = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = $this->cleanContactField($item['title'] ?? null);

            if ($title === null) {
                continue;
            }

            $clean[] = [
                'title' => $title,
                'description' => $this->cleanContactField($item['description'] ?? null) ?? '',
                'bullets' => collect((array) ($item['bullets'] ?? []))
                    ->filter(fn ($b) => is_string($b) && trim($b) !== '')
                    ->map(fn ($b) => trim($b))
                    ->values()
                    ->all(),
            ];
        }

        return $clean;
    }


    /**
     * Pull a JSON object out of a model response, whether it arrived in a
     * fenced block, bare, or wrapped in prose.
     */
    private function decodeJsonBlock(string $rawText): mixed
    {
        $match = [];

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $rawText, $match)) {
            $decoded = json_decode($match[1], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode($rawText, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($rawText, '{');
        $end = strrpos($rawText, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return json_decode(substr($rawText, $start, $end - $start + 1), true);
        }

        return null;
    }

    private function parseResponse(string $rawText): array
    {
        // Try to extract JSON from the response
        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $rawText, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[1], true);
        } else {
            $parsed = json_decode($rawText, true);
        }

        if (is_array($parsed) && isset($parsed['score'])) {
            return [
                'rfp_name' => $parsed['rfp_name'] ?? null,
                'contact_name' => $this->cleanContactField($parsed['contact_name'] ?? null),
                'contact_title' => $this->cleanContactField($parsed['contact_title'] ?? null),
                'contact_department' => $this->cleanContactField($parsed['contact_department'] ?? null),
                'contact_email' => $this->cleanContactField($parsed['contact_email'] ?? null),
                'contact_phone' => $this->cleanContactField($parsed['contact_phone'] ?? null),
                'due_date' => $this->parseDueDate($parsed['due_date'] ?? null),
                'pre_bid_conference_date' => $this->parseDueDate($parsed['pre_bid_conference_date'] ?? null),
                'pre_bid_conference_details' => $this->cleanContactField($parsed['pre_bid_conference_details'] ?? null),
                'locality_city' => $this->cleanContactField($parsed['locality_city'] ?? null),
                'locality_state' => $this->cleanContactField($parsed['locality_state'] ?? null),
                'locality_county' => $this->cleanContactField($parsed['locality_county'] ?? null),
                'target_department' => $this->cleanContactField($parsed['target_department'] ?? null),
                'score' => (int) max(0, min(100, $parsed['score'])),
                'summary' => $parsed['summary'] ?? '',
                'red_flags' => $parsed['red_flags'] ?? [],
                'requirements' => $parsed['requirements'] ?? [],
                'submission_requirements' => $parsed['submission_requirements'] ?? [],
                'raw_response' => $rawText,
            ];
        }

        // If JSON parsing failed, return raw text with a default structure
        return [
            'contact_name' => null,
            'contact_title' => null,
            'contact_department' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'due_date' => null,
            'pre_bid_conference_date' => null,
            'pre_bid_conference_details' => null,
            'score' => null,
            'summary' => $rawText,
            'red_flags' => [],
            'requirements' => [],
            'submission_requirements' => [],
            'raw_response' => $rawText,
        ];
    }

    private function cleanContactField(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function parseDueDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '' || strtolower($value) === 'null') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Turn a free-text instruction into changes to a proposal's existing scope — a sprint table
     * pasted in, "drop sprint 6 and fold its work into 5", "tighten every description", "add a
     * mobile app phase". Claude reads the current scope (with item ids) and decides what to
     * create, edit, rename or delete; the person never has to name ids or library entries.
     *
     * Returns {reply, operations} in App\Support\ScopeEdits' shape. Nothing is saved here: the
     * person who asked previews the changes first.
     *
     * @param  array{project_title?: ?string, client_company?: ?string, introduction?: ?string, current_scope?: string, library?: string}  $context
     * @return array{reply: string, operations: array<int, array<string, mixed>>}
     */
    public function planScopeChanges(string $instructions, array $context = []): array
    {
        @set_time_limit(0);

        $project = $this->cleanContactField($context['project_title'] ?? null) ?? 'this project';
        $client = $this->cleanContactField($context['client_company'] ?? null) ?? 'the client';
        $intro = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($context['introduction'] ?? ''))));
        $intro = $intro !== '' ? mb_substr($intro, 0, 4000) : 'Not written yet.';
        $current = $this->cleanContactField($context['current_scope'] ?? null) ?? 'The scope is empty.';
        $library = $this->cleanContactField($context['library'] ?? null) ?? 'None.';

        $prompt = <<<PROMPT
        You maintain the Scope of Work section of a divStrong proposal: the part that tells the client, section by section, exactly what they get. The proposal lead has typed an instruction below. Work out what they want done to the scope and express it as a list of operations against the current scope.

        PROJECT: {$project}
        CLIENT: {$client}

        PROPOSAL OVERVIEW (context only):
        {$intro}

        CURRENT SCOPE — sections in order, each item with its id:
        {$current}

        HOUSE SCOPE LIBRARY — reusable items divStrong often includes. Adapt one when it genuinely fits what the lead asked for; never add one they did not ask for:
        {$library}

        THE LEAD'S INSTRUCTION:
        """
        {$instructions}
        """

        HOW TO INTERPRET IT:
        - Decide for yourself whether this means writing new scope, revising existing items, restructuring sections, removing work, or some mix. The lead will not name item ids, library entries or operations — that is your job.
        - A pasted sprint table or feature list for an empty scope means create the whole scope. The same list against an existing scope means bring the scope in line with it: update items that cover the same work, create what is missing, delete what the list no longer includes.
        - A targeted request ("rename sprint 3", "drop the SEO item", "make the bullets more concrete") touches only what it names. Leave every other item alone — do not emit operations for items you are not changing.
        - Prefer "update" over delete-and-recreate when an item keeps its purpose.
        - When sprints are added or removed in the middle, renumber the following sprint sections with rename_section so the numbering stays continuous.
        - If the instruction is not about the scope at all (a question, a bug report, something unrelated), return no operations and say in "reply" what this tool can do.

        {$this->clientVoiceBlock()}

        WRITING RULES FOR NEW OR REWRITTEN ITEMS:
        - Sprint sections are titled "Sprint #N — Theme In Title Case" (an em dash, a 2-5 word theme). Other sections get a short Title Case name. Match the naming already used in the current scope.
        - A section holds 2-5 items. Each item has a specific Title Case title, a one-or-two sentence plain-language description, and 2-4 concrete deliverable bullets — things handed over, not activities.
        - Never add systems, vendors, numbers or features the instruction, overview or current scope do not mention; where the instruction says something is conditional ("if retained", "agreed"), keep it conditional.

        OPERATIONS — every field is always present; use "" / 0 / [] for fields an action does not use:
        - create: new item. "section" = the section it goes in (existing or new). "after_section" = for a NEW section only, the existing section it should follow ("" = at the end; "^" = at the very start). item_id = 0.
        - update: change item "item_id". Set only the fields that change — "" for title/description and [] for bullets mean keep as is. A non-empty "section" moves the item to that section (end of it).
        - delete: remove item "item_id".
        - rename_section: rename "section" to "new_section" (all its items follow).
        - delete_section: remove section "section" and every item in it.

        "reply": one or two plain sentences telling the lead what you changed and anything you deliberately left out or need from them.
        PROMPT;

        $response = $this->sendWithRetry([['type' => 'text', 'text' => $prompt]], options: [
            // A full multi-sprint scope plus thinking runs well past the default cap.
            'max_tokens' => max($this->maxTokens, 32000),
            'effort' => 'medium',
            'timeout' => 300,
            'format' => ['type' => 'json_schema', 'schema' => $this->scopeChangesSchema()],
        ]);

        if ($response->json('stop_reason') === 'max_tokens') {
            throw new \RuntimeException('That change is too large to draft in one go. Try it a few sprints at a time.');
        }

        $parsed = json_decode($this->firstTextBlock($response), true);

        if (! is_array($parsed)) {
            throw new \RuntimeException('Claude did not return changes I could read. Please try again.');
        }

        return [
            'reply' => trim((string) ($parsed['reply'] ?? '')),
            'operations' => array_values(array_filter((array) ($parsed['operations'] ?? []), 'is_array')),
        ];
    }

    private function scopeChangesSchema(): array
    {
        $string = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'reply' => $string,
                'operations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => ['type' => 'string', 'enum' => ['create', 'update', 'delete', 'rename_section', 'delete_section']],
                            'item_id' => ['type' => 'integer'],
                            'section' => $string,
                            'new_section' => $string,
                            'after_section' => $string,
                            'title' => $string,
                            'description' => $string,
                            'bullets' => ['type' => 'array', 'items' => $string],
                        ],
                        'required' => ['action', 'item_id', 'section', 'new_section', 'after_section', 'title', 'description', 'bullets'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['reply', 'operations'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Write or revise a proposal's Overview from a free-text instruction — "write the overview
     * from this brief", "make it shorter", "mention the March launch date". Claude reads the
     * current overview and scope and returns the full replacement text.
     *
     * Returns {reply, overview}; an empty overview means nothing to change. Nothing is saved here.
     *
     * @param  array{project_title?: ?string, client_company?: ?string, client_name?: ?string, introduction?: ?string, current_scope?: string}  $context
     * @return array{reply: string, overview: string}
     */
    public function draftOverview(string $instructions, array $context = []): array
    {
        @set_time_limit(0);

        $project = $this->cleanContactField($context['project_title'] ?? null) ?? 'this project';
        $client = $this->cleanContactField($context['client_company'] ?? null)
            ?? $this->cleanContactField($context['client_name'] ?? null) ?? 'the client';
        $current = trim((string) ($context['introduction'] ?? ''));
        $current = $current !== '' ? mb_substr($current, 0, 12000) : 'Not written yet.';
        $scope = $this->cleanContactField($context['current_scope'] ?? null) ?? 'The scope is empty.';

        $prompt = <<<PROMPT
        You write the Overview section of a divStrong proposal: the opening pages a client reads before the scope and pricing. The proposal lead has typed an instruction below. Work out what they want and return the complete Overview as it should read afterwards.

        PROJECT: {$project}
        CLIENT: {$client}

        CURRENT OVERVIEW (HTML):
        {$current}

        SCOPE OF WORK (context only — do not restate it item by item):
        {$scope}

        THE LEAD'S INSTRUCTION:
        """
        {$instructions}
        """

        HOW TO INTERPRET IT:
        - Decide whether this means writing the Overview from scratch, revising it, or a targeted edit. Pasted notes, a brief or RFP text mean write (or rewrite) the Overview from them.
        - A targeted request ("shorter", "warmer", "mention the March launch", "drop the second paragraph") changes only what it names. Keep every other sentence as it is.
        - If the instruction is not about the Overview at all (a question, something unrelated), return an empty "overview" and say in "reply" what this tool can do.

        {$this->clientVoiceBlock()}

        WRITING RULES:
        - Unless the lead asks otherwise: 2-4 paragraphs. First mirror back what the client is trying to achieve, in their terms. Then how divStrong will deliver it. Then why this team is the right fit, with genuine enthusiasm.
        - Address the reader as "you" and "your team". Never invent a client name, people, dates, numbers, systems or commitments the instruction, current overview or scope do not mention.
        - HTML only: <p> paragraphs, with <strong>, <em>, <ul>/<li> where the lead asks for them. No headings, no markdown, no inline styles.

        "overview": the full replacement HTML, or "" when nothing should change.
        "reply": one or two plain sentences telling the lead what you changed and anything you need from them.
        PROMPT;

        $response = $this->sendWithRetry([['type' => 'text', 'text' => $prompt]], options: [
            'effort' => 'medium',
            'timeout' => 300,
            'format' => ['type' => 'json_schema', 'schema' => [
                'type' => 'object',
                'properties' => ['reply' => ['type' => 'string'], 'overview' => ['type' => 'string']],
                'required' => ['reply', 'overview'],
                'additionalProperties' => false,
            ]],
        ]);

        if ($response->json('stop_reason') === 'max_tokens') {
            throw new \RuntimeException('That overview ran too long to draft. Try asking for something shorter.');
        }

        $parsed = json_decode($this->firstTextBlock($response), true);

        if (! is_array($parsed)) {
            throw new \RuntimeException('Claude did not return an overview I could read. Please try again.');
        }

        return [
            'reply' => trim((string) ($parsed['reply'] ?? '')),
            'overview' => trim((string) ($parsed['overview'] ?? '')),
        ];
    }
}
