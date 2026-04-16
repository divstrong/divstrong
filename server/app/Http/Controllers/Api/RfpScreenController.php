<?php

namespace App\Http\Controllers\Api;

use App\Actions\ScreenRfp;
use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\RfpScreen;
use App\Models\TermsLibrary;
use App\Services\ClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RfpScreenController extends Controller
{
    private const ALLOWED_MIMES = 'pdf,doc,docx,txt,csv,md';

    public function index(Request $request): JsonResponse
    {
        $screens = RfpScreen::query()
            ->forUser()
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn (RfpScreen $s) => $this->presentSummary($s));

        return response()->json(['data' => $screens]);
    }

    public function show(Request $request, RfpScreen $rfpScreen): JsonResponse
    {
        $this->authorizeOwnership($request, $rfpScreen);

        return response()->json(['data' => $this->presentDetail($rfpScreen)]);
    }

    public function store(Request $request, ScreenRfp $action): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:' . self::ALLOWED_MIMES, 'max:20480'],
            'rfp_name' => ['nullable', 'string', 'max:255'],
            'prompt' => ['nullable', 'string'],
        ]);

        try {
            $screen = $action->create(
                $request->user(),
                $data['file'],
                $data['prompt'] ?? null,
                $data['rfp_name'] ?? null,
            );

            return response()->json(['data' => $this->presentDetail($screen)], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Screening failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function rescan(Request $request, RfpScreen $rfpScreen, ScreenRfp $action): JsonResponse
    {
        $this->authorizeOwnership($request, $rfpScreen);

        $data = $request->validate([
            'file' => ['nullable', 'file', 'mimes:' . self::ALLOWED_MIMES, 'max:20480'],
        ]);

        try {
            $screen = $action->rescan($rfpScreen, $data['file'] ?? null);

            return response()->json(['data' => $this->presentDetail($screen)]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Rescan failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createProposal(Request $request, RfpScreen $rfpScreen): JsonResponse
    {
        $this->authorizeOwnership($request, $rfpScreen);
        abort_unless($rfpScreen->status === 'completed', 422, 'RFP must be fully analyzed first.');

        try {
            $service = new ClaudeService();
            $content = $service->generateProposalContent(
                $rfpScreen->rfp_name ?? 'Untitled RFP',
                $rfpScreen->summary ?? '',
                $rfpScreen->requirements ?? [],
                $rfpScreen->red_flags ?? [],
            );

            $proposal = Proposal::create([
                'user_id' => Auth::id(),
                'project_title' => $rfpScreen->rfp_name ?? 'Untitled Project',
                'proposal_date' => now(),
                'valid_until' => now()->addDays(60),
                'client_name' => $content['contact_name'] ?? $rfpScreen->contact_name ?? '',
                'client_email' => $content['contact_email'] ?? $rfpScreen->contact_email ?? '',
                'client_company' => $content['contact_company'] ?? '',
                'introduction' => $content['introduction'] ?? '',
                'status' => ProposalStatus::Draft,
                'view_count' => 0,
            ]);

            foreach ($content['scope_items'] ?? [] as $i => $item) {
                $proposal->scopeItems()->create([
                    'category' => $item['category'] ?? 'Development',
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'bullets' => $item['bullets'] ?? [],
                    'sort_order' => $i,
                ]);
            }

            foreach (TermsLibrary::where('is_active', true)->orderBy('sort_order')->get() as $i => $term) {
                $proposal->terms()->create([
                    'content' => $term->content,
                    'sort_order' => $i,
                ]);
            }

            return response()->json([
                'proposal_id' => $proposal->id,
                'message' => 'Draft proposal created from RFP.',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Proposal generation from RFP failed', [
                'rfp_screen_id' => $rfpScreen->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Proposal generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, RfpScreen $rfpScreen): JsonResponse
    {
        $this->authorizeOwnership($request, $rfpScreen);

        if ($rfpScreen->file_path) {
            Storage::disk('public')->delete($rfpScreen->file_path);
        }

        $rfpScreen->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function authorizeOwnership(Request $request, RfpScreen $screen): void
    {
        abort_unless($request->user()->isAdmin() || $screen->user_id === $request->user()->id, 403);
    }

    private function presentSummary(RfpScreen $s): array
    {
        return [
            'id' => $s->id,
            'rfp_name' => $s->rfp_name,
            'status' => $s->status,
            'score' => $s->score,
            'score_label' => $s->score_label,
            'score_color' => $s->score_badge_color,
            'due_date' => $s->due_date?->toDateString(),
            'created_at' => $s->created_at?->toIso8601String(),
            'analyzed_at' => $s->analyzed_at?->toIso8601String(),
        ];
    }

    private function presentDetail(RfpScreen $s): array
    {
        return array_merge($this->presentSummary($s), [
            'summary' => $s->summary,
            'red_flags' => $s->red_flags ?? [],
            'requirements' => $s->requirements ?? [],
            'contact' => [
                'name' => $s->contact_name,
                'title' => $s->contact_title,
                'department' => $s->contact_department,
                'email' => $s->contact_email,
                'phone' => $s->contact_phone,
            ],
            'file' => [
                'original_filename' => $s->original_filename,
                'file_type' => $s->file_type,
                'url' => $s->file_path ? Storage::disk('public')->url($s->file_path) : null,
            ],
        ]);
    }
}
